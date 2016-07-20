<img align="right" style="float: right; height: 200px;" src="doc/img/stackformation_200px.png">

# StackFormation

**Lightweight AWS CloudFormation Stack, Template and Parameter Manager and Preprocessor**

[![Build Status](https://travis-ci.org/AOEpeople/StackFormation.svg?branch=master)](https://travis-ci.org/AOEpeople/StackFormation)
[![Code Climate](https://codeclimate.com/github/AOEpeople/StackFormation/badges/gpa.svg)](https://codeclimate.com/github/AOEpeople/StackFormation)
[![Test Coverage](https://codeclimate.com/github/AOEpeople/StackFormation/badges/coverage.svg)](https://codeclimate.com/github/AOEpeople/StackFormation/coverage)

Author: 
 - [Fabrizio Branca](https://twitter.com/fbrnc)

Contributors:
 - [Lee Saferite](https://github.com/LeeSaferite)
 - [Julian Kleinhans](https://github.com/kj187)

### Installation

#### Via composer

[Install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) first, then:
```
composer require aoepeople/stackformation
```

#### Using the phar

Grab the latest release from https://github.com/AOEpeople/StackFormation/releases/latest
or use this shortcut (requires `jq` to be installed)
```
wget $(curl -s https://api.github.com/repos/AOEpeople/StackFormation/releases/latest | jq -r '.assets[0].browser_download_url')
```

If you want to use stackformation globally:
```
mv stackformation.phar /usr/local/bin/stackformation
chmod +x /usr/local/bin/stackformation
```

### Quickstart

#### Setup

Create a `.env.default` file (and add it yo your gitignore: `echo .env.default >> .gitignore`)
```
AWS_ACCESS_KEY_ID=INSERT_YOUR_ACCESS_KEY_HERE
AWS_SECRET_ACCESS_KEY=INSERT_YOUR_SECRET_KEY_HERE
AWS_DEFAULT_REGION=INSERT_YOUR_DEFAULT_REGION_HERE
```

#### Your first blueprint

Create a `blueprints.yml` in your current directory:
```
blueprints:
  - stackname: my-stack
    template: my-stack.template
```

Create you CloudFormation template `my-stack.template`:
```
{
  "Resources": { 
    "MyResource1": { "Type": "AWS::CloudFormation::WaitConditionHandle" }
  }
}
```

Deploy your stack:
```
bin/stackformation.php deploy my-stack
```

#### Adding parameters

Add parameters in your `my-stack.template`:
```
{
  "Parameters: {
    "MyParameter1": { "Type": "String" }
  },
  "Resources": { 
    "MyResource1": { "Type": "AWS::CloudFormation::WaitConditionHandle" }
  }
}
```

...and configure that parameter in the `blueprint.yml` file:
```
blueprints:
  - stackname: my-stack
    template: my-stack.template
    parameters:
      MyParameter1: 'Hello World'
```

#### Referencing outputs/resources/parameters from other stacks

TODO

#### Inject user data

TODO

### Structuring your blueprints

Structure your blueprints including all templates and other files (e.g. userdata) in "modules".
StackFormation will load all stack.yml files from following locations:
- `blueprints/*/*/*/blueprints.yml`
- `blueprints/*/*/blueprints.yml`
- `blueprints/*/blueprints.yml`
- `blueprints/blueprints.yml`
- `blueprints.yml`

So it's suggested to create a directory structure like this one:
```
blueprints/
  stack1/
    userdata/
      provisioning.sh
    blueprints.yml
    my.template
  stack2/
    blueprints.yml
  ...
```

All `blueprints.yml` files will be merged together.

### Using stack policies

To prevent stack resources from being unintentionally updated or deleted during a stack update you can use [stack policies](http://docs.aws.amazon.com/AWSCloudFormation/latest/UserGuide/protect-stack-resources.html).
Stack policies apply only during stack updates and should be used only as a fail-safe mechanism to prevent accidental 
updates to certain stack resources.

It's suggested to create a stack_policies directory below the corresponding stack directory:
```
blueprints/
  stack1/
    stack_policies/
    blueprints.yml
    ...
  stack2/
    stack_policies/
    blueprints.yml
    ...
  ...
```

You have to tell StackFormation where it could find the stack policy. 

Example:
```
blueprints:
  - stackname: 'my-stack'
    template: 'templates/my-stack.template'
    stackPolicy: 'stack_policies/my-stack.json'
```

### Using composer

You can pull in StackFormation modules via composer. Look at the [cfn-lambdahelper](https://github.com/AOEpeople/cfn-lambdahelper) 
for an example. A custom composer installer (configured as `require` dependency) will take care of putting all the
module files in your `blueprints/` directory. This way you can have project specific and generic modules next to each other.

Please note that a "StackFormation module" will probably not come with a `blueprints.yml` file since this (and especially the 
stack parameter configuration) is project specific. 

You will need to create the stack configuration for the parts you want to use. A good place would be `blueprints/blueprints.yml` 
where you reference the imported module.

Example:
```
blueprints:
  - stackname: 'lambdacfnhelpers-stack'
    template: 'cfn-lambdahelper/lambda_cfn_helpers.template'
    Capabilities: CAPABILITY_IAM
```


### Parameter Values

- Output lookup: `{output:<stack>:<output>}` -> output value
- Resource lookup: `{resource:<stack>:<logicalResource>}` -> physical Id of that resource
- Parameter lookup: `{parameter:<stack>:<logicalResource>}` -> parameter value (note that some parameters will not be shown if they're 'no_echo')
- Environment variable lookup: `{env:<var>}` -> value of environment variable 'var'
- Environment variable lookup with default value fallback: `{env:<var>:<defaultValue>}` -> value of environment variable 'var' falling back to 'defaultValue' if env var is not set
- Stack/global variable lookup: `{var:<var>}` -> value variable 'var'
- Current timestamp: `{tstamp}` -> e.g. '1453151115'
- MD5 sum: `{md5:<filename>}` -> e.g. 'fdd747e9989440289dcfb476c75b4268'
- Clean: `{clean:2.1.7}` -> '217' (removes all characters that aren't allowed in stack names
- Switch profile: `[profile:<profileName>:...]` will switch to a different profile and evaluate the second parameter there. This is useful in cross account setups.

Output and resource lookup allow you to "connect" stacks to each other by wiring the output or resources created in
one stack to the input parameters needed in another stack that sits on top of the first one without manually 
managing the input values.

Example
```
blueprints:
  - stackname: stack1-db
    template: templates/stack1.template
    [...]
  - stackname: stack2-app
    template: templates/stack2.template
    parameters:
      build: 's3://{output:stack1:bucketName}/{env:BUILD}/build.tar.gz'
      db: '{output:stack1-db:DatabaseRds}'
```

Variables (global/local, nested into other placeholders)
```
vars:
  KeyPair: 'mykeypair'
    
blueprints:
  - stackname: mystack
    vars:
      ParentStack: 'MyParentStack'
    parameters:
      KeyPair: '{var:mykeypair}'
      Database: '{output:{var:ParentStack}:DatabaseRds}'
    [...]
```

Switch Profile Example (in this example an AMI is baked in a different account and shared with this account)
```
blueprints:
  - stackname: mystack
    parameters:
      BaseAmi: '[profile:myDevAccountProfile:{output:bakestack:BaseAmi}]'
```

### Conditional parameter values

You might end up deploying the same stacks to multiple environments or accounts. Instead of duplicating the blueprints (or using YAML reference) you'll probably
want to parameterize your blueprints like this 
```
blueprints:
  - stackname: 'app-{env:Environment}-build'
    template: 'build.template'
    parameters:
      KeyPair: 'MyKeyPair'
    [...]
```

... and then before deploying (locally or from your CI server) you'd set the env var first and then deploy:
```
export Environment=prod
bin/stackformation.php blueprint:deploy 'app-{env:Environment}-build'
```

But in many cases those stacks do have some minor differences in some of the parameters (e.g. different VPCs or KeyNames,...)
You could solve it like this with nested placeholders:
```
blueprints:
  - stackname: 'app-{env:Environment}-build'
    template: 'build.template'
    vars:
      prod-KeyName: MyProdKey
      stage-KeyName: MyStageKey
    parameters:
      KeyPair: '{var:{env:Environment}-KeyName}'
```

While this is perfectly possible this gets very confusing soon. Plus you'll have to mention every variation of the variable explicitely.
 
Instead you can use a conditional value:
```
blueprints:
  - stackname: 'app-{env:Environment}-build'
    template: 'build.template'
    parameters:
      KeyPair: 
        '{env:Environment}==prod': MyProdKey
        '{env:Environment}==stage': MyStageKey
        'default': MyDevKey
```

StackFormation will evaluate all keys from top to bottom and the first key that evaluates to true will be returned. 
Allowed conditions:
- 'A==B'
- 'A!=B'
- 'default' (will always evaluate to true. Make sure you put this at the very end since everything after this will be ignored).
Placeholders will be resolved before the conditions are evaluated.


### Wildcards

When referencing a stack in `{output:<stack>:<output>}`, `{resource:<stack>:<logicalResource>}`, or `{parameter:<stack>:<logicalResource>}` you can use a wildcard
to specify a stack. In this case StackFormation looks up all live stacks and finds a stack matching the pattern. If there's no stack or more than a single stack 
matching the pattern StackFormation will throw an exception.
This feature is helpful when you know there's always only a single stack of one type that has a placeholder in it's stackname:

Example: 
Stackname: `deployment-{env:BUILD_NUMBER}`
In blueprints.yml: 
```
blueprints:
  - stackname: mystack
    parameters:
      Elb: '{output:deployment-*:Elb}'
```

### Effective stackname

You can include environment variable in your stackname (which is very handy for automation via Jenkins).
In this case your effective stackname (e.g. `build-5`) will be different from the configured stackname (e.g. `build-{env:BUILD_NUMBER}`)

Example
```
blueprints:
  - stackname: 'build-{env:BUILD_NUMBER}'
    template: templates/deploy_build.template
```

### Relative file paths

Please note that all files paths in the `template` section of a `blueprints.yml` are relative to the current `blueprints.yml` file
and all files included via `Fn::FileContent`/ `Fn:FileContentTrimLines` or `Fn:FileContentMinify` are relative to the 
CloudFormation template file.

Example:
```
blueprints/
  stack1/
    userdata/
      provisioning.sh
    blueprints.yml
    my.template
```

blueprints.yml:
```
blueprints:
  - stackname: test
    template: my.template
```

my.template
```
{ [...]
  "Ec2Instance": {
    "Type": "AWS::AutoScaling::LaunchConfiguration",
    "Properties": {
      "UserData": {"Fn::Base64": {"Fn::FileContent": "userdata/provisioning.sh"}}
    }
  }
}
```

### Template merging

StackFormation allows you to configure more than one template:

```
blueprints:
  - stackname: iam
    template:
      - iam_role_jenkins.template
      - iam_user_inspector.template
    description: 'IAM users and roles'
```

The template files cannot have duplicate keys in any of the top level attributes. StackFormation will then merge them into 
a single CloudFormation template and deploy this one instead. This feature helps you to structure your template logically
without having to deploy and manage them separatly. Also with this you can choose which template to include in case you're
pulling in a StackFormation module like https://github.com/AOEpeople/cfn-lambdahelper.

You can always inspect the final merged and preprocessed template:
```
bin/stackformation.php stack:template iam
```

### Prefixed template merging

If you list your templates with attributes instead of a plain list, the attribute keys will be used to prefix every element of that template.
This way you can you the same template with different input parameters instead of duplicating resources. This comes in handy for VPC setups.

```
blueprints:
  - stackname: vpc-subnets
    template:
      ZoneA: az.template
      ZoneB: az.template
    parameters:
      ZoneAVpc: MyVPC
      ZoneAPublicSubnetCidrBlock: '10.0.0.0/24'
      ZoneAPrivateSubnetCidrBlock: '10.0.10.0/24'
      ZoneAAZ: 'eu-west-1a'
      ZoneBVpc: MyVPC
      ZoneBAPublicSubnetCidrBlock: '10.0.1.0/24'
      ZoneBPrivateSubnetCidrBlock: '10.0.11.0/24'
      ZoneBAZ: 'eu-west-1b'
      [...]
```

If you have a parameter that needs to be passed to all templates you can prefix it with '*' (make sure you add quotes around that key 
since JSON will consider this a reference instead) and StackFormation will replace '*' with each prefix used in the `template:` section.

```
blueprints:
  - stackname: vpc-subnets
    template:
      ZoneA: az.template
      ZoneB: az.template
    parameters:
      '*Vpc': MyVPC # Will automatically be expanded to 'ZoneAVpc: MyVPC' and 'ZoneBVpc: MyVPC'
      '*Igw': MyInternetGateway
      ZoneAPublicSubnetCidrBlock: '10.0.0.0/24'
      ZoneAPrivateSubnetCidrBlock: '10.0.10.0/24'
      ZoneAAZ: 'eu-west-1a'
      ZoneBVpc: MyVPC
      ZoneBAPublicSubnetCidrBlock: '10.0.1.0/24'
      ZoneBPrivateSubnetCidrBlock: '10.0.11.0/24'
      ZoneBAZ: 'eu-west-1b'
      [...]
```

### `before`

You can run shell commands before the CloudFormation is being deployed.
The commands will be executed in the directory where the blueprints.yml file lives. 

Example:
```
blueprints:
  - stackname: 'my-lambda-function'
    template: lambda.template
    Capabilities: CAPABILITY_IAM
    before:
    - cd function 
    - npm install aws-sdk
    - zip -r nat_gateway.zip nat_gateway.js node_modules/
    - aws s3 cp nat_gateway.zip s3://mybucket/lambda/nat_gateway.zip
```    

and you can even use placeholders:
```
blueprints:
  - stackname: 'my-lambda-function'
    template: lambda.template
    Capabilities: CAPABILITY_IAM
    vars:
      bucket: mybucket
      key: 'lambda/nat_gateway.zip'
    parameters:
      # these are the input parameters passed to the cfn template that match the upload location in the custom script below
      S3Bucket: '{var:bucket}'
      S3Key: '{var:key}'
    before:
    - cd function
    - npm install aws-sdk
    - zip -r nat_gateway.zip nat_gateway.js node_modules/
    - aws s3 cp nat_gateway.zip s3://{var:bucket}/{var:key}

```

### `after`

Similar to `before` scripts you can define scripts that are being executed after the stack has been deployed.
Please note this only work if you're 'observing' the deploying (no if you deployed with '--no-observe' or if you're 
stopping the process (e.g. CTRL+C) during the deployment.

The `after` configuration equals the `before` configuration with the addition that you have access to the status in the `${STATUS}` variable/
(Special status values in addition to the default ones like 'CREATE_COMPLETE',...
are 'NO_UPDATES_PERFORMED' and 'STACK_GONE')

Example
```
blueprints:
  - stackname: 'my-static-website'
    description: 'Static website hosted in S3'
    template: 'website.template'
    after:
      - 'if [[ $STATUS =~ ^(UPDATE|CREATE)_COMPLETE|NO_UPDATES_PERFORMED$ ]] ; then aws s3 sync --delete content/ s3://www-tst.aoeplay.net/; fi'
```

### `before` and `after`

`before` or `after` are being executed in the base directory of the current blueprint (that's the directory the blueprint's blueprint.yml file is located at).
But you can switch directories in your script. The `${CWD}` variable holds the current working directory (the project root) in case you want to switch to that.

When a profile is being used (even if the profile is loaded via the `profiles.yml` file) the `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` variables will be 
set in the script context, so you can safely call the aws cli tool in the same context the blueprint is being deployed.

In addition to that `${BLUEPRINT}` will hold the current blueprint's name and `${STACKNAME}` the current resulting stack name 
Also `${STATUS}` will hold the last status of the stack that has just been deployed (`after` scripts only).

You can separate the script lines in an array (that will then be concatenated with `\n` before executing:
```
blueprints:
  - stackname: 'my-static-website'
    [...]
    after:
      - 'echo "Line 1"'
      - 'echo "Line 2"'
```

or you can use the YAML multiline notation:
```
blueprints:
  - stackname: 'my-static-website'
    [...]
    after: |
      echo "Line 1"
      echo "Line 2"
```
### AWS SDK

StackFormation uses the AWS SDK for PHP. You should configure your keys in env vars:
```
export AWS_ACCESS_KEY_ID=INSERT_YOUR_ACCESS_KEY
export AWS_SECRET_ACCESS_KEY=INSERT_YOUR_PRIVATE_KEY
export AWS_DEFAULT_REGION=eu-west-1
```

### Function `Fn::FileContent`

Before uploading CloudFormation template to the API there's some pre-processing going on:
I've introduced a new function "FileContent" that accepts a path to a file. This file will be read, converted into JSON (using `Fn::Join`).
The path is relative to the path of the current CloudFormation template file.

Usage Example:
```
    [...]
    "UserData": {"Fn::Base64": {"Fn::FileContent":"../scripts/setup.sh"}},
    [...]
```

### Functions `Fn::FileContentTrimLines` and `Fn::FileContentMinify`

These functions are similar to `Fn::FileContent` but additional they trim whitespace or minify the code.
This comes in handy when deploying Lambda function where the content can't be larger than 2048kb if you 
want to directly embed the source code via CloudFormation (instead of deploying a zip file).

### Function `Fn::FileContentUnpretty` 

This function is the same as `Fn::FileContent` expect it will return the resulting JSON without formatting it, 
which will reduce the file size significantly due to the missing whitespace in the JSON structure (not inside the file content!)
This is useful if you're seeing the "...at 'templateBody' failed to satisfy constraint: Member must have length less than or equal to 51200" error message.

### Function `Fn::Split`

Sometime you have a dynamic number of array items. `Fn::Split` allows you to configure them as a single string and transforms them into an array:

```
"Aliases": { "Fn::Split": [",", "www.example.com,cdn.example.com"]}
```
results in:
```
"Aliases": ["www.example.com","cdn.example.com"]
```

### Inject Parameters

The scripts (included via `Fn::FileContent`) may contain references to other CloudFormation resources or parameters. 
Part of the pre-processing is to convert snippets like `{Ref:MagentoWaitConditionHandle}` or `{Ref:AWS::Region}` (note the missing quotes!)
into correct JSON snippets and embed them into the `Fn::Join` array.

Usage Example:
```
#!/usr/bin/env bash
/usr/local/bin/cfn-signal --exit-code $? '{Ref:WaitConditionHandle}'
```
will be converted to:
```
{"Fn::Join": ["", [
"#!\/usr\/bin\/env bash\n",
"\/usr\/local\/bin\/cfn-signal --exit-code $? '", {"Ref": "WaitConditionHandle"}, "'"
]]}
```

### Include file content

You can include content from a different file into a script. Use this is you have duplicate code that you need to embed into multiple 
resource's UserData:

Example:
```
#!/usr/bin/env bash

###INCLUDE:../generic/includes/base.sh

[...]
```

### Inject raw JSON

```
###JSON###
{ "hello": "world" }
######
```

### Stackname filter

You can configure a regular expression in the `STACKFORMATION_NAME_FILTER` environment variable (e.g. via `.env.default`) which
will filter all your stack lists to the stacks matching this pattern. This is useful if you have a naming convention in place and
you don't want to see other team's stacks in your list.

Example:
```
STACKFORMATION_NAME_FILTER=/^myproject-(a|b)-/
```

### Comments

You can add comments to your JSON file. Due to a current bug you can't have double quotes in your comment block.

Example:
```
{"IpProtocol": "tcp", "FromPort": "80", "ToPort": "80", "CidrIp": "1.2.3.4/32"}, /* AOE WI Office */
{"IpProtocol": "tcp", "FromPort": "80", "ToPort": "80", "CidrIp": "5.6.7.8/32"}, /* Fabrizio Home Office */
```

### Port

`"Port":"..."` will automatically expanded to `"FromPort": "...", "ToPort": "..."`. So if you're specifying a single
port instead of a range of ports you can reduce the redundancy:

Example:
```
{"IpProtocol": "tcp", "Port": "80", "CidrIp": "1.2.3.4/32"}, 
/* expands to: */
{"IpProtocol": "tcp", "FromPort": "80", "ToPort": "80", "CidrIp": "1.2.3.4/32"},
```

### Expand strings with {Ref:...}

Tired of concatenating strings with `{"Fn::Join": ["", [` manually? Just add the references in a string and StackFormation will
expand this for you:

Example:
```
"Key": "Name", "Value": "magento-{Ref:Environment}-{Ref:Build}-instance"
/* will be replaced with: */
"Key": "Name", "Value": {"Fn::Join": ["", ["magento-", {"Ref":"Environment"}, "-", {"Ref":"Build"}, "-instance"]]}
```

### Misc

Use the `jq` tool to create a simple list of all parameters (almost) ready to paste it in the blueprints.yml

```
cat my.template | jq '.Parameters | keys' | sed 's/",/: \'\'/g' | sed 's/"//g'
```
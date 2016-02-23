# StackFormation

**Lightweight AWS CloudFormation Stack, Template and Parameter Manager and Preprocessor**

Author: 
 - [Fabrizio Branca](https://twitter.com/fbrnc)

Contributors:
 - [Lee Saferite](https://github.com/LeeSaferite)
 - [Julian Kleinhans](https://github.com/kj187)

### Quickstart

Create a `stacks.yml` in your current directory

```
stacks:
  - stackname: my-stack
    template: templates/my-stack.template
    parameters:
      foo: 42
      bar: 43
  - stackname: my-second-stack
    template: templates/my-stack.template
    parameters:
      foo: 42
      bar: 43
```

### Structuring your stacks

Structure your stacks including all templates and other files (e.g. userdata) in "modules".
StackFormation will load all stack.yml files from following locations:
- `stacks/*/*/stacks.yml`
- `stacks/*/stacks.yml`
- `stacks/stacks.yml`
- `stacks.yml`

So it's suggested to create a directory structure like this one:
```
stacks/
  stack1/
    userdata/
      provisioning.sh
    stacks.yml
    my.template
  stack2/
    stacks.yml
  ...
```

All `stacks.yml` files will be merged together.

### Using stack policies

To prevent stack resources from being unintentionally updated or deleted during a stack update you can use [stack policies](http://docs.aws.amazon.com/AWSCloudFormation/latest/UserGuide/protect-stack-resources.html).
Stack policies apply only during stack updates and should be used only as a fail-safe mechanism to prevent accidental 
updates to certain stack resources.

It's suggested to create a stack_policies directory below the corresponding stack directory:
```
stacks/
  stack1/
    stack_policies/
    stacks.yml
    ...
  stack2/
    stack_policies/
    stacks.yml
    ...
  ...
```

You have to tell StackFormation where it could find the stack policy. 

Example:
```
stacks:
  - stackname: 'my-stack'
    template: 'templates/my-stack.template'
    stackPolicy: 'stack_policies/my-stack.json'
```

### Using composer

You can pull in StackFormation modules via composer. Look at the [cfn-lambdahelper](https://github.com/AOEpeople/cfn-lambdahelper) 
for an example. A custom composer installer (configured as `require` dependency) will take care of putting all the
module files in your `stacks/` directory. This way you can have project specific and generic modules next to each other.

Please note that a "StackFormation module" will probably not come with a `stacks.yml` file since this (and especially the 
stack parameter configuration) is project specific. 

You will need to create the stack configuration for the parts you want to use. A good place would be `stacks/stacks.yml` 
where you reference the imported module.

Example:
```
stacks:
  - stackname: 'lambdacfnhelpers-stack'
    template: 'cfn-lambdahelper/lambda_cfn_helpers.template'
    Capabilities: CAPABILITY_IAM
```


### Parameter Values

- Empty value: keep previous value (when updating existing stack)
- Output lookup: `{output:<stack>:<output>}` -> output value
- Resource lookup: `{resource:<stack>:<logicalResource>}` -> physical Id of that resource
- Parameter lookup: `{parameter:<stack>:<logicalResource>}` -> parameter value (note that some parameters will not be shown if they're 'no_echo')
- Environment variable lookup: `{env:<var>}` -> value of environment variable 'var'
- Environment variable lookup with default value fallback: `{env:<var>:<defaultValue>}` -> value of environment variable 'var' falling back to 'defaultValue' if env var is not set
- Stack/global variable lookup: `{var:<var>}` -> value variable 'var'
- Current timestamp: `{tstamp}` -> e.g. '1453151115'

Output and resource lookup allow you to "connect" stacks to each other by wiring the output or resources created in
one stacks to the input paramaters needed in another stack that sits on top of the first one without manually 
managing the input values.

Example
```
stacks:
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
    
stacks:
  - stackname: mystack
    vars:
      ParentStack: 'MyParentStack'
    parameters:
      KeyPair: '{var:mykeypair}'
      Database: '{output:{var:ParentStack}:DatabaseRds}'
    [...]
```

### Wildcards

When referencing a stack in `{output:<stack>:<output>}`, `{resource:<stack>:<logicalResource>}`, or `{parameter:<stack>:<logicalResource>}` you can use a wildcard
to specify a stack. In this case StackFormation looks up all live stacks and finds a stack matching the pattern. If there's no stack or more than a single stack 
matching the pattern StackFormation will throw an exception.
This feature is helpful when you know there's always only a single stack of one type that has a placeholder in it's stackname:

Example: 
Stackname: `deployment-{env:BUILD_NUMBER}`
In stacks.yml: 
```
stacks:
  - stackname: mystack
    parameters:
      Elb: '{output:deployment-*:Elb}'
```

### Effective stackname

You can include environment variable in your stackname (which is very handy for automation via Jenkins).
In this case your effective stackname (e.g. `build-5`) will be different from the configured stackname (e.g. `build-{env:BUILD_NUMBER}`)

Example
```
stacks:
  - stackname: 'build-{env:BUILD_NUMBER}'
    template: templates/deploy_build.template
```

### Relative file paths

Please note that all files paths in the `template` section of a `stacks.yml` are relative to the current `stacks.yml` file
and all files included via `Fn::FileContent`/ `Fn:FileContentTrimLines` or `Fn:FileContentMinify` are relative to the 
CloudFormation template file.

Example:
```
stacks/
  stack1/
    userdata/
      provisioning.sh
    stacks.yml
    my.template
```

stacks.yml:
```
stacks:
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
stacks:
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
stacks:
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
stacks:
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
The commands will be executed in the directory where the stacks.yml file lives. 

Example:
```
stacks:
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
stacks:
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

### Functions `Fn:FileContentTrimLines` and `Fn:FileContentMinify`

These functions are similar to `Fn::FileContent` but additional they trim whitespace or minify the code.
This comes in handy when deploying Lambda function where the content can't be larger than 2048kb if you 
want to directly embed the source code via CloudFormation (instead of deploying a zip file).

### Function `Fn:FileContentUnpretty` 

This function is the same as `Fn::FileContent` expect it will return the resulting JSON without formatting it, 
which will reduce the file size significantly due to the missing whitespace in the JSON structure (not inside the file content!)
This is useful if you're seeing the "...at 'templateBody' failed to satisfy constraint: Member must have length less than or equal to 51200" error message.

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

### PHP 

Deploy a stack programmatically
```
require_once __DIR__ . '/vendor/autoload.php';

$stackmanager = new \StackFormation\StackManager();
$stackmanager->deployStack('my-stack');
```

### Misc

Use the `jq` tool to create a simple list of all parameters (almost) ready to paste it in the stacks.yml

```
cat my.template | jq '.Parameters | keys' | sed 's/",/: \'\'/g' | sed 's/"//g'
```
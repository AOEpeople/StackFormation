# StackFormation

**Lightweight AWS CloudFormation Stack, Template and Parameter Manager and Preprocessor**

Author: 
 - [Fabrizio Branca](https://twitter.com/fbrnc)

Contributors:
 - [Lee Saferite](https://github.com/LeeSaferite)

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
- Output lookup: `output:<stack>:<output>` -> output value
- Resource lookup: `resource:<stack>:<logicalResource>` -> physical Id of that resource
- Parameter lookup: `parameter:<stack>:<logicalResource>` -> parameter value (note that some parameters will not be shown if they're 'no_echo')
- Environment variable lookup: `env:<var>` -> value of environment variable 'var'
- Stack/global variable lookup: `var:<var>` -> value variable 'var'

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

### Commands

- stack:list
- stack:deploy
- stack:delete
- stack:observe

### PHP 

Deploy a stack programmatically
```
require_once __DIR__ . '/vendor/autoload.php';

$stackmanager = new \StackFormation\StackManager();
$stackmanager->deployStack('my-stack');
```

# StackFormation

**Lightweight AWS CloudFormation Stack, Template and Parameter Manager and Preprocessor**

Author: 
 - [Fabrizio Branca](https://twitter.com/fbrnc)

Contributors:
 - [Lee Saferite](https://github.com/LeeSaferite)

### Stack Configuration

Create a `stacks.yml` in your current directory

```
stacks:
    my-stack:
        template: templates/my-stack.template
        parameters:
            foo: 42
            bar: 43
    my-second-stack:
        template: templates/my-stack.template
        parameters:
            foo: 42
            bar: 43
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
    stack1-db:
        template: templates/stack1.template
        [...]
    stack2-app:
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
    mystack:
        vars:
            ParentStack: 'MyParentStack'
        parameters:
            KeyPair: '{var:mykeypair}'
            Database: '{output:{var:ParentStack}:DatabaseRds}'
        [...]
```

### Effective stackname

You can provide an effective stackname that can be different from the key in the `stacks.yml` file. You can use this to 
include an environment variable in the stackname.

Example
```
stacks:
    build-x:
        stackname: 'build-{env:BUILD_NUMBER}'
        template: templates/deploy_build.template
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

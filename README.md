# StackFormation

## LightWeight AWS CloudFormation Stack, Template and Parameter Manager and Preprocessor

Author: [Fabrizio Branca](https://twitter.com/fbrnc)

### Stack Configuration

Create a `stacks.yml` in your current directory

```
stacks:
    my-stack:
        template: templates/my-stack.template
        parameters:
            foo: 42,
            bar: 43
    my-second-stack:
        template: templates/my-stack.template
        parameters:
            foo: 42,
            bar: 43
```

### Parameter Values

- Empty value: keep previous value (when updating existing stack)
- Output lookup: `output:<stack>:<output>` -> output value
- Resource lookop: `resource:<stack>:<logicalResource>` -> physical Id of that resource

### AWS SDK

StackFormation uses the AWS SDK for PHP. You should configure your keys in env vars:
```
export AWS_ACCESS_KEY_ID=INSERT_YOUR_ACCESS_KEY
export AWS_SECRET_ACCESS_KEY=INSERT_YOUR_PRIVATE_KEY
```

### `Fn::FileContent`

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

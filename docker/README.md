
# AWS StackFormation with Docker
___
**Lightweight AWS CloudFormation Stack, Template and Parameter Manager and Preprocessor**
**Url:** https://github.com/AOEpeople/StackFormation
![alt text][logo]

# Available tags

- `4.3.6`, `latest` (includes PHP 7.0)
- `4.3.6-golang`, `latest-golang` (includes PHP 7.0, Golang ?.?)

### Golang version
The Golang version includes the slim version as basis and on top it supports go. Golang could be used for AWS Lambda for instance (see https://github.com/kj187/aws_stackformation_templates/tree/master/blueprints/lambda/golang)

## Usage

```
$ docker run --rm -it -v $(pwd):/app -w /app stackformation:latest bash
```

Or if you use lambda with golang for instance
```
$ docker run --rm -it -v $(pwd):/app -w /app stackformation:latest-golang bash
```

## Build a new image version

```
$ git clone git@github.com:AOEpeople/StackFormation.git
$ ./StackFormation/docker/build.sh STACKFORMATION_GITHUB_RELEASE_VERSION
```

STACKFORMATION_GITHUB_RELEASE_VERSION
Github release version which includes an automated generated phar file

Push to docker hub

```
$ ./StackFormation/docker/push.sh STACKFORMATION_GITHUB_RELEASE_VERSION
```

[logo]: https://raw.githubusercontent.com/AOEpeople/StackFormation/master/doc/Images/stackformation_200px.png "StackFormation"

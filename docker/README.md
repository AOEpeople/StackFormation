
# AWS StackFormation with Docker
**Lightweight AWS CloudFormation Stack, Template and Parameter Manager and Preprocessor**
- **Github:** https://github.com/AOEpeople/StackFormation
- **Documentation:** http://stackformation.readthedocs.io/en/latest/
- **DockerHub:** https://hub.docker.com/r/kj187/stackformation/


# Available tags

- `4.3.6`, `latest` (includes PHP 7.0)
- `4.3.6-golang`, `latest-golang` (includes PHP 7.0, Golang 1.6.2 linux/amd64)

### Golang version
The Golang version includes the slim version as basis and on top it supports golang. 
Golang could be used for AWS Lambda for instance 
(see https://github.com/kj187/aws_stackformation_templates/tree/master/blueprints/lambda/golang)

## Usage

```
$ docker run --rm -it -v $(pwd):/app -w /app kj187/stackformation:latest bash
$ stackformation <COMMAND>
```

Or if you want to use lambda with golang
```
$ docker run --rm -it -v $(pwd):/app -w /app kj187/stackformation:latest-golang bash
$ stackformation <COMMAND>
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

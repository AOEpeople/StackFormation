#!/usr/bin/env bash

. $PWD/_base.sh

push() {
    echo ">>>> Push $1:$2 to dockerhub"
    docker push $1:$2
}

echo '>> Start docker hub push process'
docker login

cd $DIR/slim
push kj187/stackformation $1
push kj187/stackformation latest

cd $DIR/golang
push kj187/stackformation $1-golang
push kj187/stackformation latest-golang

echo '>> Pushing to docker hub finished'

#!/usr/bin/env bash

. $PWD/_base.sh

build() {
    docker_repo=$1
    sf_release=$2
    img_tag=$3
    img_latest_tag=$4

    echo ">>>> Build $docker_repo:$img_tag and $docker_repo:$img_latest_tag"
    echo ">>>>>> docker build -t $docker_repo:$img_tag -t $docker_repo:$img_latest_tag --build-arg STACKFORMATION_VERSION=$sf_release ."
    docker build -t $docker_repo:$img_tag -t $docker_repo:$img_latest_tag --build-arg STACKFORMATION_VERSION=$sf_release . || error_exit "Cant build image"
}

echo '>> Start building process'

cd $DIR/slim
echo ">>> Building Slim version"
build kj187/stackformation $1 $1 latest

cd $DIR/golang
echo ">>> Building Golang version"
build kj187/stackformation $1 $1-golang latest-golang

echo '>> Building finished'

clean

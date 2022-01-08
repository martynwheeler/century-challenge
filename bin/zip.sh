#!/bin/bash

file=$(pwd)/century.tgz

cd $HOME/htdocs/century

tar -zcvf $file .env .env.local composer.json ./config ./public ./src ./templates ./vendor

exit

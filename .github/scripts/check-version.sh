#!/bin/bash

LAST_TAG=`git describe --tags --abbrev=0`
IFS=. components=(${LAST_TAG##*-})
exit `php -r "require_once __DIR__.'/vendor/autoload.php'; use AutoMapper\AutoMapper; echo (AutoMapper::MAJOR_VERSION >= intval(\"${components[0]}\") && AutoMapper::MINOR_VERSION >= intval(\"${components[1]}\")) ? 0 : 1;"`

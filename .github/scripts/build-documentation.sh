#!/bin/bash

# preparing fake remote
mkdir .build
git config user.name ci-bot
git config user.email ci-bot@example.org
git remote add gh-pages ./.build
cd ./.build
git init
git checkout -b gh-pages
git config receive.denyCurrentBranch ignore
cd -

# build documentation for main branch
poetry run mike deploy --push --remote gh-pages dev

# build documentation for 8.x
git checkout tags/8.2.2
poetry run mike deploy --push --remote gh-pages --update-aliases 8.2.2 latest
poetry run mike set-default --push latest

# build documentation for 9.x
git checkout tags/9.0.0-beta.2
poetry run mike deploy --push --remote gh-pages 9.0.0-beta.2

# clean fake remote
cd ./.build
git reset --hard gh-pages
cd -

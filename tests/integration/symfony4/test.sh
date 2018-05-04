#!/bin/bash

set -e

if [ -z "${CIRCLE_PR_NUMBER}" ]; then
    BRANCH="master"
    REPO="https://github.com/census-instrumentation/opencensus-php"
else
    PR_INFO=$(curl "https://api.github.com/repos/${CIRCLE_PROJECT_USERNAME}/${CIRCLE_PROJECT_REPONAME}/pulls/${CIRCLE_PR_NUMBER}")
    BRANCH=$(echo $PR_INFO | jq -r .head.ref)
    REPO=$(echo $PR_INFO | jq -r .head.repo.html_url)
fi

pushd $(dirname ${BASH_SOURCE[0]})

composer create-project --prefer-dist symfony/skeleton symfony ^4.0
cp -r src tests phpunit.xml.dist symfony/

pushd symfony

composer config repositories.opencensus git ${REPO}
composer require opencensus/opencensus:dev-${BRANCH} doctrine
composer require --dev phpunit/phpunit:^7.0 guzzlehttp/guzzle:~6.0

bin/console doctrine:migrations:migrate -n
vendor/bin/phpunit

popd
popd

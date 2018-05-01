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

curl -L https://wordpress.org/latest.tar.gz | tar zxf -
sed -i "s|dev-master|dev-${BRANCH}|" composer.json
sed -i "s|https://github.com/census-instrumentation/opencensus-php|${REPO}|" composer.json
composer install -n --prefer-dist
cp wp-config.php wordpress
vendor/bin/wp core install  --admin_user=admin \
                            --admin_password=password \
                            --allow-root \
                            --path=wordpress/ \
                            --url=http://wordpress/ \
                            --admin_email=admin@opencensus.io \
                            --title="OpenCensus Test"

vendor/bin/phpunit

popd

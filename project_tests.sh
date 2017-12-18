#!/usr/bin/env bash
set -e

env="${ENVIRONMENT_NAME:-ci}"

rm -f build/*.xml
proofreader src/ tests/ web/
vendor/bin/phpunit --log-junit build/phpunit.xml

echo "Creating, deleting an index"
bin/ci-lifecycle "$env"

echo "Importing api-dummy"
bin/ci-import "$env"

echo "Reindexing api-dummy"
bin/ci-reindex "$env"

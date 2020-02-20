#!/usr/bin/env bash
set -e

rm -f build/*.xml
proofreader src/ tests/ web/
vendor/bin/phpunit --log-junit build/phpunit.xml

echo "Creating, deleting an index"
bin/ci-lifecycle

echo "Importing api-dummy"
bin/ci-import

echo "Reindexing api-dummy"
bin/ci-reindex

echo "Reindexing RDS articles"
bin/reindex-rds

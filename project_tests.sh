#!/usr/bin/env bash
set -e

rm -f build/*.xml
vendor/bin/phpcs --standard=phpcs.xml.dist --warning-severity=0 -p src/ tests/ web/
vendor/bin/phpunit --log-junit build/phpunit.xml

echo "Creating, deleting an index"
bin/ci-lifecycle

echo "Importing api-dummy"
bin/ci-import

echo "Reindexing api-dummy"
bin/ci-reindex

echo "Reindexing RDS articles"
bin/ci-reindex-rds

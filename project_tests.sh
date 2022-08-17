#!/usr/bin/env bash
set -e

# lsh@2022-08-17: some change has maxed out the default (soft) file limit of 1024.
# this is a kludge and the real culprit should be found and fixed.
# - https://github.com/elifesciences/search/pull/382
ulimit -S -n 2048

function cleanup {
    ulimit -S n 1024
}
trap cleanup EXIT

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

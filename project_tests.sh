#!/usr/bin/env bash
set -e

# lsh@2022-08-17: some change has maxed out the default (soft) file limit of 1024.
# this is a kludge and the real culprit should be found and fixed.
# - https://github.com/elifesciences/search/pull/382
ulimit -S -n 2048

function cleanup {
    rc=$?
    ulimit -S -n 1024
    exit $rc
}
trap cleanup EXIT

vendor/bin/phpcs --standard=phpcs.xml.dist --warning-severity=0 -p src/ tests/ web/
vendor/bin/composer-dependency-analyser
vendor/bin/phpstan analyse
vendor/bin/phpunit

echo "Creating, deleting an index"
bin/ci-lifecycle

# need to be run before running the queue:watch
echo "Reindexing reviewed-preprint articles with dateFrom parameter"
bin/ci-reindex-reviewed-preprints-dateFrom

echo "Importing api-dummy"
bin/ci-import

echo "Reindexing api-dummy"
timeout 60 bin/ci-reindex

echo "Importing ERA (formerly known as RDS) articles"
bin/ci-import-era

echo "Reindexing reviewed-preprint articles"
bin/ci-reindex-reviewed-preprints

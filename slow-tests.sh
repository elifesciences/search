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

echo "Creating, deleting an index"
bin/ci-lifecycle

# need to be run before running the queue:watch
echo "Reindexing reviewed-preprint articles with dateFrom parameter"
bin/ci-reindex-reviewed-preprints-dateFrom

echo "Importing api-dummy"
bin/ci-import

echo "Reindexing api-dummy"
bin/ci-reindex

echo "Reindexing RDS articles"
bin/ci-reindex-rds

echo "Reindexing reviewed-preprint articles"
bin/ci-reindex-reviewed-preprints

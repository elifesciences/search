#!/usr/bin/env bash
set -e

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

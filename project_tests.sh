#!/usr/bin/env bash
set -e

env="${ENVIRONMENT_NAME:-ci}"

rm -f build/*.xml
proofreader src/ tests/ web/
vendor/bin/phpunit --log-junit build/phpunit.xml
bin/ci-import "$env"

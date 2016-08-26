#!/usr/bin/env bash
set -e

rm -f build/*.xml
proofreader src/ web/
vendor/bin/phpunit --log-junit build/phpunit.xml

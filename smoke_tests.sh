#!/usr/bin/env bash
set -ex

attempts=3
delay=10

retry ./smoke_tests_app.sh $attempts $delay
retry ./smoke_tests_elasticsearch.sh $attempts $delay

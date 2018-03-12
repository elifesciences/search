#!/usr/bin/env bash
set -ex

[ $(curl --write-out %{http_code} --silent --output /dev/null localhost/ping) == 200 ]
retry ./smoke_tests_elasticsearch.sh 3

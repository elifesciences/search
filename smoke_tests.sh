#!/usr/bin/env bash
set -ex

attempts=3
delay=10

# temporary, to get 18.04 formula branch passing. remove once upgraded. this delay is incorporated there
sleep 20 

retry ./smoke_tests_app.sh $attempts $delay
retry ./smoke_tests_elasticsearch.sh $attempts $delay

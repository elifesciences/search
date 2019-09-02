#!/usr/bin/env bash
set -ex

hostname | grep 'fresh-follower-node' && {
    echo "This is a follower node without an elasticsearch leader. Smoke tests cannot be run."
    exit 0
}

attempts=3
delay=10

retry ./smoke_tests_app.sh $attempts $delay
retry ./smoke_tests_elasticsearch.sh $attempts $delay

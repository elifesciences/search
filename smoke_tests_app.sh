#!/bin/bash
set -ex

test1=$(curl --write-out '%{http_code}' --silent --output /dev/null localhost/ping)
[[ "$test1" == "200" ]]

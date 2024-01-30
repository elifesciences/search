#!/bin/bash
set -ex

test1=$(curl --write-out '%{http_code}' --silent --output /dev/null localhost/search?for=notexistentkeywordthatwouldmakeanemptyresult)
[[ "$test1" == "200" ]]

# output should have a "types" field
test2=$(curl --silent localhost/search?for=something | jq 'has("types")')
[[ "$test2" == "true" ]]

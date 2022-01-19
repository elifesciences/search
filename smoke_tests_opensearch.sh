#!/usr/bin/env bash
set -ex

[ $(curl --write-out %{http_code} --silent --output /dev/null localhost/search?for=notexistentkeywordthatwouldmakeanemptyresult) == 200 ]
# output should have a "types" field
[ $(curl --silent localhost/search?for=something | jq 'has("types")') == "true" ]

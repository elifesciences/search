#!/bin/bash
# creates a repository in Elasticsearch to restore a snapshot
# decompresses given path to snapshot file
# closes all open indices
# restores snapshot
# opens all closed indices

set -eu

snapshot="$1" # 'backup'
snapshot_path="$2" # '/tmp/backup.tar.gz'

function errcho {
    echo "$@" 1>&2;
}

if [ ! -f "$snapshot_path" ]; then
    errcho "file not found: $snapshot_path"
    exit 1
fi

repo="repo"
repo_path="/var/lib/elasticsearch/$repo"
elasticsearch="127.0.0.1:9200"

function curlit {
    url=$1
    status_code=$(curl --silent --output /dev/stderr --write-out "%{http_code}" "$@")
    # https://superuser.com/questions/590099/can-i-make-curl-fail-with-an-exitcode-different-than-0-if-the-http-status-code-i
    errcho "elasticsearch response status code: $status_code"
    if test $status_code -ne 200; then
        exit $status_code
    fi
}

function create_repo {
    errcho "creating repo '$repo' at '$repo_path' for snapshots (idempotent)"
    curlit -XPUT "$elasticsearch/_snapshot/$repo" -d '{"type": "fs", "settings": {"location": "'$repo_path'"}}'
}

function decompress_snapshot {
    # assumes snapshot was compressed with the create-snapshot.sh script
    # in this case there is a root directory called 'repo'
    errcho "decompressing snapshot at '$snapshot_path'"
    (
        cd "$repo_path/.."
        tar xzf "$snapshot_path"
    )
}

function close_indices {
    errcho "closing all indices"
    curlit -XPOST "$elasticsearch/_all/_close"
}

function restore_snapshot {
    errcho "restoring snapshot '$snapshot'"
    curlit -XPOST "$elasticsearch/_snapshot/$repo/$snapshot/_restore?wait_for_completion=true"
}

function open_indices {
    errcho "opening all indices"
    curlit -XPOST "$elasticsearch/_all/_open"
}

create_repo
decompress_snapshot
# close_indices
restore_snapshot
# open_indices

curl localhost/search | jq .

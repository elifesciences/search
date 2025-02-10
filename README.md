# eLife Search

This project is using opensearch to index content of the eLife API and provide a full-text search for Journal.

## Prerequisites for local development

1. Clone the project `git clone https://github.com/elifesciences/search.git`

### Starting the app

To bring up all services, run:
```shell
make dev
```

### Importing and using search

The `bin/console queue:import` command imports items from API (in dev this is the api-dummy instance running in docker compose) and adds them into the queue. Run this in the docker environment with:

```shell
docker compose exec app bin/console queue:import all
```

> **Note**: `all` here means all types of search content. Other possible values can be found in src/Search/Queue/Command/ImportCommand.php

If you are running the queue watcher, you should now see the results by accessing the search API on http://localhost:8888/search

If you are not running the watcher, inspect the queue count via

```shell
docker compose exec app bin/console queue:count
```

### Testing

To run the tests:
```shell
make test
```

To run additional fast checks (e.g. linting) as well as the tests:
```shell
make check
```

To run all the project tests (inc above tests and integration tests)
```shell
docker compose down queue-watcher
docker compose exec app bash project_tests.sh
```
NOTE: these integration tests require the queue watcher  to not be running so the tests can control when items are consumed. This is why we make sure to stop watcher services.

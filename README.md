# eLife Search

This project is using opensearch to index content of the eLife API and provide a full-text search for Journal.

## Local development

1. Clone the project `git clone https://github.com/elifesciences/search.git`
2. To bring up all services, run: `make dev`

You should now be able to access the search API on http://localhost:8888/search

Common tasks include:

- To run fast checks (e.g. linting) and fast tests: `make check`
- To run all PHPUnit tests, including slow ones: `make test`
- To replicate CI checks, including integration tests: `make all-checks`
- To run a production rather than a development image: `make prod`
- To empty the database and all state: `make clean`

See the Makefile for further targets.

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

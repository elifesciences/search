# eLife Search

This project is using opensearch to index content of the eLife API and provide a full-text search for Journal.

## Local development

1. Clone the project `git clone https://github.com/elifesciences/search.git`
2. To bring up all services, run: `make dev`

You should now be able to access the search API on http://localhost:8888/search

Common tasks include:

- To run a production rather than a development image: `make prod`
- To empty the database and all state: `make clean`

See the Makefile for further targets.

### Importing and using search

`make import-entity` will enqueue _all_ items from a local instance of the api-dummy.

An optional make variable `ENTITY` can be passed in. Possible values for `ENTITY` can be found in [src/Search/Queue/Command/ImportCommand.php](src/Search/Queue/Command/ImportCommand.php).

To monitor the queue count:

```shell
docker compose exec app bin/console queue:count
```

Reload http://localhost:8888/search to see items being served by the search API.

### Testing
To run all PHPUnit tests, including slow ones:
```
make test
```
To run fast checks (e.g. linting) and fast tests: 
```
make check
```

To replicate CI checks, including integration tests:
```
make all-checks
```

To access all documents in the local database under the index named `elife_test`:
```
curl -v 'localhost:9200/elife_test/_search?pretty=true&q=*:*' | jq .
```

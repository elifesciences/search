# eLife Search

This project is using elasticsearch to index content of the eLife articles and provide a full-text search for Journal.

To reliably recreate any issue experienced in CI or Prod you should continue to use [builder](https://github.com/elifesciences/builder).

## Docker for local development

Important: Keep in mind that docker is just used to improve the developer experience.

1. Clone the project `git clone https://github.com/elifesciences/search.git`
2. Rename `/dev/config.php.dist` on local to `/dev/config.php`
3. Run `docker-compose -f dev/docker-compose.yaml up --build`

### Setup

Follow the steps below to set up the project:

1. Go to `app` container:

```bash 
$ docker-compose -f dev/docker-compose.yaml exec app /bin/bash
```

2. Run following commands in order:

```bash 
$ bin/console queue:create && ./project_tests.sh
```

Now you can access the search API on http://localhost:8888/search

### Testing

To run the tests: `docker-compose -f dev/docker-compose.yaml exec app vendor/bin/phpunit`

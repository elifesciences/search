# eLife Search

This project is using elasticsearch to index content of the eLife articles and provide a full-text search for Journal.

To reliably recreate any issue experienced in CI or Prod you should continue to use [builder](https://github.com/elifesciences/builder).

## Docker for local development

Important: Keep in mind that docker is just used to improve the developer experience.

1. Clone the project `git clone https://github.com/elifesciences/search.git`
2. Rename `config.php.dist` on local to `/dev/config.php`
3. Run `docker-compose up --build`

### Running search

    ```

1. The `bin/console queue:import` command imports items from API and adds them into the queue:
    ```bash
    $ docker-compose exec app bin/console queue:import all # other possible values can be found in src/Search/Gearman/Command/ImportCommand.php
    ```

2. After adding items to the queue, running the following command will index them in Elasticsearch:
    ```bash
    $ docker-compose exec app bin/console queue:watch
    ```

Now you can access the search API on http://localhost:8888/search

### Testing

To run the tests: `docker-compose exec app vendor/bin/phpunit`

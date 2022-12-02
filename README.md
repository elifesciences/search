eLife Search
=============

This project is using elasticsearch to index content of the eLife articles and provide a full-text search for Journal.

Important: Keep in mind that docker is just used to improve the developer experience. To reliably recreate any issue experienced in CI or Prod you should continue to use [builder](https://github.com/elifesciences/builder).

Installation
------------

1. Clone the project `git clone https://github.com/elifesciences/search.git`
2. Rename `config.php.dist` on local to `config.php` because config.php is in .gitignore.
3. Run `docker-compose up --build`

Testing
-------

To run the tests: `docker-compose exec app vendor/bin/phpunit`

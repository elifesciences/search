eLife Search
=============

This project is using elasticsearch to index content of the eLife articles and provide a full-text search for Journal.

Installation
------------

1. Clone the project `git clone https://github.com/elifesciences/search.git`
2. Run `docker-compose up --build`

Important : Rename `config.php.dist` on local to `config.php` because config.php is in .gitignore.

Testing
-------

To run the tests: `docker-compose exec app vendor/bin/phpunit`
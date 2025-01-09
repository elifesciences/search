.PHONY: dev
dev: config.php
	docker compose up --wait
	docker compose logs --follow

.PHONY: check
check: test
	docker compose run --rm --no-deps app vendor/bin/phpcs --standard=phpcs.xml.dist --warning-severity=0 -p src/ tests/ web/
	docker compose run --rm --no-deps app vendor/bin/composer-dependency-analyser
	docker compose run --rm --no-deps app vendor/bin/phpstan analyse

.PHONY: test
test: config.php
	docker compose up --wait
	docker compose exec app vendor/bin/phpunit

.PHONY: stop
stop:
	docker compose down

config.php:
	cp config.php.dist config.php

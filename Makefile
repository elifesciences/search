.PHONY: dev
dev: bring-up-all-services
	docker compose logs --follow

.PHONY: bring-up-all-services
bring-up-all-services: config.php
	docker compose up --wait

.PHONY: check
check: static-analysis test

.PHONY: static-analysis
static-analysis:
	docker compose run --rm --no-deps app vendor/bin/phpcs --standard=phpcs.xml.dist --warning-severity=0 -p src/ tests/ web/
	docker compose run --rm --no-deps app vendor/bin/composer-dependency-analyser
	docker compose run --rm --no-deps app vendor/bin/phpstan analyse

.PHONY: test
test: bring-up-all-services
	docker compose exec app vendor/bin/phpunit

.PHONY: all-tests
all-tests: config.php
	docker compose up app --wait
	docker compose down queue-watcher
	docker compose exec app bash project_tests.sh

.PHONY: stop
stop:
	docker compose down

config.php:
	cp config.php.dist config.php

.PHONY: dev
dev: bring-up-app-and-queue-watcher
	docker compose logs --follow

.PHONY: bring-up-app-and-queue-watcher
bring-up-app-and-queue-watcher: config.php
	docker compose up --wait

.PHONY: bring-up-app-without-queue-watcher
bring-up-app-without-queue-watcher: config.php
	docker compose up app --wait
	docker compose down queue-watcher

.PHONY: check
check: static-analysis test

.PHONY: static-analysis
static-analysis:
	docker compose run --rm --no-deps app vendor/bin/phpcs --standard=phpcs.xml.dist --warning-severity=0 -p src/ tests/ web/
	docker compose run --rm --no-deps app vendor/bin/composer-dependency-analyser
	docker compose run --rm --no-deps app vendor/bin/phpstan analyse

.PHONY: test
test: bring-up-app-and-queue-watcher
	docker compose exec app vendor/bin/phpunit $(TEST)

.PHONY: all-checks
all-checks: bring-up-app-without-queue-watcher
	docker compose exec app bash project_tests.sh

.PHONY: stop
stop:
	docker compose down

.PHONY: clean
clean:
	docker compose down --volumes

.PHONY: import-all
import-all:
	docker compose exec app bin/console queue:import all

.PHONY: update-api-sdk
update-api-sdk:
	docker compose run --no-deps setup composer install
	docker compose run --no-deps setup composer update 'elife/api' 'elife/api-sdk' --no-suggest --no-interaction

config.php:
	cp config.php.dist config.php

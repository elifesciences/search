.PHONY: dev
dev: bring-up-app-and-queue-watcher
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml logs --follow

.PHONY: prod
prod: bring-up-app-and-queue-watcher
	docker compose --file docker-compose.yaml --file docker-compose.prod.yaml logs --follow

.PHONY: bring-up-app-and-queue-watcher
bring-up-app-and-queue-watcher: config.php build
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml up --wait

.PHONY: bring-up-app-without-queue-watcher
bring-up-app-without-queue-watcher: config.php build
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml up app --wait
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml down queue-watcher

.PHONY: build
build:
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml build

.PHONY: check
check: static-analysis test

.PHONY: static-analysis
static-analysis: config.php build
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml run --rm --no-deps app vendor/bin/phpcs --standard=phpcs.xml.dist --warning-severity=0 -p src/ tests/ web/
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml run --rm --no-deps app vendor/bin/composer-dependency-analyser
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml run --rm --no-deps app vendor/bin/phpstan analyse

.PHONY: test
test: config.php bring-up-app-and-queue-watcher
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml exec app vendor/bin/phpunit $(TEST)

.PHONY: clean
clean:
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml down --volumes
	rm -rf var/logs/*.json

.PHONY: all-checks
all-checks: config.php clean bring-up-app-without-queue-watcher
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml exec app bash project_tests.sh

.PHONY: stop
stop:
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml down

ENTITY = all
.PHONY: import-entity
import-entity: config.php bring-up-app-and-queue-watcher
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml exec app bin/console queue:import $(ENTITY)

.PHONY: import-all-entities-in-journal-test-environment
import-all-entities-in-journal-test-environment:
	kubectl -n journal--test create job --from=cronjob/search-queue-import-all import-all-$(shell date "+%Y%m%d-%H%M")

.PHONY: update-api-sdk
update-api-sdk: config.php build
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml run --no-deps setup composer install
	docker compose --file docker-compose.yaml --file docker-compose.dev.yaml run --no-deps setup composer update 'elife/api' 'elife/api-sdk' --no-suggest --no-interaction

.PHONY: clean-index-for-search-test
clean-index-for-search-test:
	kubectl -n journal--test exec -it $$(kubectl get pods -n journal--test -o json | jq -r '.items[] | select(.metadata.name | startswith("search-queue-watcher-")) | .metadata.name' | head -n1) -- ./bin/console search:setup -d

config.php:
	cp config.php.dist config.php

DOCKER_COMPOSE_DEV = docker compose --file docker-compose.yaml --file docker-compose.dev.yaml
APP_CONSOLE = $(DOCKER_COMPOSE_DEV) exec app bin/console

.PHONY: dev
dev: bring-up-app-and-queue-watcher
	$(DOCKER_COMPOSE_DEV) logs --follow

.PHONY: prod
prod: config.php
	docker compose --file docker-compose.yaml --file docker-compose.prod.yaml build
	docker compose --file docker-compose.yaml --file docker-compose.prod.yaml up --wait
	docker compose --file docker-compose.yaml --file docker-compose.prod.yaml logs --follow

.PHONY: bring-up-app-and-queue-watcher
bring-up-app-and-queue-watcher: config.php build
	$(DOCKER_COMPOSE_DEV) up --wait

.PHONY: bring-up-app-without-queue-watcher
bring-up-app-without-queue-watcher: config.php build
	$(DOCKER_COMPOSE_DEV) up app --wait
	$(DOCKER_COMPOSE_DEV) down queue-watcher

.PHONY: build
build:
	$(DOCKER_COMPOSE_DEV) build

.PHONY: check
check: static-analysis fast-test

.PHONY: static-analysis
static-analysis: vendor
	vendor/bin/phpcs --standard=phpcs.xml.dist --warning-severity=0 -p src/ tests/ web/
	vendor/bin/composer-dependency-analyser
	vendor/bin/phpstan analyse

.PHONY: test
test: config.php bring-up-app-and-queue-watcher
	$(DOCKER_COMPOSE_DEV) exec app vendor/bin/phpunit $(TEST)

.PHONY: fast-test
fast-test: vendor
	vendor/bin/phpunit --exclude-group web --exclude-group slow $(TEST)

.PHONY: integration-test
integration-test: config.php clean bring-up-app-without-queue-watcher
	$(DOCKER_COMPOSE_DEV) exec app bash bin/ci-import

.PHONY: clean
clean:
	$(DOCKER_COMPOSE_DEV) down --volumes
	rm -rf var/logs/*.json
	rm -rf vendor

.PHONY: all-checks
all-checks: config.php clean bring-up-app-without-queue-watcher
	$(DOCKER_COMPOSE_DEV) exec app bash project_tests.sh

.PHONY: stop
stop:
	$(DOCKER_COMPOSE_DEV) down

ENTITY = all
.PHONY: import-entity
import-entity: config.php bring-up-app-and-queue-watcher
	$(APP_CONSOLE) queue:import $(ENTITY)

.PHONY: observe-indexing-status
observe-indexing-status:
	$(APP_CONSOLE) queue:count
	$(APP_CONSOLE) index:total:write
	$(APP_CONSOLE) index:total:read

NEW_INDEX_NAME = elife_search_$(shell date "+%Y%m%d%H%M%S")

.PHONY: test-reindexing
test-reindexing: bring-up-app-and-queue-watcher
	$(DOCKER_COMPOSE_DEV) exec app bin/reindex $(NEW_INDEX_NAME)

.PHONY: import-all-entities-in-journal-test-environment
import-all-entities-in-journal-test-environment:
	kubectl -n journal--test create job --from=cronjob/search-queue-import-all import-all-$(shell date "+%Y%m%d-%H%M")

.PHONY: reindex-in-journal-test-environment
reindex-in-journal-test-environment:
	kubectl -n journal--test create job --from=cronjob/search-queue-reindex reindex-$(shell date "+%Y%m%d-%H%M")

.PHONY: reindex-in-journal-prod-environment
reindex-in-journal-prod-environment:
	kubectl -n journal--prod create job --from=cronjob/search-queue-reindex reindex-$(shell date "+%Y%m%d-%H%M")

.PHONY: update-api
update-api: config.php build
	$(DOCKER_COMPOSE_DEV) run --no-deps setup composer install
	$(DOCKER_COMPOSE_DEV) run --no-deps setup composer update 'elife/api' 'elife/api-sdk' --no-suggest --no-interaction

.PHONY: clean-index-for-search-test
clean-index-for-search-test:
	kubectl -n journal--test exec -it $$(kubectl get pods -n journal--test -o json | jq -r '.items[] | select(.metadata.name | startswith("search-queue-watcher-")) | .metadata.name' | head -n1) -- ./bin/console search:setup -d

.PHONY: journal-test-index-details
journal-test-index-details:
	@echo "journal-test search indices: "
	@kubectl -n journal--test exec -c search -it $$(kubectl get pods -n journal--test -o json | jq -r '.items[] | select(.metadata.name | startswith("search-queue-watcher-")) | .metadata.name' | head -n1) -- ./bin/console index:list
	@echo "Total items in the journal-test write index: "
	@kubectl -n journal--test exec -c search -it $$(kubectl get pods -n journal--test -o json | jq -r '.items[] | select(.metadata.name | startswith("search-queue-watcher-")) | .metadata.name' | head -n1) -- ./bin/console index:total:write
	@echo "Total items in the journal-test read index: "
	@kubectl -n journal--test exec -c search -it $$(kubectl get pods -n journal--test -o json | jq -r '.items[] | select(.metadata.name | startswith("search-queue-watcher-")) | .metadata.name' | head -n1) -- ./bin/console index:total:read

.PHONY: journal-prod-index-details
journal-prod-index-details:
	@echo "journal-prod search indices: "
	@kubectl -n journal--prod exec -c search -it $$(kubectl get pods -n journal--prod -o json | jq -r '.items[] | select(.metadata.name | startswith("search-queue-watcher-")) | .metadata.name' | head -n1) -- ./bin/console index:list
	@echo "Total items in the journal-prod write index: "
	@kubectl -n journal--prod exec -c search -it $$(kubectl get pods -n journal--prod -o json | jq -r '.items[] | select(.metadata.name | startswith("search-queue-watcher-")) | .metadata.name' | head -n1) -- ./bin/console index:total:write
	@echo "Total items in the journal-prod read index: "
	@kubectl -n journal--prod exec -c search -it $$(kubectl get pods -n journal--prod -o json | jq -r '.items[] | select(.metadata.name | startswith("search-queue-watcher-")) | .metadata.name' | head -n1) -- ./bin/console index:total:read

config.php:
	cp config.php.dist config.php

vendor: composer.json composer.lock
	composer install

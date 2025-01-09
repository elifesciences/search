.PHONY: dev
dev: config.php
	docker compose up --wait
	docker compose logs --follow

.PHONY: test
test: config.php
	docker compose up --wait
	docker compose exec app vendor/bin/phpunit

.PHONY: stop
stop:
	docker compose down

config.php:
	cp config.php.dist config.php

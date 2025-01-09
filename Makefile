.PHONY: dev
dev: config.php
	docker compose up --wait
	docker compose logs --follow

config.php:
	cp config.php.dist config.php

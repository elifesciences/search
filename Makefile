.PHONY: dev
dev: config.php
	docker compose up

config.php:
	cp config.php.dist config.php

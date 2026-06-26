.PHONY: build test lint lint-check shell

build:
	docker compose build

test:
	docker compose run --rm app vendor/bin/phpunit --colors=always

lint:
	docker compose run --rm app vendor/bin/pint

lint-check:
	docker compose run --rm app vendor/bin/pint --test

shell:
	docker compose run --rm app sh

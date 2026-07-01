.PHONY: build test coverage lint lint-check shell

build:
	docker compose build

test:
	docker compose run --rm app vendor/bin/phpunit --no-coverage --colors=always

coverage:
	docker compose run --rm app sh -c "mkdir -p build && vendor/bin/phpunit --coverage-text --coverage-clover=build/coverage.xml && composer coverage:check"

lint:
	docker compose run --rm app vendor/bin/pint

lint-check:
	docker compose run --rm app vendor/bin/pint --test

shell:
	docker compose run --rm app sh

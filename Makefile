.PHONY: build up install test coverage docs

build:
	docker compose build

up:
	docker compose up -d mysql

install:
	docker compose run --rm php composer install

test:
	docker compose run --rm php vendor/bin/pest

coverage:
	docker compose run --rm php vendor/bin/pest --coverage

docs:
	docker compose run --rm php sh -c "cd docs && npm ci && npm run docs:build"

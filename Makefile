up:
	docker-compose up -d

init: up
	docker-compose exec app composer install
	docker-compose exec app php bin/console --no-interaction doctrine:migration:migrate

down:
	docker-compose down

test:
	APP_ENV=test php bin/phpunit

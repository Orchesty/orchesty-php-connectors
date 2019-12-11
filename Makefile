.PHONY: docker-up-force docker-down-clean test

DC=docker-compose
DE=docker-compose exec -T app
IMAGE=dkr.hanaboso.net/pipes/connectors
BASE=hanabosocom/php-dev:php-7.4

.env:
	sed -e "s/{DEV_UID}/$(shell id -u)/g" \
		-e "s/{DEV_GID}/$(shell id -u)/g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo "\/tmp\/.ssh-auth-sock"; else echo '\/tmp\/.nope'; fi)/g" \
		.env.dist >> .env; \

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

# Docker
prod-build: .env
	docker pull $(IMAGE):dev
	docker-compose -f docker-compose.yml run --rm --no-deps app  composer install --ignore-platform-reqs
	docker build -t $(IMAGE):master .
	docker push $(IMAGE):master

docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans
	sleep 10

docker-down-clean: .env
	$(DC) down -v

# Composer
composer-install:
	$(DE) composer install --ignore-platform-reqs --no-suggest

composer-update:
	$(DE) composer update --ignore-platform-reqs --no-suggest

clear-cache:
	$(DE) rm -rf var/log
	$(DE) bin/console cache:clear --env=test
	$(DE) bin/console cache:warmup --env=test

# App
init-dev: docker-up-force composer-install

phpcodesniffer:
	$(DE) vendor/bin/phpcs -p --standard=ruleset.xml --colors src tests

phpstan:
	$(DE) vendor/bin/phpstan analyse -c phpstan.neon -l 8 --memory-limit=512M src tests

phpunit:
	$(DE) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --colors tests/Unit

phpintegration:
	$(DE) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --colors tests/Integration

phpcontroller:
	$(DE) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --colors tests/Controller

phpcoverage:
	$(DE) php vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --coverage-html var/coverage --whitelist src tests

phpcoverage-ci:
	$(DE) ./vendor/hanaboso/php-check-utils/bin/coverage.sh 85

test: docker-up-force composer-install fasttest

fasttest: phpcodesniffer clear-cache phpstan phpunit phpintegration phpcontroller phpcoverage-ci

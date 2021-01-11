#
# Env
#
-include .env


#
##@ HELP
#

.PHONY: help
help:  ## Display this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)
.DEFAULT_GOAL := help


#
##@ INITIALIZATION
#

install: ## Install dependencies, init db and build cache (Use "make start" if you use Docker !)
	@${COMPOSER} --no-scripts --optimize-autoloader --no-progress --no-suggest --classmap-authoritative --prefer-dist --no-interaction install
	@${MAKE} db
	@${MAKE} warmup

warmup: ## Symfony cache warmup
	@echo 'Preparing symfony cache...'
	@${PHP} ${CONSOLE} cache:clear
	@${PHP} ${CONSOLE} cache:warmup

db: ## Prepare database
	@${PHP} ${CONSOLE} doctrine:database:drop --force --if-exists
	@${PHP} ${CONSOLE} doctrine:database:create
	@${PHP} ${CONSOLE} doctrine:schema:update -f
	@${PHP} ${CONSOLE} doctrine:fixtures:load --env=dev --no-interaction
	@${PHP} ${CONSOLE} doctrine:mongodb:schema:drop || true
	@${PHP} ${CONSOLE} doctrine:mongodb:schema:create
	@${PHP} ${CONSOLE} doctrine:mongodb:schema:update
	@${PHP} ${CONSOLE} doctrine:mongodb:fixtures:load --env=dev --no-interaction

#
##@ DOCKER
#

build: ## Build image
	@docker build -t ${IMAGE} .

start: ## Start container
	@docker network create ${CONTAINER_NAME}-network
	@docker run -d \
		--name ${CONTAINER_NAME} \
		-v '${PWD}:/app' \
		-e XDEBUG_MODE=coverage \
		--network ${CONTAINER_NAME}-network \
		${IMAGE}
	@docker run -d \
		--name ${CONTAINER_NAME}-mysql \
		-e 'MYSQL_ALLOW_EMPTY_PASSWORD=yes' \
	  	-e 'MYSQL_DATABASE=${CONTAINER_NAME}' \
		--network ${CONTAINER_NAME}-network \
		mariadb:10.4.8
	@docker run -d \
		--name ${CONTAINER_NAME}-mongo \
		-e 'MONGO_INITDB_DATABASE=${MONGO_DB_NAME}' \
		--network ${CONTAINER_NAME}-network \
		mongo:4.2
	@${MAKE} install

stop: ## Stop and remove container
	@docker stop ${CONTAINER_NAME} && docker rm ${CONTAINER_NAME}
	@docker stop ${CONTAINER_NAME}-mysql && docker rm ${CONTAINER_NAME}-mysql
	@docker stop ${CONTAINER_NAME}-mongo && docker rm ${CONTAINER_NAME}-mongo
	@docker network rm ${CONTAINER_NAME}-network

terminal: ## Start terminal shell
	@docker exec -it ${CONTAINER_NAME} sh

#
##@ TESTING
#

.PHONY: tests
tests: ## Launch a set of tests
	@${MAKE} warmup db lint-yaml lint-container composer-validate php-cs phpstan php-unit-coverage check-coverage

phpstan:  ## Launch phpstan analyse
	@echo 'Starting phpstan analyse...'
	@${PHP} vendor/bin/phpstan analyse --ansi --configuration=phpstan.neon --level=7 --memory-limit=-1 src

php-cs: ## Launch php-cs without fixing
	@echo 'Starting php-cs analyse...'
	@${PHP} vendor/bin/php-cs-fixer fix --ansi -v --show-progress=estimating-max --diff-format=udiff --dry-run

php-cs-fixer: ## Launch php-cs-fixer to fix files
	@echo 'Starting php-cs-fixer...'
	@${PHP} vendor/bin/php-cs-fixer fix --ansi -v --show-progress=estimating-max --diff-format=udiff

php-unit: ## Launch unit tests & coverage
	@echo 'Starting phpunit tests...'
	@${PHP} vendor/bin/phpunit --colors=always

php-unit-coverage: ## Launch unit tests only
	@echo 'Phpunit tests with coverage analyzis...'
	@${PHP} vendor/bin/phpunit --coverage-xml coverage --coverage-html coverage --colors=always

lint-yaml: ## Lint yaml
	@echo 'Starting yaml lint...'
	@${PHP} ${CONSOLE} lint:yaml src --ansi --parse-tags

lint-container: ## Lint container
	@echo 'Starting container lint...'
	@${PHP} ${CONSOLE} lint:container --ansi

composer-validate: ## Valid composer.json
	@echo 'Validating composer.json...'
	@${COMPOSER} validate composer.json

check-coverage: ## Check if coverage satisfying
	@echo 'Testing coverage...'
	@if [ ! -f coverage/index.xml ]; then \
  		echo "'coverage/index.xml' is missing. \nPlease run 'make php-unit' to generate coverage files"; \
	else \
		${PHP} ${CONSOLE} check:coverage --ansi; \
	fi;

.PHONY: coverage
coverage: ## Open coverage report in browser
	@if [ ! -f coverage/index.html ]; then \
  		echo "'coverage/index.html' is missing. \nPlease run 'make php-unit' to generate coverage files"; \
	else \
		open coverage/index.html; \
	fi;

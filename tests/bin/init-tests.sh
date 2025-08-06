#!/bin/bash

# Use the test-specific docker-compose.yaml file
COMPOSE_FILE="$(dirname "$0")/docker-compose.yaml"

docker compose -f "$COMPOSE_FILE" down -v --remove-orphans
docker compose -f "$COMPOSE_FILE" up -d

docker compose -f "$COMPOSE_FILE" exec php .github/ci/scripts/setup-pimcore-environment.sh
#docker compose -f "$COMPOSE_FILE" exec php composer update

#docker compose -f "$COMPOSE_FILE" exec php touch config/dao-classmap.php
#docker compose -f "$COMPOSE_FILE" exec php ./bin/console internal:model-dao-mapping-generator

printf "\n\n\n================== \n"
printf "Run 'docker compose -f %s exec php vendor/bin/codecept run -vv' to re-run the tests.\n" "$COMPOSE_FILE"
printf "Run 'docker compose -f %s down -v --remove-orphans' to shutdown container and cleanup.\n\n" "$COMPOSE_FILE"
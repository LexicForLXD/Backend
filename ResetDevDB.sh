#!/usr/bin/env bash

docker-compose down
docker-compose up -d

sleep 1.5

php bin/console doctrine:schema:update --force

php bin/console doctrine:fixtures:load

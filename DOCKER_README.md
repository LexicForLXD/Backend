# DEV Env with Docker

## Start Docker-DEV environment

- Update the path to the LXD-certificate "/path/to/cert/" with your local path in docker-compose.yml 

- Start all containers
```
docker-compose up -d
```
- Create the database schema 
```
docker-compose exec web php bin/console doctrine:schema:update --force
```
- Create the database fixtures 
```
docker-compose exec web php bin/console doctrine:fixtures:load
```
- Done :)

## Access to Containers
- WebServer : localhost port 80
- Postgres Database : localhost port 5432
# Environment with Docker

## Start Docker environment

- Update the path to the LXD-certificate "/path/to/cert/" with your local path in docker-compose.yml

- Update the path to the SSH Key "/path/to/ssh/" with your local path in docker-compose.yml

- Optional (remove not required cert volume links)

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
- Default user - username : mmustermann - password : password

- Done :)

## Useful commands 

- Create new user account 
```
docker-compose exec web php bin/console app:create-user
```

- Execute shell commands in the Web Container
```
docker-compose exec web <command>
```

## Access to Containers
- WebServer : localhost port 80
- Postgres Database : localhost port 5432
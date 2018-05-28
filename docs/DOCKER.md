# Environment with Docker

## Start Docker environment

- Copy .env.EXAMPLE to .env and make your changes

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
docker-compose exec web php bin/console fos:oauth-server:create-client --grant-type password
```
- Create the first user
```
docker-compose exec web php bin/console app:create-user
```
- Done :)

## Useful commands 

- Create new user account 
```
docker-compose exec web php bin/console app:create-user
```
- List all users 
```
docker-compose exec web php bin/console app:list-users
```
- Delete a user account 
```
docker-compose exec web php bin/console app:delete-user
```

- Execute shell commands in the Web Container
```
docker-compose exec web <command>
```

- Get log files
```
 docker-compose exec web cat var/logs/prod.log
 docker-compose exec web cat var/logs/dev.log
 docker-compose exec web cat var/logs/queue.log
```
## Access to Containers
- WebServer : localhost port 80
- Postgres Database : localhost port 5432
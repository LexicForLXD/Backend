# DEV Env with Docker

## Start Docker-DEV environment

- If you want to run the the latest master based image from GitLab without rebuilding it locally remove the <code>build: .</code> line from the docker-compose.yml file - Info: login to the GitLab registry by using <code>docker login git.janrtr.de:4567</code> and providing your GitLab credentials

- Update the path to the LXD-certificate "/path/to/cert/" with your local path in docker-compose.yml

- Optional (remove not required cert volume links)

- Start all containers
```
docker-compose up -d
```
- Composer install
```
docker-compose exec web composer install
```
- Create the database schema
```
docker-compose exec web php bin/console doctrine:schema:update --force
```
- Create the database fixtures
```
docker-compose exec web php bin/console doctrine:fixtures:load
```
- Create new User
```
docker-compose exec web php bin/console app:create-user
```


- Done :)

## Access to Containers
- WebServer : localhost port 80
- Postgres Database : localhost port 5432
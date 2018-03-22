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

## Required steps for Duplicity Backup
##### This steps are required to work with BackupDestinations using SSH Key Authentication

- Add these to links to the docker-compose file (under volumes)
```
- "/path/to/backupSSH/ssh:/root/.ssh/id_rsa"
- "/path/to/backupSSH/ssh.pub:/root/.ssh/id_rsa.pub"
```
- Replace "path/to/backupSSH" with local filepath to the SSH Key
- The private key must have permissions 600 and the public key 644

- Add BackupDestinations to known hosts (accept with yes)
```
docker-compose exec web ssh myuser@myDestination
```

## Access to Containers
- WebServer : localhost port 80
- Postgres Database : localhost port 5432
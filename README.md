SYP-LXC Backend
========================

[![pipeline status](https://git.janrtr.de/syp-lxc/Backend/badges/master/pipeline.svg)](https://git.janrtr.de/syp-lxc/Backend/commits/master)
[![coverage report](https://git.janrtr.de/syp-lxc/Backend/badges/master/coverage.svg)](https://git.janrtr.de/syp-lxc/Backend/commits/master)
# Requirements
- PostgreSQL
- Redis

#### Required PHP-Modules

```
[PHP Modules]
bcmath
bz2
Core
ctype
curl
date
dom
filter
gd
gettext
gmp
hash
intl
json
libxml
mbstring
mcrypt
mysqlnd
openssl
pcre
PDO
pdo_dblib
pdo_mysql
PDO_ODBC
pdo_pgsql
Phar
readline
redis
Reflection
session
SimpleXML
soap
SPL
ssh2
standard
tokenizer
xml
xmlreader
xmlrpc
xmlwriter
zip
zlib
```


# Installation from Source

### Resolve dependencies

```
composer install
```

### Create Database schema

```php
php bin/console doctrine:schema:update --force
```

### Test password grant client erzeugen
```php
php bin/console doctrine:fixtures:load
```

### User erzeugen
```php
php bin/console app:create-user
```

# Installation via Docker
- see documentation [here](docs/DOCKER.md)

# Documentation
### Create up to date Swagger documentation
```php
./vendor/bin/swagger -e vendor
```

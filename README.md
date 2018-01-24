SYP-LXC Backend
========================

[![pipeline status](https://git.janrtr.de/syp-lxc/Backend/badges/master/pipeline.svg)](https://git.janrtr.de/syp-lxc/Backend/commits/master)
[![coverage report](https://git.janrtr.de/syp-lxc/Backend/badges/master/coverage.svg)](https://git.janrtr.de/syp-lxc/Backend/commits/master)
#Requirements
- PostgreSQL
- Redis
####Required PHP-Modules
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
mysqli
mysqlnd
openssl
pcre
PDO
pdo_dblib
pdo_mysql
PDO_ODBC
pdo_pgsql
pdo_sqlite
Phar
readline
redis
Reflection
session
SimpleXML
soap
SPL
sqlite3
ssh2
standard
tokenizer
xml
xmlreader
xmlrpc
xmlwriter
zip
zlib

[Zend Modules]
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

# Installation via Docker
- see DOCKER_README.md

# Documentation
### Create up to date Swagger documentation
```php
./vendor/bin/swagger -e vendor   
```

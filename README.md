SYP-LXC Backend
========================

[![pipeline status](https://git.janrtr.de/syp-lxc/Backend/badges/master/pipeline.svg)](https://git.janrtr.de/syp-lxc/Backend/commits/master)
[![coverage report](https://git.janrtr.de/syp-lxc/Backend/badges/master/coverage.svg)](https://git.janrtr.de/syp-lxc/Backend/commits/master)
# Installation

### Resolve dependencies

```
composer install
```

### Create Database schema

```php
php bin/console doctrine:schema:update --force
```

### Start Symfony development Server

```php
php bin/console server:run
```

### Test password grant client erzeugen
```php
php bin/console doctrine:fixtures:load     
```

# Documentation
### Create up to date Swagger documentation
```php
./vendor/bin/swagger -e vendor   
```

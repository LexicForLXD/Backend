SYP-LXC Backend
========================

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

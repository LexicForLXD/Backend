SYP-LXC Backend
========================

Installation
--------------

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


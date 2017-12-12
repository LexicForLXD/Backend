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

### Test password grant client erzeugen
```sql
INSERT INTO `oauth2_clients` VALUES (NULL, '3bcbxd9e24g0gk4swg0kwgcwg4o8k8g4g888kwc44gcc0gwwk4', 'a:0:{}', '4ok2x70rlfokc8g0wws8c8kwcokw80k44sg48goc0ok4w0so0k', 'a:1:{i:0;s:8:"password";}');
```


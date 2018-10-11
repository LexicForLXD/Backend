# SYP-LXC Backend

[![Build Status](https://travis-ci.com/LexicForLXD/Backend.svg?branch=master)](https://travis-ci.com/LexicForLXD/Backend)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2FLexicForLXD%2FBackend.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2FLexicForLXD%2FBackend?ref=badge_shield)
[![semantic-release](https://img.shields.io/badge/%20%20%F0%9F%93%A6%F0%9F%9A%80-semantic--release-e10079.svg)](https://github.com/semantic-release/semantic-release)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/43ce32100dde4fcfabe9e67fbc3f06f8)](https://www.codacy.com/app/LexicForLXD/Backend?utm_source=github.com&utm_medium=referral&utm_content=LexicForLXD/Backend&utm_campaign=Badge_Grade)

<!-- [![coverage report](https://git.janrtr.de/syp-lxc/Backend/badges/master/coverage.svg)](https://git.janrtr.de/syp-lxc/Backend/commits/master) -->

## Requirements

- see requirements [here](../docs/REQUIREMENTS.md)

## Installation from Source

### Resolve dependencies and set parameters

```
composer install
```

### Create Database schema

```php
php bin/console doctrine:schema:update --force
```

### Password grant client erzeugen

```php
php bin/console fos:oauth-server:create-client --grant-type password --grant-type refresh_token
```

### User erzeugen

```php
php bin/console app:create-user
```

# Installation via Docker

- see prod environment documentation [here](../docs/DOCKER.md)
- see backend development environment documentation [here](../docs/DOCKER_DEV.md)

## Documentation

### Create up to date Swagger documentation

```php
./vendor/bin/swagger -e vendor
```

### Hosted swagger docs

[here](https://lexicforlxd.github.io/Backend/?url=https://raw.githubusercontent.com/LexicForLXD/Backend/gh-pages/openapi.json)

## License

[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2FLexicForLXD%2FBackend.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2FLexicForLXD%2FBackend?ref=badge_large)

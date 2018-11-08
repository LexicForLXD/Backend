# Lexic Installation

## Preparation

- make sure the the [requirements](./REQUIREMENTS.md) are met.

## Install

- clone the repository
- change into cloned directory: `cd Backend`
- generate a ssh key pair: `ssh-keygen`
- generate a ssl certificate: `sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout ./privkey.pem -out ./cert.pem`
- install the system: `composer install`
- set up oauth: `php bin/console fos:oauth-server:create-client --grant-type password --grant-type refresh_token`
- set up first user: `php bin/console app:create-user`

## Supervisord

Example for supervisord config

```bash
[supervisord]
nodaemon=true

[program:php-fpm]
command=php-fpm7.2 -F
#stdout_logfile=/dev/stdout
#stdout_logfile_maxbytes=0
#stderr_logfile=/dev/stderr
#stderr_logfile_maxbytes=0
autorestart=true
startretries=0

[program:nginx]
command=nginx -g 'daemon off;'
#stdout_logfile=/dev/stdout
#stdout_logfile_maxbytes=0
#stderr_logfile=/dev/stderr
#stderr_logfile_maxbytes=0
autorestart=false
startretries=0

[program:queue-daemon]
command=php /var/www/backend/bin/console dtc:queue:run -v -t 3600 -d 3600
#stdout_logfile=/dev/stdout
#stdout_logfile_maxbytes=0
stderr_logfile=/var/www/backend/var/logs/queue.log
stderr_logfile_maxbytes=0
autorestart=true
startretries=0
```

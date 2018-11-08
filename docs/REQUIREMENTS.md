# LEXIC requirements

## Required services

- PostgreSQL (recommended version 10.x)

## Required PHP-Modules

```
[PHP Modules]
Core
ctype
curl
date
dom
filter
gettext
hash
json
libxml
openssl
pcre
PDO
pdo_pgsql
Phar
readline
Reflection
session
SimpleXML
SPL
ssh2
standard
tokenizer
xml
xmlreader
xmlwriter
zlib
```

If you want to use a different DBMS you are free to use it. You can use every DBMS, which is supported by doctrine/dbal. You have to edit app/config/config.yml and make sure that the required php-modules are installed.

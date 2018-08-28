# LEXIC requirements

## Required services

- PostgreSQL (recommended version 10.x)

## Required PHP-Modules

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
openssl
pcre
PDO
pdo_dblib
PDO_ODBC
pdo_pgsql
Phar
readline
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

If you want to use a different DBMS you are free to use it. You can use every DBMS, which is supported by doctrine/dbal. You have to edit app/config/config.yml and make sure that the required php-modules are installed.

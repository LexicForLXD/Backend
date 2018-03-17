FROM janrtr/docker-symfony-php7-composer:3.7

RUN apk --no-cache add git php7-simplexml php7-ssh2

#Duplicity - commands from https://hub.docker.com/r/wernight/duplicity/~/dockerfile/
RUN set -x \
 && apk add --no-cache \
        ca-certificates \
        duplicity \
        lftp \
        openssh \
        openssl \
        py-crypto \
        py-pip \
        py-paramiko \
        py-setuptools \
        rsync \
 && update-ca-certificates \
 && pip install \
      pydrive==1.3.1 \
      fasteners==0.14.1 \
 && apk del --purge py-pip

ADD /app /www/symfony/app

#Add parameters.yml for Docker
ADD /docker/parameters.yml /www/symfony/app/config/parameters.yml

ADD /bin /www/symfony/bin
ADD /src /www/symfony/src
ADD /var /www/symfony/var
ADD /web /www/symfony/web
ADD /composer.json /www/symfony/composer.json
ADD /composer.lock /www/symfony/composer.lock

#Add cert volume
RUN mkdir -p /srv/lexic
RUN chown -R www:www /srv/lexic

# Add phpunit
RUN wget https://phar.phpunit.de/phpunit.phar
RUN chmod +x phpunit.phar
RUN mv phpunit.phar /usr/local/bin/phpunit

WORKDIR /www/symfony
ENV SYMFONY_ENV=dev

RUN chown -R www:www /www

# Configure supervisord
COPY docker/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN composer install --no-interaction
RUN chown -R www:www /www

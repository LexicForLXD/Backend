FROM janrtr/docker-symfony-php7-composer:3.7

RUN apk --no-cache add git php7-simplexml php7-ssh2

#Install xdebug
ENV XDEBUG_VERSION 2.6.0

RUN apk update && apk add --no-cache --virtual .build-deps autoconf gcc make \
    g++ zlib-dev file g++ libc-dev make pkgconf \
    tar curl php7-pear tzdata php7-dev php7-phar libmemcached-dev \
    && apk add php7 php7-cli php7-curl php7-gd git php7-json libmemcached \
    && cp /usr/share/zoneinfo/Europe/Berlin /etc/localtime \
    && echo "Europe/Berlin" > /etc/timezone \

##Xdebug
#&& cd /tmp && wget http://xdebug.org/files/xdebug-$XDEBUG_VERSION.tgz \
#    && tar -zxvf xdebug-$XDEBUG_VERSION.tgz \
#    && cd xdebug-$XDEBUG_VERSION && phpize \
#    && ./configure --enable-xdebug && make && make install \
#    && echo "zend_extension=$(find /usr/lib/php7/modules/ -name xdebug.so)" > /etc/php7/php.ini \
#    && echo "xdebug.remote_enable=on" >> /etc/php7/php.ini \
#    && echo "xdebug.remote_handler=dbgp" >> /etc/php7/php.ini \
#    && echo "xdebug.remote_connect_back=1" >> /etc/php7/php.ini \
#    && echo "xdebug.remote_autostart=on" >> /etc/php7/php.ini \
#    && echo "xdebug.remote_port=9004" >> /etc/php7/php.ini \
#    && echo "xdebug.remote_autostart = 1" >> /etc/php7/php.ini \

#Cleanup
&& rm -rf /tmp/* \
   && rm -rf /var/cache/apk/* \
   && apk del .build-deps && rm -rf tmp/*

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
RUN wget https://phar.phpunit.de/phpunit-6.5.5.phar && mv phpunit-6.5.5.phar phpunit.phar
RUN chmod +x phpunit.phar
RUN mv phpunit.phar /usr/local/bin/phpunit

WORKDIR /www/symfony
ENV SYMFONY_ENV=dev

RUN chown -R www:www /www

# Configure supervisord
COPY docker/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

#RUN composer install --no-interaction
RUN chown -R www:www /www

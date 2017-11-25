#!/usr/bin/env bash

rm -Rf /var/www/vhosts/OLab4/www-root/core/storage/cache/*

if [ ! -f /var/www/vhosts/OLab4/composer.lock ]; then
	cd /var/www/vhosts/OLab4
	composer update
fi

if [ -f /var/lib/mysql/entrada_data.sql ]; then
  touch /tmp/entrada_data.start
	mysql -uroot -ppassword < /var/lib/mysql/entrada_data.sql
  touch /tmp/entrada_data.finished
fi

if [ -f /var/lib/mysql/openlabyrinth_data.sql ]; then
  touch /tmp/openlabyrinth_data.start
	mysql -uroot -ppassword < /var/lib/mysql/openlabyrinth_data.sql
  touch /tmp/openlabyrinth_data.finished
fi


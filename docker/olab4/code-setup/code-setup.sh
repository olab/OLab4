#!/usr/bin/env bash

rm -Rf /var/www/vhosts/Olab4/www-root/core/storage/cache

if [ ! -f /var/www/vhosts/Olab4/composer.lock ]; then
	cd /var/www/vhosts/Olab4
	composer update
fi

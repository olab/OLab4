#!/usr/bin/env bash

rm -Rf /var/www/vhosts/OLab4/www-root/core/storage/cache/*

if [ ! -f /var/www/vhosts/OLab4/composer.lock ]; then
	cd /var/www/vhosts/OLab4
	composer update
fi

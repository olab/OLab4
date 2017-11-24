#!/usr/bin/env bash

if [ ! -f /var/www/vhosts/olab4/composer.lock ]; then
	cd /var/www/vhosts/olab4
	composer update
fi


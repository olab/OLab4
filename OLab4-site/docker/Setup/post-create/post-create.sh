#!/usr/bin/env bash
#set -x 

rm -Rf /var/www/vhosts/Olab4/www-root/core/storage/cache/*

echo "Testing for composer API update"
if [ ! -f /var/www/vhosts/OLab4-api/composer.lock ]; then
    echo "Running composer update"
    touch /tmp/composer.api.start
    cd /var/www/vhosts/OLab4-api
    composer update
    touch /tmp/composer.api.finished
fi

echo "Testing for composer update"
if [ ! -f /var/www/vhosts/OLab4/composer.lock ]; then
    echo "Running composer update"
    touch /tmp/composer.start
    cd /var/www/vhosts/OLab4
    composer update
    touch /tmp/composer.finished
fi

cd /var/lib/mysql

loadscript() {

	echo "Testing for $1"
	if [ ! -f /var/lib/mysql/$1 ]; then  
		if [ ! -f /var/lib/mysql/$1.gz ]; then  
			echo "Retrieving $1"
			wget http://demo.olab.ca/demo-files/$1.gz
		fi
		gunzip -v $1.gz
	fi

	echo "Loading $1"
	touch /tmp/$1.start
	mysql -uroot -ppassword < /var/lib/mysql/$1
	touch /tmp/$1.finished

}

loadscript "entrada_auth_data.sql"
loadscript "entrada_clerkship_data.sql"
loadscript "entrada_data.sql"
loadscript "openlabyrinth_data.sql"
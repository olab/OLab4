#!/usr/bin/env bash
#set -x 

# initialize any support/config file and put them in 
# their rightful places
/bin/cp -r /var/www/vhosts/docker/Setup/root/* /
rm -Rf /var/www/vhosts/OLab4-site/www-root/core/storage/cache/*

# revert to composer 1.x as there are compatibility issues with 2.x at this time.
composer self-update --1

runcomposer() {

	echo "Composing $1"

	pushd $1	
	
	if [ ! -f composer.lock ]; then
		echo "Running composer on $1"
		touch /tmp/composer.api.start
		composer install
		touch /tmp/composer.api.finished
	fi

	popd
}

loadscript() {

	echo "Loading database $1"
	
	if [ ! -f $1_data.sql ]; then  
		if [ ! -f $1_data.sql.gz ]; then  
			echo "Retrieving $1_data.sql.gz"
			wget https://demo.olab.ca/player/demo-files/$1_data.sql.gz
		fi
		
		echo "Unzipping $1_data.sql.gz"		
		gunzip -v $1_data.sql.gz
	fi

	echo "Loading $1_data.sql"
	
	touch /tmp/$1.start
	mysql -uroot -ppassword $1 < $1_data.sql
	touch /tmp/$1.finished

}

runcomposer "/var/www/vhosts/OLab4-api"
runcomposer "/var/www/vhosts/OLab4-site"

pushd /var/lib/mysql
loadscript "entrada_auth"
loadscript "entrada_clerkship"
loadscript "entrada"
loadscript "openlabyrinth"
popd

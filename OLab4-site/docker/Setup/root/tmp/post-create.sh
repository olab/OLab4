#!/usr/bin/env bash
set -x 

rm -Rf /var/www/vhosts/Olab4/www-root/core/storage/cache/*

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

	echo "Loading for $2"
	
	if [ ! -f $1 ]; then  
		if [ ! -f $1.gz ]; then  
			echo "Retrieving $1"
			wget https://demo.olab.ca/player/demo-files/$1.gz
		fi
		
		echo "Unzipping $1"		
		gunzip -v $1.gz
	fi

	echo "Loading $1"
	
	touch /tmp/$1.start
	mysql -uroot -ppassword $2 < $1
	touch /tmp/$1.finished

}

runcomposer "/var/www/vhosts/OLab4-api"
runcomposer "/var/www/vhosts/OLab4-site"

pushd /var/lib/mysql
loadscript "entrada_auth_data.sql" "entrada_auth"
loadscript "entrada_clerkship_data.sql" "entrada_clerkship"
loadscript "entrada_data.sql" "entrada"
loadscript "openlabyrinth_data.sql" "openlabyrinth"
popd
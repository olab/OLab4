#!/usr/bin/env bash
#set -x 

rm -Rf /var/www/vhosts/OLab4/www-root/core/storage/cache/*

if [ ! -f /var/www/vhosts/OLab4/composer.lock ]; then
  touch /tmp/composer.start
	cd /var/www/vhosts/OLab4
	composer update
  touch /tmp/composer.finished
fi

cd /tmp

if [ ! -f entrada_data.sql ]; then  
  wget http://www.olab.ca:26000/demo/entrada_data.sql.gz
  gunzip entrada_data.sql.gz
  touch entrada_data.start
  mysql -uroot -ppassword < entrada_data.sql
  touch entrada_data.finished
fi

if [ ! -f openlabyrinth_data.sql ]; then
  wget http://www.olab.ca:26000/demo/openlabyrinth_data.sql.gz
  gunzip entrada_data.sql.gz
  touch openlabyrinth_data.start
  mysql -uroot -ppassword < openlabyrinth_data.sql
  touch openlabyrinth_data.finished
fi 

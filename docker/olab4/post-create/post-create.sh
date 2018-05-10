#!/usr/bin/env bash
#set -x 

rm -Rf /var/www/vhosts/OLab/Olab4/www-root/core/storage/cache/*

if [ ! -f /var/www/vhosts/OLab/OLab4/composer.lock ]; then
  touch /tmp/composer.start
	cd /var/www/vhosts/OLab/OLab4
	composer update
  touch /tmp/composer.finished
fi

cd /var/lib/mysql

if [ ! -f /var/lib/mysql/entrada_data.sql ]; then  
  wget http://www.olab.ca/dev/demo-files/entrada_data.sql.gz
  gunzip -v entrada_data.sql.gz
  touch /tmp/entrada_data.start
  mysql -uroot -ppassword < /var/lib/mysql/entrada_data.sql
  touch /tmp/entrada_data.finished
fi

if [ ! -f /var/lib/mysql/openlabyrinth_data.sql ]; then
  wget http://www.olab.ca/dev/demo-files/openlabyrinth_data.sql.gz
  gunzip -v openlabyrinth_data.sql.gz
  touch /tmp/openlabyrinth_data.start
  mysql -uroot -ppassword < /var/lib/mysql/openlabyrinth_data.sql
  touch /tmp/openlabyrinth_data.finished
fi 

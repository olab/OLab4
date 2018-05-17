#!/usr/bin/env bash
#set -x 
cd /var/lib/mysql

echo "Clearing existing schemas"
mysql -uroot -ppassword < /tmp/reset-data-db.sql

echo "Testing for entrada seed data"
if [ ! -f /var/lib/mysql/entrada_data.sql ]; then  
    if [ ! -f /var/lib/mysql/entrada_data.sql.gz ]; then  
        echo "Retrieving entrada seed data"
        wget http://www.olab.ca/apidev/demo-files/entrada_data.sql.gz
    fi
    gunzip -v entrada_data.sql.gz
fi

echo "Loading entrada seed data"
touch /tmp/entrada_data.start
pv /var/lib/mysql/entrada_data.sql | mysql -uroot -ppassword 
touch /tmp/entrada_data.finished

echo "Testing for OLab seed data"
if [ ! -f /var/lib/mysql/openlabyrinth_data.sql ]; then
    if [ ! -f /var/lib/mysql/openlabyrinth_data.sql.gz ]; then
        echo "Retrieving OLab seed data"
        wget http://www.olab.ca/apidev/demo-files/openlabyrinth_data.sql.gz
    fi  
    gunzip -v openlabyrinth_data.sql.gz
fi 

echo "Loading OLab seed data"
touch /tmp/openlabyrinth_data.start
pv /var/lib/mysql/openlabyrinth_data.sql | mysql -uroot -ppassword
touch /tmp/openlabyrinth_data.finished


#!/usr/bin/env bash
#set -x 
cd /var/lib/mysql

echo "Clearing existing schemas"
mysql -uroot -ppassword < /tmp/reset-data-db.sql

echo "Testing for entrada $1 seed data"
if [ ! -f /var/lib/mysql/entrada_data$1.sql ]; then  
    if [ ! -f /var/lib/mysql/entrada_data$1.sql.gz ]; then  
        echo "Retrieving entrada $1 seed data"
        wget http://www.olab.ca/apidev/demo-files/entrada_data$1.sql.gz
    fi
    gunzip -v entrada_data$1.sql.gz
fi

echo "Loading entrada $1 seed data"
touch /tmp/entrada_data$1.start
pv /var/lib/mysql/entrada_data$1.sql | mysql -uroot -ppassword 
touch /tmp/entrada_data$1.finished

echo "Testing for OLab $1 seed data"
if [ ! -f /var/lib/mysql/openlabyrinth_data$1.sql ]; then
    if [ ! -f /var/lib/mysql/openlabyrinth_data$1.sql.gz ]; then
        echo "Retrieving OLab $1 seed data"
        wget http://www.olab.ca/apidev/demo-files/openlabyrinth_data$1.sql.gz
    fi  
    gunzip -v openlabyrinth_data$1.sql.gz
fi 

echo "Loading OLab $1 seed data"
touch /tmp/openlabyrinth_data$1.start
pv /var/lib/mysql/openlabyrinth_data$1.sql | mysql -uroot -ppassword
touch /tmp/openlabyrinth_data$1.finished


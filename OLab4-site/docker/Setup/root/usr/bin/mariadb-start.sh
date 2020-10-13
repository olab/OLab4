#!/usr/bin/env bash

if [ ! -f /var/lib/mysql/ibdata1 ]; then

	/usr/bin/mysql_install_db  --force

	/usr/bin/mysqld_safe --user=root &
	sleep 5s

	/usr/bin/mysqladmin -u root password password

  touch /tmp/create-entrada-db.started
	/usr/bin/mysql -uroot -ppassword < /tmp/create-entrada-db.sql > /tmp/create-entrada-db.out
  touch /tmp/create-entrada-db.finished

  touch /tmp/create-olab-db.started
	/usr/bin/mysql -uroot -ppassword < /tmp/create-olab-db.sql > /tmp/create-olab-db.out
  touch /tmp/create-olab-db.finished

	pkill mysqld
	sleep 5s
fi

/usr/bin/mysqld_safe --user=root

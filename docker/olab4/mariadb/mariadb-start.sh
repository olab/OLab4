#!/usr/bin/env bash

if [ ! -f /var/lib/mysql/ibdata1 ]; then

	/usr/bin/mysql_install_db

	/usr/bin/mysqld_safe &
	sleep 5s

	/usr/bin/mysqladmin -u root password password

  touch /tmp/entrada.sql.started
	/usr/bin/mysql -uroot -ppassword < /tmp/entrada.sql > /tmp/entrada.out
  touch /tmp/entrada.sql.finished

  touch /tmp/olab.sql.started
	/usr/bin/mysql -uroot -ppassword < /tmp/olab.sql > /tmp/olab.out
  touch /tmp/olab.sql.finished

	pkill mysqld
	sleep 5s
fi

/usr/bin/mysqld_safe

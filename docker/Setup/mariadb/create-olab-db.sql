CREATE DATABASE /*!32312 IF NOT EXISTS*/ `openlabyrinth` CHARACTER SET utf8 COLLATE utf8_general_ci;
GRANT ALL ON `openlabyrinth`.* TO 'entrada'@'%';
GRANT ALL ON `openlabyrinth`.* TO 'entrada'@'localhost';
FLUSH PRIVILEGES;

--SOURCE /var/lib/mysql/entrada_data.sql;
--SOURCE /var/lib/mysql/openlabyrinth_data.sql;


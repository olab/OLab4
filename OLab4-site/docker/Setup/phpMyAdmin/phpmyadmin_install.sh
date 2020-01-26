#!/usr/bin/env bash
if [ ! -f /usr/share/phpmyadmin ]; then

    cd /tmp
    mkdir phpMyAdmin
    wget https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.tar.gz
    tar -zxvf phpMyAdmin-latest-all-languages.tar.gz -C phpMyAdmin --strip-components 1
    rm -f phpMyAdmin-latest-all-languages.tar.gz
    mv phpMyAdmin /usr/share

fi

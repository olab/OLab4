FROM centos:centos7

LABEL maintainer "Matt Simpson <simpson@queensu.ca>"

# You can connect to a bash shell on entrada-developer by typing:
# docker exec -it entrada-developer bash

# Set your specific Timezone
ENV TZ="America/Edmonton"

# Sets Timezone in container.
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime

# Sets Networking in container.
RUN echo "NETWORKING=yes" > /etc/sysconfig/network

# Sets CentOS root password.
RUN echo root:password | chpasswd && yum install -y passwd

# Install Inline with Upstream Stable (IUS), which also enables EPEL.
RUN yum -y install https://centos7.iuscommunity.org/ius-release.rpm
RUN yum -y install https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
RUN yum -y install http://rpms.remirepo.net/enterprise/remi-release-7.rpm
#RUN yum install yum-utils
RUN yum-config-manager --enable remi-php72

# Install Python and Supervisor.
RUN yum -y install python-setuptools
RUN mkdir -p /var/log/supervisor
RUN easy_install supervisor

# Install Entrada Developer dependancies.
RUN yum -y install git \
                htmldoc \
                wkhtmltopdf \
                curl \
                wget \
                unzip \
                vim \
                nano \
                iproute \
                python-setuptools \
                hostname \
                inotify-tools \
                yum-utils \
                which \
                ruby \
                rubygems \
                exim \
                openssl \
                httpd \
                dos2unix \
                mod_ssl \
                php \
                php-mcrypt \
                php-gd \
                php-devel \
                php-mysql \
                php-intl \
                php-mbstring \
                php-pecl-xdebug \
                php-bcmath \
                php-ldap \
                php-imap \
                php-pspell \
                php-soap \
                php-xmlrpc \
                php-tidy \
                php-opcache \
                mariadb-client \
                mariadb-server \
                pecl \
                php-pecl-ds


# Update PHP's configuration
RUN sed -i 's@;date.timezone =@date.timezone = '"$TZ"'@g' /etc/php.ini
RUN sed -i 's@display_errors = Off@display_errors = On@g' /etc/php.ini
RUN sed -i 's@error_reporting = E_ALL \& ~E_DEPRECATED \& ~E_STRICT@error_reporting = E_ALL \& ~E_NOTICE \& ~E_STRICT \& ~E_DEPRECATED@g' /etc/php.ini
RUN sed -i 's@upload_max_filesize = 2M@upload_max_filesize = 512M@g' /etc/php.ini
RUN sed -i 's@post_max_size = 8M@post_max_size = 512M@g' /etc/php.ini
RUN sed -i 's@memory_limit = 128M@memory_limit = 512M@g' /etc/php.ini

#RUN { \
        #echo 'date.timezone = "$TZ"'; \
        #echo 'display_errors = On'; \
        #echo 'error_reporting = E_ALL \& ~E_NOTICE \& ~E_STRICT \& ~E_DEPRECATED'; \
        #echo 'upload_max_filesize = 512M'; \
        #echo 'post_max_size = 512M'; \
        #echo 'memory_limit = 512M'; \
        #echo 'opcache.validate_timestamps=on'; \
        #echo 'opcache.revalidate_freq=0'; \
        #echo 'opcache.enable_cli=0'; \
        #echo 'opcache.enable=0'; \
    #} > /etc/php.d/90-olab.ini

# Set the container volumes.
VOLUME /var/lib/mysql
VOLUME /var/www/vhosts
VOLUME /etc/httpd/vhosts.d

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Tell Apache to include all of the virtual hosts
RUN echo "IncludeOptional vhosts.d/*.conf" >> /etc/httpd/conf/httpd.conf

# install PHP data structures
# RUN pecl install ds

# Install PHPMyAdmin
# RUN yum -y install phpmyadmin
COPY apache/phpmyadmin.conf /etc/httpd/conf.d/phpmyadmin.conf
COPY apache/90-xdebug.ini /etc/php.d
# COPY apache/95-php-ds.ini /etc/php.d
RUN mkdir /etc/phpMyAdmin
COPY phpMyAdmin/config.inc.php /etc/phpMyAdmin/config.inc.php
COPY phpMyAdmin/phpmyadmin_install.sh /usr/bin
RUN chmod +x /usr/bin/phpmyadmin_install.sh
RUN /usr/bin/phpmyadmin_install.sh

# Install Capistrano for deployment recipes.
RUN gem install capistrano -v 2.15.9
RUN gem install colorize
RUN gem install sshkit
RUN gem install gnm-caplock

EXPOSE 80
EXPOSE 443
EXPOSE 3306

# Copy Supervisor configuration.
COPY supervisor/supervisord.conf /etc/supervisord.conf

# Create the MariaDB entrada user account and default databases.
COPY mariadb/entrada.cnf /etc/my.cnf.d
COPY mariadb/create-entrada-db.sql /tmp
COPY mariadb/create-olab-db.sql /tmp

# Run any code set up steps
COPY post-create/post-create.sh /tmp
RUN chmod +x /tmp/post-create.sh 

# Update PHP for XDEBUG
COPY post-create/php.ini /etc/php.d/90-xdebug.ini

COPY mariadb/mariadb-start.sh /usr/bin
RUN chmod +x /usr/bin/mariadb-start.sh

CMD ["/usr/bin/supervisord"]

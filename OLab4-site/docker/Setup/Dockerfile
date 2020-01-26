FROM centos:centos7

LABEL maintainer "Corey Wirun <corey@cardinalcreek.ca>"

# You can connect to a bash shell on entrada-developer by typing:
# docker exec -it olab4-developer bash

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
RUN yum -y install http://rpms.remirepo.net/enterprise/remi-release-7.rpm
RUN yum-config-manager --enable remi-php72

# Install Python and Supervisor.
RUN yum -y install python-setuptools deltarpm python-pip
RUN mkdir -p /var/log/supervisor
#RUN easy_install supervisor
RUN pip install --upgrade pip
RUN pip install supervisor

# Install Entrada Developer dependancies.
RUN yum -y install git \
                pv \
                htmldoc \
                wkhtmltopdf \
                curl \
                wget \
                unzip \
                vim \
                nano \
                iproute \
                python-setuptools \
		file \
                hostname \
                inotify-tools \
		npm \
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
RUN sed -i 's@memory_limit = 128M@memory_limit = 2512M@g' /etc/php.ini
RUN sed -i 's@mysqli.default_socket =@mysqli.default_socket = /var/run/mariadb/mysql.sock@g' /etc/php.ini

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
# RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === 'c5b9b6d368201a9db6f74e2611495f369991b72d9c8cbd3ffbc63edff210eb73d46ffbfce88669ad33695ef77dc76976') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"

# Tell Apache to include all of the virtual hosts
RUN echo "IncludeOptional vhosts.d/*.conf" >> /etc/httpd/conf/httpd.conf

# Install PHPMyAdmin
RUN yum -y install php-pecl-zip phpMyAdmin
COPY apache/phpmyadmin.conf /etc/httpd/conf.d/phpmyadmin.conf
COPY apache/90-xdebug.ini /etc/php.d
COPY apache/hostname /etc/hostname

# Install Capistrano for deployment recipes.
RUN gem install net-ssh -v "<5.0" --no-ri --no-rdoc  
RUN gem install capistrano -v 2.15.9 --no-ri --no-rdoc
RUN gem install colorize --no-ri --no-rdoc
RUN gem install sshkit --no-ri --no-rdoc

EXPOSE 80
EXPOSE 443
EXPOSE 3306

# Copy Supervisor configuration.
COPY supervisor/supervisord.conf /etc/supervisord.conf

# Create the MariaDB entrada user account and default databases.
COPY mariadb/entrada.cnf /etc/my.cnf.d
COPY mariadb/create-entrada-db.sql /tmp
COPY mariadb/create-olab-db.sql /tmp
COPY mariadb/reset-data-db.sh /tmp
COPY mariadb/reset-data-db.sql /tmp
COPY mariadb/dump-data-db.sh /tmp

# Run any code set up steps
COPY post-create/post-create.sh /tmp
RUN chmod +x /tmp/post-create.sh 

COPY mariadb/mariadb-start.sh /usr/bin
RUN chmod +x /usr/bin/mariadb-start.sh

CMD ["/usr/bin/supervisord"]

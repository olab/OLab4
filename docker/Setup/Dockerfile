FROM centos:centos7 as centos

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
RUN echo root:password | chpasswd

# Install Inline with Upstream Stable (IUS), which also enables EPEL.
RUN yum -y install  https://repo.ius.io/ius-release-el7.rpm http://rpms.remirepo.net/enterprise/remi-release-7.rpm && \
	yum-config-manager --enable remi-php72

# Install Python and Supervisor.
# RUN yum -y install python-setuptools deltarpm python-pip
RUN mkdir -p /var/log/supervisor
# RUN pip install --upgrade pip
# RUN pip install supervisor

# Install Entrada Developer dependancies.
RUN yum -y install git \
	curl \
	dos2unix \
	exim \
	file \
	hostname \
	htmldoc \
	httpd \
	inotify-tools \
	iproute \
	mariadb-client \
	mariadb-server \
	mod_ssl \
	nano \
	npm \
	openssl \
	pecl \
	php \
	php-bcmath \
	php-devel \
	php-gd \
	php-imap \
	php-intl \
	php-ldap \
	php-mbstring \
	php-mcrypt \
	php-mysql \
	php-opcache \
	php-pecl-ds \
	php-pecl-xdebug \
	php-pecl-zip \ 
	php-pspell \
	php-soap \
	php-tidy \
	php-xmlrpc \
	phpMyAdmin \
	pv \
	python-setuptools \
	python-setuptools deltarpm python-pip \
	ruby \
	rubygems \
	supervisor \
	unzip \
	vim \
	wget \
	which \
	wkhtmltopdf \
	yum-utils && \
    pip install --upgrade pip

# Update PHP's configuration
RUN sed -i 's@;date.timezone =@date.timezone = '"$TZ"'@g' /etc/php.ini
RUN sed -i 's@display_errors = Off@display_errors = On@g' /etc/php.ini
RUN sed -i 's@error_reporting = E_ALL \& ~E_DEPRECATED \& ~E_STRICT@error_reporting = E_ALL \& ~E_NOTICE \& ~E_STRICT \& ~E_DEPRECATED@g' /etc/php.ini
RUN sed -i 's@upload_max_filesize = 2M@upload_max_filesize = 512M@g' /etc/php.ini
RUN sed -i 's@post_max_size = 8M@post_max_size = 512M@g' /etc/php.ini
RUN sed -i 's@memory_limit = 128M@memory_limit = 2512M@g' /etc/php.ini
RUN sed -i 's@mysqli.default_socket =@mysqli.default_socket = /var/run/mariadb/mysql.sock@g' /etc/php.ini

# Set the container volumes.
VOLUME /var/lib/mysql
VOLUME /var/www/vhosts

# Install Composer
RUN export COMPOSER_INSTALLER_SHA384=$(curl https://composer.github.io/installer.sig) \
  && php -r "readfile('https://getcomposer.org/installer');" > /tmp/composer-setup.php \
  && php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== '${COMPOSER_INSTALLER_SHA384}') { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }"
  
# RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
#RUN php -r "if (hash_file('sha384', 'composer-setup.php') === #'c5b9b6d368201a9db6f74e2611495f369991b72d9c8cbd3ffbc63edff210eb73d46ffbfce88669ad33695ef77dc76976') { echo 'Installer verified'; } else { echo #'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN php -r "unlink('/tmp/composer-setup.php');"

# Tell Apache to include all of the virtual hosts
RUN echo "IncludeOptional vhosts.d/*.conf" >> /etc/httpd/conf/httpd.conf

# Stage any configuration files to container file system
COPY ./root /

RUN yum -y install \
	autoconf \
	automake \
	bison \
	bzip2 \
	curl \
	gcc-c++ \
	git-core \
	libffi-devel \
	libtool \
	libyaml-devel \
	make \
	openssl-devel \
	patch \
	readline \
	readline-devel \
	sqlite-devel \
	zlib \
	zlib-devel && yum clean all

# Setup unprivileged user environmental variables
ENV RBENV_ROOT /root/.rbenv
ENV PATH ${RBENV_ROOT}/bin:${RBENV_ROOT}/shims:$PATH
RUN curl -sL https://github.com/rbenv/rbenv-installer/raw/master/bin/rbenv-installer | bash -

RUN rbenv install 2.5.1 && \
	rbenv global 2.5.1 && \
	ruby -v

# Install Capistrano for deployment recipes.

RUN gem install net-ssh -v "<5.0" --no-ri --no-rdoc  && \
	gem install capistrano -v 2.15.9 --no-ri --no-rdoc && \
	gem install colorize --no-ri --no-rdoc && \
	gem install sshkit --no-ri --no-rdoc

EXPOSE 80
EXPOSE 443
EXPOSE 3306

# Configure the set up step scripts so they can be run
RUN chmod +x /tmp/post-create.sh && chmod +x /usr/bin/mariadb-start.sh

CMD ["/usr/bin/supervisord"]

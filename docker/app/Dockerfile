#FROM php:8.1-apache
ARG TAG=7.4-apache-bullseye
FROM php:${TAG}

# PHP 設定ファイル
# COPY php.ini /etc/php.ini
COPY php.ini /usr/local/etc/php/php.ini

# Composerのインストール
RUN cd /usr/bin && curl -s http://getcomposer.org/installer | php && ln -s /usr/bin/composer.phar /usr/bin/composer

# ミドルウェアインストール
# https://hub.docker.com/_/php/
RUN apt update \
  && apt upgrade -y \
  && apt install -y \
        apt-transport-https \
        libfreetype6-dev \
        libicu-dev \
        apt-utils \
        build-essential \
        debconf-utils \
        libjpeg62-turbo-dev \
        libpng-dev \
        gcc \
        git \
        zip \
        unzip \
        vim \
        gnupg2 \
        libpng-dev \
        libpq-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        wget \
        msmtp \
        msmtp-mta \
        mariadb-client \
        rsync \
        locales \
        ssl-cert \
        zlib1g-dev \
        libwebp-dev \
        gnupg \
        iputils-ping \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) zip gd mysqli pdo pdo_mysql intl\
    && apt upgrade -y ca-certificates \
    && echo "en_US.UTF-8 UTF-8" >/etc/locale.gen \
    && locale-gen

# Google Cloud SDK
RUN curl https://packages.cloud.google.com/apt/doc/apt-key.gpg | gpg --dearmor -o /usr/share/keyrings/cloud.google.gpg
RUN echo "deb [signed-by=/usr/share/keyrings/cloud.google.gpg] https://packages.cloud.google.com/apt cloud-sdk main" | tee -a /etc/apt/sources.list.d/google-cloud-sdk.list
RUN apt-get update && apt-get install -y google-cloud-cli
RUN apt-get install -y google-cloud-cli google-cloud-cli-cloud-run-proxy

# ダウンロードしたファイルを/var/www/scriptに保存
RUN wget https://dl.google.com/cloudsql/cloud_sql_proxy.linux.amd64 -O /usr/local/bin/cloud_sql_proxy

# ダウンロードしたファイルのパーミッションを変更
RUN chmod +x /usr/local/bin/cloud_sql_proxy

# APC
RUN pecl install apcu && echo "extension=apcu.so" > /usr/local/etc/php/conf.d/apc.ini

# Node.js 12.x
RUN curl -sL https://deb.nodesource.com/setup_12.x | bash - \
    && apt update \
    && apt install -y nodejs \
    && apt clean

# modRewriteを有効化する
RUN mv /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled
RUN /bin/sh -c a2enmod rewrite

# タイムゾーン設定
RUN ln -sf  /usr/share/zoneinfo/Asia/Tokyo /etc/localtime

CMD ["apache2-foreground"]
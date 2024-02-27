# performance test

## 環境構築方法
ビルド 

```
docker-compose build
```

起動

```
docker-compose up -d
```

Gcloud SDK 初期設定

```
docker-compose exec app gcloud init

# login 処理
docker-compose exec app gcloud auth login --no-launch-browser

# INSTANCE_NAME=`project:region:instance-name`の形式必要
docker-compose exec app cloud_sql_proxy -instances=INSTANCE_NAME=tcp:0.0.0.0:3306
```

## Gcloud 接続
```
$ docker-compose exec -it app bash

# mysql client から CloudSQLへ 接続確認
# cloud_sql_proxyが起動中であることが必要
root@f761b2f53458:/var/www# mysql -h 0.0.0.0 -u test_user -p

# 以下表示されれば、CloudSQLへの接続問題が正常です
Welcome to the MariaDB monitor.  Commands end with ; or \g.
Your MySQL connection id is 135005
Server version: 8.0.31-google (Google)
```


## 試験用データ

試験データ構成
```
# DATABASE
CREATE DATABASE performance_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE performance_test;

# 上記権限足りないエラー発生したら、以下コマンドを実行
GRANT ALL PRIVILEGES ON *.* TO 'test_user'@'%';
FLUSH PRIVILEGES;

# TABLE
CREATE TABLE test_data (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(500) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

# Sample Data Insert
INSERT INTO test_data (name, email)
SELECT
    CONCAT('test_data_', FLOOR(RAND() * 1000000)),
    CONCAT('test_data_', UUID(), '@', FLOOR(RAND() * 1000000), '.jp');
```

試験データ作成
```text

# CloudSQLへのデータ作成時
vi ./www/script/insert.php
# $host = '0.0.0.0';　接続先をこちらに変更

# LocalSQLへのデータ作成時
vi ./www/script/insert.php
# $host = 'mysql';　接続先をこちらに変更

# 上記接続先設定完了したら、以下コマンド実行
# 以下コマンドを実行し、100万件データinsert
php ./www/script/insert.php

# 以下コマンドで、insertデータ確認
php ./www/script/select.php

```

EC-CUBE構築
※既存eccubeプロジェクトには、gcloud sdkがないため、
gcloud sdk利用できる環境に、既存のeccubeソースを持ってきて、起動できるようにする必要です。

```text
# ソースコピー
cp -r 既存Eccubeソース ./www/html/

# composerなど実行
cd ./www/html/

# Install Symfony Components
composer require symfony/apache-pack
composer require symfony/orm-pack
# composer require --dev symfony/maker-bundle

npm install

# 以下エラー起きます
Executing script cache:clear --no-warmup [KO]
 [KO]
Script cache:clear --no-warmup returned with error code 255
!!
!!  Fatal error: Uncaught Dotenv\Exception\InvalidPathException: Unable to read any of the environment file(s) at [/var/www/html/bin/../.env.install]. in /var/www/html/vendor/vlucas/phpdotenv/src/Store/FileStore.php:68
!!  Stack trace:
!!  #0 /var/www/html/vendor/vlucas/phpdotenv/src/Dotenv.php(222): Dotenv\Store\FileStore->read()
!!  #1 /var/www/html/bin/console(22): Dotenv\Dotenv->load()
!!  #2 {main}
!!    thrown in /var/www/html/vendor/vlucas/phpdotenv/src/Store/FileStore.php on line 68
!!

# .envファイルを作成
# 注意　環境変数などは、やり方によりますが、.envに記載ではなく、別のdocker-compose.ymlに記載することもあります。

# eccube install
php bin/console e:i --no-interaction

# schema-update:
php bin/console cache:clear --no-warmup
php bin/console eccube:generate:proxies
php bin/console doctrine:schema:update --dump-sql
php bin/console doctrine:schema:update --force
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --no-warmup

# init-setup
php bin/console customize:initialize:setup

# plugin-install
# ProductReview42
php bin/console eccube:plugin:install --code="ProductReview42"
php bin/console eccube:plugin:enable --code="ProductReview42"

# GmoPaymentGateway42
php bin/console eccube:plugin:install --code="GmoPaymentGateway42"
php bin/console eccube:plugin:enable --code="GmoPaymentGateway42"

# GmoPsKb4
php bin/console eccube:plugin:install --code="GmoPsKb4"
php bin/console eccube:plugin:enable --code="GmoPsKb4"

# Coupon42
php bin/console eccube:plugin:install --code="Coupon42"
php bin/console eccube:plugin:enable --code="Coupon42"

# MailMagazine42
php bin/console eccube:plugin:install --code="MailMagazine42"
php bin/console eccube:plugin:enable --code="MailMagazine42"

# ProductOption42
php bin/console eccube:plugin:install --code="ProductOption42"
php bin/console eccube:plugin:enable --code="ProductOption42"

```

## Ec-cube動作確認
以下リンクをたたいて、画面表示できればOKです。

```
http://localhost:8080/
```

実行したSQLの取得方法
```shell
# mysql server
tail -f /var/log/mysql/mysqld.log
```


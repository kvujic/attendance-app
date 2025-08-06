# attendance-app

## 概要

ある企業が開発した独自の勤怠管理アプリ

## 環境構築

**Docker ビルド**

1. `git clone git@github.com:kvujic/flea-market-app.git`
2. `cd flea-market-app`
3. DockerDesktop アプリを立ち上げる
4. `docker-compose up -d --build`

> ※ Apple Silicon (M1/M2) を使用している場合、docker-compose.yml の mysql, phpMyAdmin, MailHog などで`platform: linux/amd64` の設定が必要になることがあります。

```bash
mysql:
    platform: linux/amd64
    image: mysql:8.0.26
    environment:
```

**Laravel 環境構築**

1. PHP コンテナに入る

```bash
docker-compose exec php bash
```

2. パッケージをインストール

```bash
composer install
```

3. 「.env.example」ファイルを「.env」ファイルに命名を変更。または、新しく.env ファイルを作成

```bash
cp .env.example .env
```

4. .env に以下の環境変数を設定

```text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```

6. アプリケーションキーの作成

```bash
php artisan key:generate
```

7. マイグレーションの実行

```bash
php artisan migrate
```

8. シーディングの実行

```bash
php artisan db:seed
```

### MailHog（開発用メール確認）

開発環境では、MailHog を使用してメールの送信内容をブラウザ上で確認できます  

1. .env に以下を追記

```text
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

2. ブラウザでアクセス  
   http://localhost:8025
   > 会員登録後に表示されるページ内の「認証はこちらから」をクリックすると上記 URL にアクセスできます
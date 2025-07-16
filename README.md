# coachtech-attendance
## 環境構築
**Dockerビルド**
1. `git clone git@github.com:seiya71/coachtech-attendance.git`
2. `cd coachtech-attendance`
3. `mkdir -p docker/mysql/data`
4. `docker-compose up -d --build`
> *MacのM1・M2チップのPCの場合、`no matching manifest for linux/arm64/v8 in the manifest list entries`のメッセージが表示されビルドができないことがあります。
エラーが発生する場合は、docker-compose.ymlファイルの「mysql」内に「platform」の項目を追加で記載してください*
``` bash
mysql:
    platform: linux/x86_64(この文追加)
    image: mysql:8.0.26
    environment:
```

**Laravel環境構築**
1. `docker-compose exec php composer create-project "laravel/laravel=8.*" . --prefer-dist`
2. `cp src/.env.example src/.env`
3. .envに以下の環境変数を追加
``` text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

STRIPE_PUBLIC_KEY=
STRIPE_SECRET_KEY=

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=8b045afad1fae6
MAIL_PASSWORD=0ef65a6fa989fd
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```
4. キャッシュをクリア
```
docker-compose exec php php artisan config:clear
docker-compose exec php php artisan cache:clear
```
5. アプリケーションキーの作成
``` bash
docker-compose exec php php artisan key:generate
```
6. 権限の調整（エラー防止のため）

Laravelの動作に必要な書き込み権限を付与します：

```bash
docker-compose exec php chmod -R 775 storage
docker-compose exec php chmod -R 775 bootstrap/cache
```

7. `docker-compose exec php php artisan storage:link`

8. マイグレーションの実行
``` bash
docker-compose exec php php artisan migrate
```
9. シーディング用の商品画像の配置場所の作成
``` bash
mkdir -p src/storage/app/public/item_images
```
10. シーディングの実行
``` bash
docker-compose exec php php artisan db:seed
```
**テスト環境構築**
1. `.env.testing.example` を `.env.testing` にコピー
```bash
cp src/.env.testing.example src/.env.testing
```
2.アプリケーションキーの生成
```bash
docker-compose exec php php artisan key:generate --env=testing
```
3.テスト用データベースのマイグレーション
```bash
docker-compose exec php php artisan migrate --env=testing
```
4.テストの実行（必ずこちらのコマンドでテストを実行してください）
```bash
docker-compose exec php php artisan test --env=testing
```
## 使用技術（実行環境）
* nginx 1.21.1
* mysql 8.0.26
* php 7.4.9-fpm
* Laravel Framework 8.83.28
* Fortify
* Storage
* Stripe
* Mailtrap
## ER図
![/ER](/ER.drawio.png)
## URL
- 開発環境：http://localhost/
- phpMyAdmin：http://localhost:8080/
- Mailtrap：https://mailtrap.io/inboxes
# АРМ-Должников

## Разворачивание в Docker

Находясь в корне репозитория, вводим команды в терминале:
````console
cp docker-compose.yml.example docker-compose.yml
cp deploy-config.yml.example deploy-config.yml
cp .env.example .env
cp .env.testing.example .env.testing
````

Правим строки в файле `.env` на:
````console

DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=debtors
DB_USERNAME=root
DB_PASSWORD=password
REDIS_HOST=redis
MAIL_DRIVER=smtp

````

Правим строки в файле `.env.testing` на:
````console

DB_HOST=mariadb_test
DB_PORT=3306
DB_DATABASE=debtors_test
DB_USERNAME=root
DB_PASSWORD=password

````

Собираем контейнеры:
````console
docker compose build
````
Создаем сеть для связи с другими проектами (если ещё нет):
````console
docker network create external-net
````
Запускаем контейнеры
````console
docker compose up -d
````

Разворачиваем проект в контейнере
``````
docker compose exec php composer install
``````
Миграции запускаем не раньше чем секунд через 30 после поднятия контейнера
``````
docker compose exec php php artisan migrate

``````
Далее
``````

docker compose exec php php artisan db:seed
docker compose exec php php artisan key:generate
``````
Правим строку в файле `.env`:
````console
APP_KEY={генерированный ключ}

docker compose exec php php artisan storage:link
``````

Правим `hosts` файл:
* Файл в Windows: `C:\Windows\System32\drivers\etc\hosts`
* Файл в macOS или Linux: `/etc/hosts`

И вставляем:
````console
127.0.0.1 arm_debt.loc
````

Проект развернут и доступен по адресу [http://armf_debt.loc/](http://armf_debt.loc/). Вход доступен с созданного пользователя.

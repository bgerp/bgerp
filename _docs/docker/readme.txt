============================================================
PHP 8.2 DEV CONTAINER (Apache) – LOCAL SETUP INSTRUCTIONS
============================================================

Цел
----
Този Docker контейнер добавя паралелна среда с PHP 8.2 + Apache,
която работи на порт 8082 и използва:
- същия код (ef_root)
- същата MariaDB база на локалната машина

Сегашната ви среда (Apache + PHP 7.4 на порт 80) НЕ СЕ ПИПА.


Предварителни изисквания
------------------------
1) Docker + Docker Compose
   - Linux (Ubuntu):
       sudo apt install docker.io docker-compose-plugin
       sudo usermod -aG docker $USER
       (logout / login)
   - Windows:
       Инсталирайте Docker Desktop (WSL2 backend)

2) Локален Apache + PHP 7.4 + MariaDB вече трябва да работят
   (както досега).


Структура в репото
------------------
В репото има папка:

  docker/
    php82/
      Dockerfile
      php.ini
      vhost.conf
      entrypoint.sh
    docker-compose.yml
    .env.example

!!! ВАЖНО:
Файлът `.env` е персонален за всяка машина и НЕ се комитва.


Стъпка 1: Създай .env файл
--------------------------
Копирай примерния файл:

  cp docker/.env.example docker/.env

Отвори `docker/.env` и попълни ПЪЛНИТЕ пътища
на ТВОЯТА машина.

Пример (Linux):
---------------
EF_ROOT=/home/user/dev/ef_root
WEB_ROOT=/home/user/dev/ef_web
PORT_PHP82=8082
DB_HOST=host.docker.internal
DB_PORT=3306

Пример (Windows):
-----------------
EF_ROOT=C:\dev\ef_root
WEB_ROOT=C:\dev\ef_web
PORT_PHP82=8082
DB_HOST=host.docker.internal
DB_PORT=3306


Обяснение:
----------
EF_ROOT  - основната директория с кода
WEB_ROOT - директорията, която Apache ще сервира (DocumentRoot)
PORT_PHP82 - портът за PHP 8.2 (по подразбиране 8082)


Стъпка 2: Права за писане (ЗАДЪЛЖИТЕЛНО)
---------------------------------------
Контейнерът трябва да може да пише в:

- web/sbf
- uploads

На локалната машина изпълни:

Linux / WSL:
------------
chmod -R a+rwx <WEB_ROOT>/sbf
chmod -R a+rwx <EF_ROOT>/uploads

(само за dev – НЕ за production)


Стъпка 3: MariaDB да приема връзки от контейнера
------------------------------------------------
1) MariaDB трябва да слуша извън localhost.
   В конфигурацията (пример):
     bind-address = 0.0.0.0

2) DB потребителят трябва да има право да се логва не само от localhost:

   GRANT ALL PRIVILEGES ON yourdb.* TO 'youruser'@'%' IDENTIFIED BY 'yourpass';
   FLUSH PRIVILEGES;


Стъпка 4: Стартиране на контейнера
----------------------------------
От директорията `docker/`:

  docker compose build
  docker compose up -d

Провери логовете:
  docker compose logs -f php82


Стъпка 5: Достъп
----------------
PHP 7.4 (старото):
  http://localhost/

PHP 8.2 (Docker):
  http://localhost:8082/


Как работи това
---------------
- Кодът се маунтва от локалната машина
- WEB_ROOT се сервира от Apache в контейнера
- В контейнера има symlink:
      ef_root/web -> WEB_ROOT
  за да работи старият код без промени
- Базата е тази на локалната машина
- PHP 8.2 е с включен E_ALL error reporting


Какво НЕ трябва да правиш
-------------------------
- Не променяй Apache/PHP 7.4 конфигурацията си
- Не пипай порт 80
- Не комитвай файла `.env`


Типичен работен процес
----------------------
1) Работиш както досега (PHP 7.4 на порт 80)
2) Отваряш същата страница на:
      http://localhost:8082/
3) Ако PHP 8.2 даде грешка:
   - оправяш кода
   - тестваш и на 7.4
4) Комитваш в края на деня


Спиране на контейнера
--------------------
docker compose down


Полезни команди
---------------
Влизане в контейнера:
  docker compose exec php82 bash

PHP info:
  docker compose exec php82 php -v
  docker compose exec php82 php -m

Composer:
  docker compose exec php82 composer install


Ако нещо не тръгне
------------------
1) Провери пътищата в .env
2) Провери правата на sbf / uploads
3) Провери MariaDB bind-address
4) Провери логовете:
      docker compose logs -f php82


Край
----
Тази среда е САМО за миграция и дебъгване на PHP 8.2.
Production конфигурации НЕ се засягат.

============================================================
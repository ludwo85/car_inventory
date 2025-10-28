# Car Inventory Manager (Laravel + Vue 3)

Simple project for managing cars and car parts
Uses **Laravel 12**, **Vue 3**, **Bootstrap 5** a **MySQL**.

---

## Installation

```bash
git clone git@github.com:ludwo85/car_inventory.git
cd car_inventory
composer install
npm install
cp .env.example .env
php artisan key:generate
```

set db settings in .env (e.g. mysql)

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=car_inventory
DB_USERNAME=car_user
DB_PASSWORD=secret
```

run migrations
```bash
php artisan migrate
```

run frontend and backend
```bash
npm run dev
php artisan serve
```

Frontend available on: http://127.0.0.1:5173
Backend available on: http://127.0.0.1:8000

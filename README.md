# Talayn Task

A Laravel-based project using PHP 8.3, MySQL, and Redis, built with focus on performance, clean architecture, and scalability.

---

## Requirements

- PHP 8.3
- MySQL 8+
- Composer 2+
- Redis (optional, for cache/queue)
- Node.js 18+ (for frontend assets, if needed)

---

## Steps


```bash
git clone https://github.com/saalid/talayn-task.git
cd talayn-task

composer install

cp .env.example .env

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

php artisan migrate

php artisan db:seed

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

sail up -d

```


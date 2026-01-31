# Установка блога на Nginx

## 1. Установка зависимостей

### Ubuntu/Debian
```bash
sudo apt update
sudo apt install nginx php-fpm php-sqlite3 sqlite3
```

### CentOS/RHEL
```bash
sudo yum install nginx php-fpm php-pdo
```

## 2. Размещение файлов
```bash
# Создание директории
sudo mkdir -p /var/www/html/blog

# Копирование файлов
sudo cp -r /path/to/blog/* /var/www/html/blog/

# Установка прав
sudo chown -R www-data:www-data /var/www/html/blog
sudo chmod -R 755 /var/www/html/blog
sudo chmod -R 777 /var/www/html/blog/db
sudo chmod -R 777 /var/www/html/blog/uploads
```

## 3. Настройка Nginx
```bash
# Копирование конфигурации
sudo cp nginx.conf /etc/nginx/sites-available/blog

# Создание симлинка
sudo ln -s /etc/nginx/sites-available/blog /etc/nginx/sites-enabled/

# Удаление дефолтного сайта (опционально)
sudo rm /etc/nginx/sites-enabled/default

# Проверка конфигурации
sudo nginx -t

# Перезапуск Nginx
sudo systemctl restart nginx
```

## 4. Настройка PHP-FPM

Отредактируйте `/etc/php/8.1/fpm/pool.d/www.conf`:
```ini
user = www-data
group = www-data
listen = /var/run/php/php8.1-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```

Отредактируйте `/etc/php/8.1/fpm/php.ini`:
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M
```

Перезапустите PHP-FPM:
```bash
sudo systemctl restart php8.1-fpm
```

## 5. Настройка SSL (Let's Encrypt)
```bash
# Установка certbot
sudo apt install certbot python3-certbot-nginx

# Получение сертификата
sudo certbot --nginx -d example.com -d www.example.com

# Автообновление
sudo certbot renew --dry-run
```

## 6. Настройка firewall
```bash
# UFW
sudo ufw allow 'Nginx Full'
sudo ufw enable

# или firewalld
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

## 7. Проверка работы

Откройте в браузере:
- http://localhost (или ваш домен)
- Войдите как admin/admin123

## 8. Автозапуск
```bash
sudo systemctl enable nginx
sudo systemctl enable php8.1-fpm
```

## Troubleshooting

### 502 Bad Gateway
```bash
# Проверьте статус PHP-FPM
sudo systemctl status php8.1-fpm

# Проверьте логи
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/php8.1-fpm.log
```

### Ошибки с правами
```bash
# Проверьте владельца файлов
ls -la /var/www/html/blog

# Установите правильные права
sudo chown -R www-data:www-data /var/www/html/blog
```

### База данных не создается
```bash
# Проверьте права на директорию db
sudo chmod 777 /var/www/html/blog/db

# Проверьте наличие модуля SQLite
php -m | grep sqlite
```
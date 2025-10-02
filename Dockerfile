FROM php:8.1-cli

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка рабочей директории
WORKDIR /app

# Копирование composer файлов
COPY composer.json ./

# Установка зависимостей
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Копирование остального кода
COPY . .

# Команда по умолчанию - запуск тестов
CMD ["vendor/bin/phpunit"]

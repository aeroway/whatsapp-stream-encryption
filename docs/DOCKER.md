# Запуск проекта в Docker

## Требования

- Docker
- Docker Compose

## Быстрый старт

### 1. Сборка и запуск контейнера

```bash
docker-compose up -d --build
```

Эта команда:
- Соберет Docker образ с PHP 8.1 и Composer
- Установит все зависимости проекта
- Запустит контейнер в фоновом режиме

### 2. Запуск тестов

```bash
docker-compose exec php vendor/bin/phpunit
```

Или на Windows:

```bash
docker-compose exec php vendor/bin/phpunit
```

### 3. Просмотр результатов

Вы увидите результаты выполнения тестов прямо в терминале:

```
PHPUnit 9.5.x by Sebastian Bergmann and contributors.

.........                                                           9 / 9 (100%)

Time: 00:00.123, Memory: 8.00 MB

OK (9 tests, X assertions)
```

## Дополнительные команды

### Запуск всех тестов с подробным выводом

```bash
docker-compose exec php vendor/bin/phpunit --verbose
```

### Запуск конкретного теста

```bash
docker-compose exec php vendor/bin/phpunit tests/EncryptionDecryptionTest.php
```

### Вход в контейнер (интерактивный режим)

```bash
docker-compose exec php bash
```

Внутри контейнера вы можете выполнять любые команды:

```bash
# Запуск тестов
vendor/bin/phpunit

# Проверка версии PHP
php -v

# Просмотр установленных пакетов
composer show

# Обновление зависимостей
composer update
```

### Переустановка зависимостей

```bash
docker-compose exec php composer install
```

### Просмотр логов контейнера

```bash
docker-compose logs -f php
```

### Остановка контейнера

```bash
docker-compose down
```

### Полная очистка (удаление контейнера и образа)

```bash
docker-compose down --rmi all --volumes
```

## Структура Docker конфигурации

### Dockerfile

```dockerfile
FROM php:8.1-cli
# Устанавливает PHP 8.1 CLI
# Добавляет git и unzip для Composer
# Копирует Composer
# Устанавливает зависимости проекта
```

### docker-compose.yml

```yaml
services:
  php:
    build: .                    # Собирает из Dockerfile в текущей директории
    volumes:
      - .:/app                  # Монтирует текущую директорию в /app контейнера
    working_dir: /app           # Устанавливает рабочую директорию
```

## Преимущества использования Docker

✅ **Изолированное окружение** - не нужно устанавливать PHP на хост-систему

✅ **Консистентность** - одинаковая среда на всех машинах

✅ **Быстрое развертывание** - одна команда для запуска

✅ **Легкое тестирование** - изолированная среда для тестов

✅ **Версионирование** - легко переключаться между версиями PHP

## Разработка с Docker

### Live-reload при изменении файлов

Благодаря volume mounting (`- .:/app`), все изменения в коде сразу доступны в контейнере:

1. Редактируйте файлы на хосте
2. Запускайте тесты в контейнере - они увидят изменения

### Пример workflow разработки

```bash
# Запустить контейнер
docker-compose up -d

# Войти в контейнер
docker-compose exec php bash

# Внутри контейнера - разработка и тестирование
vendor/bin/phpunit --filter testImageEncryption
php -r "require 'vendor/autoload.php'; echo 'Test code here';"

# Выйти из контейнера
exit

# Остановить контейнер
docker-compose down
```

## Тестирование разных версий PHP

Чтобы протестировать код на другой версии PHP, измените версию в `Dockerfile`:

```dockerfile
# Вместо php:8.1-cli используйте:
FROM php:7.4-cli   # PHP 7.4
FROM php:8.0-cli   # PHP 8.0
FROM php:8.2-cli   # PHP 8.2
FROM php:8.3-cli   # PHP 8.3
```

Затем пересоберите контейнер:

```bash
docker-compose up -d --build
```

## Continuous Integration (CI)

Docker конфигурация идеально подходит для CI/CD пайплайнов:

### GitHub Actions пример

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run tests in Docker
        run: |
          docker-compose up -d --build
          docker-compose exec -T php vendor/bin/phpunit
```

### GitLab CI пример

```yaml
test:
  image: php:8.1-cli
  script:
    - composer install
    - vendor/bin/phpunit
```

## Troubleshooting

### Проблема: "Cannot connect to Docker daemon"

**Решение:** Убедитесь, что Docker Desktop запущен.

### Проблема: "Port already allocated"

**Решение:** Измените порт в docker-compose.yml или остановите конфликтующий контейнер.

### Проблема: Изменения в коде не видны в контейнере

**Решение:** 
1. Проверьте, что volumes настроен правильно
2. Перезапустите контейнер: `docker-compose restart`

### Проблема: Медленная работа на Windows/Mac

**Решение:** Это известная особенность volume mounting. Для production используйте COPY вместо volumes.

## Оптимизация для production

Для production сборки измените `docker-compose.yml`:

```yaml
services:
  php:
    build:
      context: .
      dockerfile: Dockerfile
    # Убрать volumes для production
    command: vendor/bin/phpunit  # или ваша команда
```

И обновите `Dockerfile`, убрав разделение на два COPY:

```dockerfile
# Копирование всего кода сразу
COPY . .
RUN composer install --no-dev --optimize-autoloader
```

## Полезные команды для отладки

```bash
# Проверка, что контейнер запущен
docker-compose ps

# Проверка использования ресурсов
docker stats whatsapp-stream-encryption

# Просмотр списка файлов в контейнере
docker-compose exec php ls -la

# Проверка установленных PHP расширений
docker-compose exec php php -m

# Запуск PHP интерактивно
docker-compose exec php php -a
```

## Заключение

Docker конфигурация обеспечивает:
- ✅ Быстрое развертывание без установки PHP
- ✅ Изолированное окружение для тестирования
- ✅ Консистентность между разработчиками
- ✅ Готовность к CI/CD интеграции

Для базового использования достаточно двух команд:

```bash
docker-compose up -d --build
docker-compose exec php vendor/bin/phpunit
```

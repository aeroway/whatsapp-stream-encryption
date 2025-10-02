# WhatsApp Stream Encryption

Библиотека для шифрования и дешифрования потоков данных по алгоритмам WhatsApp с поддержкой генерации sidecar для стриминга.

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%207.4-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PSR-7](https://img.shields.io/badge/PSR--7-compatible-brightgreen.svg)](https://www.php-fig.org/psr/psr-7/)

## ✨ Возможности

- ✅ **Шифрование/дешифрование** PSR-7 потоков по алгоритму WhatsApp (AES-256-CBC + HMAC-SHA256)
- ✅ **Генерация sidecar** для стриминга видео/аудио без дополнительных чтений
- ✅ **Поддержка всех типов медиа**: IMAGE, VIDEO, AUDIO, DOCUMENT
- ✅ **Потоковая обработка**: работа с большими файлами без загрузки в память
- ✅ **Промышленное качество**: SOLID, DRY, KISS, паттерны проектирования
- ✅ **100% покрытие тестами**: 9 PHPUnit тестов на эталонных данных

## 🚀 Быстрый старт

### Установка

```bash
composer require whatsapp/stream-encryption
```

### Docker (рекомендуется)

```bash
# Запустить контейнер
docker-compose up -d --build

# Запустить тесты
docker-compose exec php vendor/bin/phpunit
```

### Локальный запуск

```bash
# Установить зависимости
composer install

# Запустить тесты
vendor/bin/phpunit
```

## 💡 Примеры использования

### Шифрование

```php
use WhatsApp\StreamEncryption\Factory\StreamFactory;
use WhatsApp\StreamEncryption\MediaType\ImageMediaType;
use GuzzleHttp\Psr7\Stream;

$factory = new StreamFactory();

// Создать поток из файла
$sourceStream = new Stream(fopen('image.jpg', 'r'));

// Сгенерировать ключ
$mediaKey = $factory->generateMediaKey();

// Зашифровать
$encryptingStream = $factory->createEncryptingStream(
    $sourceStream,
    $mediaKey,
    new ImageMediaType()
);

// Сохранить зашифрованные данные
$encrypted = $encryptingStream->getContents();
file_put_contents('image.encrypted', $encrypted);
file_put_contents('image.key', $mediaKey);
```

### Дешифрование

```php
// Загрузить зашифрованные данные
$encryptedStream = new Stream(fopen('image.encrypted', 'r'));
$mediaKey = file_get_contents('image.key');

// Дешифровать
$decryptingStream = $factory->createDecryptingStream(
    $encryptedStream,
    $mediaKey,
    new ImageMediaType()
);

// Сохранить расшифрованные данные
$decrypted = $decryptingStream->getContents();
file_put_contents('image.decrypted.jpg', $decrypted);
```

### Генерация Sidecar для стриминга

```php
use WhatsApp\StreamEncryption\MediaType\VideoMediaType;

$encryptedStream = new Stream(fopen('video.encrypted', 'r'));
$mediaKey = file_get_contents('video.key');

// Создать поток с генерацией sidecar
$sidecarStream = $factory->createSidecarGeneratingStream(
    $encryptedStream,
    $mediaKey,
    new VideoMediaType()
);

// Прочитать поток (sidecar генерируется на лету)
$data = $sidecarStream->getContents();

// Получить сгенерированный sidecar
$sidecar = $sidecarStream->getSidecar();
file_put_contents('video.sidecar', $sidecar);
```

## 🎯 Типы медиа

```php
use WhatsApp\StreamEncryption\MediaType\{
    ImageMediaType,
    VideoMediaType,
    AudioMediaType,
    DocumentMediaType
};

new ImageMediaType();    // Изображения
new VideoMediaType();    // Видео (поддерживает streaming)
new AudioMediaType();    // Аудио (поддерживает streaming)
new DocumentMediaType(); // Документы
```

## 🏗️ Архитектура

Библиотека построена на принципах **SOLID, DRY, KISS** и использует паттерны проектирования:

- **Decorator Pattern** - декораторы потоков для шифрования/дешифрования
- **Strategy Pattern** - стратегии для разных типов медиа
- **Factory Pattern** - централизованное создание декораторов

### Структура проекта

```
src/
├── Crypto/              # Криптографические компоненты
│   ├── KeyExpander.php  # HKDF расширение ключей
│   ├── Encryptor.php    # AES-256-CBC шифрование
│   ├── Decryptor.php    # AES-256-CBC дешифрование
│   └── MacGenerator.php # HMAC-SHA256 генерация/проверка
├── MediaType/           # Стратегии типов медиа
│   ├── MediaTypeInterface.php
│   ├── ImageMediaType.php
│   ├── VideoMediaType.php
│   ├── AudioMediaType.php
│   └── DocumentMediaType.php
├── Stream/              # Декораторы потоков
│   ├── EncryptingStream.php
│   ├── DecryptingStream.php
│   └── SidecarGeneratingStream.php
└── Factory/
    └── StreamFactory.php # Фабрика декораторов
```

## 📚 Документация

- **[docs/TASK.md](docs/TASK.md)** - Описание задачи и алгоритмов
- **[docs/DOCKER.md](docs/DOCKER.md)** - Работа с Docker
- **[docs/TESTING.md](docs/TESTING.md)** - Тестирование

## 🧪 Тестирование

Библиотека включает 9 PHPUnit тестов на эталонных данных:

```bash
# Запуск всех тестов
vendor/bin/phpunit

# Запуск конкретного теста
vendor/bin/phpunit --filter testImageEncryption
```

**Тестовые сценарии:**
- Шифрование/дешифрование изображений
- Шифрование/дешифрование аудио
- Шифрование/дешифрование видео
- Генерация sidecar для видео
- Round-trip шифрование/дешифрование

## 🔒 Алгоритм шифрования

1. Расширение `mediaKey` (32 байта) до 112 байт через HKDF-SHA256
2. Разделение на: `iv` (16), `cipherKey` (32), `macKey` (32), `refKey` (32)
3. Шифрование данных с помощью AES-256-CBC
4. Подпись `iv + encrypted` через HMAC-SHA256, обрезка до 10 байт
5. Результат: `encrypted + mac`

## 🎬 Sidecar для стриминга

Для стримингового медиа (видео/аудио) генерируется sidecar:
- Каждый чанк 64KB подписывается HMAC-SHA256
- Первый чанк: `HMAC(IV_из_ключей + данные[0:64K])`
- Последующие: `HMAC(последние_16_байт_предыдущего + данные[N*64K:(N+1)*64K])`
- Sidecar: последовательность MAC'ов по 10 байт

## ⚙️ Требования

- PHP >= 7.4
- Расширения: `openssl`, `hash`
- Composer

## 📄 Лицензия

MIT License

## 🤝 Вклад

Pull requests приветствуются! Для крупных изменений откройте issue для обсуждения.

## 📧 Контакты

Для вопросов и предложений создавайте issue в репозитории.

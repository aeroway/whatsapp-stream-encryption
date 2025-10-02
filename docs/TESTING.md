# Инструкция по тестированию

## Требования

- PHP 7.4 или выше
- Composer

## Установка зависимостей

```bash
composer install
```

## Запуск тестов

```bash
vendor/bin/phpunit
```

Или на Windows:

```bash
vendor\bin\phpunit.bat
```

## Ожидаемый результат

Все тесты должны пройти успешно:

- ✅ testImageEncryption - проверка шифрования изображения
- ✅ testImageDecryption - проверка дешифрования изображения
- ✅ testAudioEncryption - проверка шифрования аудио
- ✅ testAudioDecryption - проверка дешифрования аудио
- ✅ testVideoEncryption - проверка шифрования видео
- ✅ testVideoDecryption - проверка дешифрования видео
- ✅ testRoundTrip - проверка полного цикла шифрование/дешифрование
- ✅ testVideoSidecarGeneration - проверка генерации sidecar для видео
- ✅ testSidecarGenerationWithoutFullRead - проверка генерации sidecar при чтении блоками

## Тестовые данные

Тесты используют файлы из папки `samples/`:
- IMAGE.original, IMAGE.encrypted, IMAGE.key
- AUDIO.original, AUDIO.encrypted, AUDIO.key
- VIDEO.original, VIDEO.encrypted, VIDEO.key, VIDEO.sidecar

## Проверка вручную

Если не установлен PHP, можно проверить структуру проекта:

```bash
# Проверка структуры
tree /F

# Проверка наличия всех файлов
dir /S /B src
dir /S /B tests
```

Ожидаемая структура:

```
├── composer.json
├── phpunit.xml
├── .gitignore
├── README.md (оригинальное задание)
├── IMPLEMENTATION.md (документация реализации)
├── TESTING.md (эта инструкция)
├── src/
│   ├── Crypto/
│   │   ├── KeyExpander.php
│   │   ├── Encryptor.php
│   │   ├── Decryptor.php
│   │   └── MacGenerator.php
│   ├── MediaType/
│   │   ├── MediaTypeInterface.php
│   │   ├── ImageMediaType.php
│   │   ├── VideoMediaType.php
│   │   ├── AudioMediaType.php
│   │   └── DocumentMediaType.php
│   ├── Stream/
│   │   ├── EncryptingStream.php
│   │   ├── DecryptingStream.php
│   │   └── SidecarGeneratingStream.php
│   ├── Factory/
│   │   └── StreamFactory.php
│   └── Exception/
│       ├── EncryptionException.php
│       └── DecryptionException.php
├── tests/
│   ├── EncryptionDecryptionTest.php
│   └── SidecarGenerationTest.php
└── samples/
    └── (тестовые файлы)
```

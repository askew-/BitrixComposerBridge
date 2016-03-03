# MediaSoft Bitrix Composer Bridge

Утилита позволяет создовать модули битрикс в форме пакета Composer.

### Для интеграции утилиты необходимо выполнить два простых шага.

* Подключить зависимость
```sh
composer require mediasoft/bitrix-composer-bridge dev-master
```
* И настроить автомотическое выполнение в виде composer-события в вашем `composer.json`
```json
  "scripts": {
    "post-autoload-dump": [
      "MediaSoft\\Bitrix\\Module\\ComposerBridge::installModules"
    ]
  }
```
При каждом обновлении утилита будет спрашивать вас об установке тех или иных модулей.

#### Как сделать из моего модуля пакет Composer

Очень просто!
* Инициализируйте новый пакет композера в пустой директории
* Опишите ваш пакет в `composer.json`
* Положите ваш модуль в эту директорию
* Укажите в блоке `extra` файла `composer.json` код по аналогии с примером
```json
    "extra": {
        "bitrix-module": {
            "name": "sample.module (Название папки вашего модуля в папке bitrix/modules целевого проекта)",
            "path": "src/sample.module/ (Путь до модуля относительно файла composer.json вашего пакета"
        }
    }
```
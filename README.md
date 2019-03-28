# SimplaCMS smtp

Небольшая доработка для корректной отправки писем через SMTP для SimplaCMS.

Обсуждение - [Оффициальный форум поддержки SimplaCMS - Отправка писем через SMTP](http://forum.simplacms.ru/topic/13754-%D0%BE%D1%82%D0%BF%D1%80%D0%B0%D0%B2%D0%BA%D0%B0-%D0%BF%D0%B8%D1%81%D0%B5%D0%BC-%D1%87%D0%B5%D1%80%D0%B5%D0%B7-smtp/)

### Зачем это нужно?
Отправка писем стандартной функцией php mail не гарантирует 100% доставку письма до клиента, не имеет подписи и письмо может легко попасть в спам. Использование SMTP решает эту проблему.

## OldSchool Установка:
* Открываем `/config/config.php`, и копируем от туда код к себе. 
* Настраиваем SMTP в файле `/config/config.php`
  * `phpmailer_enable` - `true/false` - включить или выключить smtp, если выключен отправляет через обычный phpmail
  * `phpmailer_host` - адрес smtp сервера
  * `phpmailer_port` - порт smtp сервера
  * `phpmailer_user` - пользователь (полностью "username@sitename.ru")
  * `phpmailer_password` - пароль от этого пользователя
  * `phpmailer_ssl` - `true/false` - включить SSL
  * `phpmailer_ssl_verify` - `true/false` - выключить проверку SSL (бывает некоторые хостеры блокируют отправку SMTP, эта опция поможет)
  
* Качаем [PHPMailer](https://github.com/PHPMailer/PHPMailer)
* Находим папку `src`, копируем из неё все файлы к себе в проект, в папку `/api/PHPmailer/` (папку PHPmailer надо создать)
* Открываем `/api/Notify.php`, копируем к себе c 1 по 94 строку.

## Тестирование:
* копируем файл _test_mail.php к себе в корневую папку сайта
* меняем почту x404@bk.ru на свою
* запускаем файл по пути `http://sitename/_test_mail.php`

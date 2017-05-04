Поддержка кэширования Битрикс через php-memcached v 1.2
(https://github.com/php-memcached-dev/php-memcached)

Использование:

- local/lib/cacheenginememcached.php положить в /local/lib вашего сайта
- bitrix/.settings_extra.php соотвественно в /bitrix
- В секции 'hosts' указать IP и порт вашего сервера memcached (можно несколько) или в 'socket' указать путь к файлу сокета


Bitrix Cache with php-memcached v 1.2
(https://github.com/php-memcached-dev/php-memcached)

Usage:

- Put the file local/lib/cacheenginememcached.php into the /local/lib directory of your website
- Put the file bitrix/.settings_extra.php into the /bitrix directory 
- You can specify IP and port of your memcached server in the section 'hosts' (you can use several servers) or path to socket in section 'socket'


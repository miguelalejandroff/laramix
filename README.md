# Informix Driver Package for Laravel

`laramix` is an Informix Driver Package for [Laravel Framework](http://laravel.com/) - thanks [@taylorotwell](https://github.com/taylorotwell). `laramix` is an extension of [Illuminate/Database](https://github.com/illuminate/database) that uses either the PDO extension wrapped into the PDO namespace.

## Requirements

- php: `^7.2`
- laravel/framework: `^6.0|^7.0`
- illuminate/support: `^6.0|^7.0`
- illuminate/database: `^6.0|^7.0`
- illuminate/pagination: `^6.0|^7.0`
- illuminate/encryption: `^6.0|^7.0`
- guzzlehttp/guzzle: `^6.0|^7.0`

## Installation

Require this package in the `composer.json` of your laravel project. This will download the requirements package:

```bash
composer require archytech/laramix
```

Once Composer has installed or updated, you need to register Informix Driver. Open up `config/app.php` and find
the `providers` key and add:

```php
'providers' => [
    /*
     * Package Service Providers ...
     */
    Archytech\Laravel\Ifx\InformixServiceProvider::class,
]
```

Finally you need to publish a configuration file by running the following artisan command.

```bash
php artisan vendor:publish
```

This will copy the configuration file to `config/informix.php`

## License

Licensed under the [MIT License](LICENSE).

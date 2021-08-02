# Informix Driver Package for Laravel

`laramix` is an Informix Driver Package for [Laravel Framework](http://laravel.com/) - thanks to [@taylorotwell](https://github.com/taylorotwell). `laramix` is an extension of [Illuminate/Database](https://github.com/illuminate/database) that uses either the PDO extension wrapped into the PDO namespace.

## Requirements

- php: `^7.2`
- laravel/framework: `^6.0|^7.0`
- guzzlehttp/guzzle: `^6.0|^7.0`
- illuminate/support: `^6.0|^7.0`
- illuminate/database: `^6.0|^7.0`
- illuminate/pagination: `^6.0|^7.0`
- illuminate/encryption: `^6.0|^7.0`

## Installation

Require this package in the `composer.json` of your laravel project. This will download the requirements package:

```bash
composer require miguelalejandroff/laramix
```

Once Composer has installed or updated, you need to register Informix Driver. Open up `config/app.php` and find the `providers` key and add:

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
php artisan vendor:publish --provider="Archytech\Laravel\Ifx\InformixServiceProvider"
```

This will copy the configuration file to `config/informix.php`

## Configure `.env` files

Add this configuration to `.env` file in the root of your project:

```dotenv
# Informix #
DB_CONNECTION=informix
DB_IFX_HOST=127.0.0.1
DB_IFX_SERVICE=9188
DB_IFX_DATABASE=laravel
DB_IFX_USERNAME=informix
DB_IFX_PASSWORD=
DB_IFX_SERVER=ol_informix07
DB_IFX_URI=
DB_IFX_TOKEN=
```

## Documentation  and Usage

Visit [Database: Getting Started](https://laravel.com/docs/7.x/database)

## License

Licensed under the [MIT License](LICENSE).

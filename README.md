# Lalamove PHP Logger
Package provides a logger based on Monolog to print logs based on Lalamove's log format.

## Get started
Install it. 
The logger is not on Packagist, so if you want to install it, add the repo to you `composer.json` file:
```php
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:lalamove/logs-php.git"
    }
]
```
Then run `composer require lalamove/logs-php` to install latest version and add it to your dependencies.

## Use the logger
We recommend depency injection, example with Laravel
```php
app()->singleton("Logger", function ($app) {
    $logger = new Lalamove\Logger\Logger(
        "APP_LOGGER",
        [
            "level",
            "message",
            "src_file",
            "src_line",
            "context",
            "time",
            "backtrace",
        ]
    );
    // create the custom handler
    // required to comply with lalamove's log format
    $stream = new Lalamove\Logger\CustomHandler(
        "php://stdout",
        [
            Lalamove\Logger\Logger::INFO,
            Lalamove\Logger\Logger::ERROR,
            Lalamove\Logger\Logger::WARNING,
            Lalamove\Logger\Logger::FATAL,
            Lalamove\Logger\Logger::DEBUG,
        ]
    );
    // add a formatter
    // when developing we recommend using the HumanReadableFormatter
    // else use the Monolog JsonFormatter
    // example: 
    if (getenv("ENV") === "ldev") {
        $formatter = new Lalamove\Logger\HumanReadableFormatter();
        $stream->setFormatter($formatter);
        $logger->pushHandler($stream);
        return $logger;
    }
    $formatter = new Monolog\Formatter\JsonFormatter();
    $stream->setFormatter($formatter);
    $logger->pushHandler($stream);
    return $logger;
});
```
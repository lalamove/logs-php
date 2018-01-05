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

Then later use it this way:
```php
$logger = app()->make("Logger");
$logger->info("Hello world", [ "some" => "context" ]);
```

If you log exceptions, backtrace will be added automatically to the log context,
example:
```php
$e = new \Exception("some exception");
$logger->error($e, [ "foo" => "bar" ]);
/*
2017-12-27T10:27:21.83689928800Z | ERROR | some exception
    message : some exception
    src_file : ./LoggerTest.php
    src_line : 39
    level : ERROR
    time : 2017-12-27T10:27:21.83689928800Z
    context :
        foo : bar

    backtrace : #0 [internal function]: Lalamove\LoggerTest->testLogInfo()
    #1 ./vendor/phpunit/phpunit/src/Framework/TestCase.php(909): ReflectionMethod->invokeArgs(Object(Lalamove\LoggerTest), Array)
    #2 ./vendor/phpunit/phpunit/src/Framework/TestCase.php(768): PHPUnit_Framework_TestCase->runTest()
    #3 ./vendor/phpunit/phpunit/src/Framework/TestResult.php(612): PHPUnit_Framework_TestCase->runBare()
    #4 ./vendor/phpunit/phpunit/src/Framework/TestCase.php(724): PHPUnit_Framework_TestResult->run(Object(Lalamove\LoggerTest))
    #5 ./vendor/phpunit/phpunit/src/Framework/TestSuite.php(722): PHPUnit_Framework_TestCase->run(Object(PHPUnit_Framework_TestResult))
    #6 ./vendor/phpunit/phpunit/src/Framework/TestSuite.php(722): PHPUnit_Framework_TestSuite->run(Object(PHPUnit_Framework_TestResult))
    #7 ./vendor/phpunit/phpunit/src/TextUI/TestRunner.php(440): PHPUnit_Framework_TestSuite->run(Object(PHPUnit_Framework_TestResult))
    #8 ./vendor/phpunit/phpunit/src/TextUI/Command.php(149): PHPUnit_TextUI_TestRunner->doRun(Object(PHPUnit_Framework_TestSuite), Array)
    #9 ./vendor/phpunit/phpunit/src/TextUI/Command.php(100): PHPUnit_TextUI_Command->run(Array, true)
    #10 ./vendor/phpunit/phpunit/phpunit(52): PHPUnit_TextUI_Command::main()
    #11 {main}
*/
```

If the global constant FOOTPRINT is defined, it will be added to the context:
```php
define("FOOTPRINT", "SOMEFOOTPRINT");
$logger->info("foobar");
/*
2017-12-27T10:27:21.83689928800Z | INFO | foobar
    message : foobar
    src_file : ./LoggerTest.php
    src_line : 39
    level : INFO
    time : 2017-12-27T10:27:21.83689928800Z
    context :
        footprint : SOMEFOOTPRINT
*/
```
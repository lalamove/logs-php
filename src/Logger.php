<?php 

namespace Lalamove\Logger;

use Lalamove\Logger\CustomHandler;

use Monolog\Logger as MonoLogger;
use Monolog\Formatter\JsonFormatter;

// Lalamove log formatter
// {
//     "message": "", // string describing what happened
//     "src_file": "", // file path
//     "src_line": "", // line number
//     "fields": {}, // custom field here
//     "level": "", // debug/info/warning/error/fatal
//     "time": "", // ISO8601.nanoseconds+TZ (in node only support precision up to milliseconds)
//     "backtrace": "" // err stack
// }

/**
 * Logger class
 * Custom Lalamove Logger extending MonoLogger to override addRecord method to
 * add fields matching Lalamove format
 */
class Logger extends MonoLogger
{
    const INFO = "INFO";
    const CRITICAL = "CRITICAL";
    const ERROR = "ERROR";
    const WARNING = "WARNING";
    const DEBUG = "DEBUG";
    const FATAL = "FATAL";

    public function __construct(
        $name,
        array $props = array(),
        array $handlers = array(),
        array $processors = array()
    ) {
        parent::__construct($name, $handlers, $processors);
        $this->props = $props;
    }

    /**
     * getCallerContext function
     * Gets the src_file and src_line from the backtrace and assigns it to the context
     *
     * @param array $backtrace
     * @param array $context
     */
    public function getCallerContext(array $backtrace, array $context)
    {
        $caller = $backtrace[0];
        $context = array_merge($context,
            [
                "src_file" => $caller["file"],
                "src_line" => $caller["line"],
            ]
        );

        return $context;
    }

    /**
     * warning function
     * Calls the logger with WARNING level assigning caller context
     *
     * @param string $msg
     * @param array  $context
     */
    public function warning($msg, array $context = array())
    {
        $t = debug_backtrace(true, 2);
        if (!isset($context["src_file"]) || !isset($context["src_line"])) {
            $context = $this->getCallerContext($t, $context);
        }
        return $this->addRecord(self::WARNING, $msg, $context);
    }

    /**
     * error function
     * Calls the logger with ERROR level assigning caller context
     *
     * @param string $msg
     * @param array  $context
     */
    public function error($msg, array $context = array())
    {
        $t = debug_backtrace(true, 2);
        if (!isset($context["src_file"]) || !isset($context["src_line"])) {
            $context = $this->getCallerContext($t, $context);
        }
        return $this->addRecord(self::ERROR, $msg, $context);
    }

    /**
     * fatal function
     * Calls the logger with FATAL level assigning caller context
     *
     * @param string $msg
     * @param array  $context
     */
    public function fatal($msg, array $context = array())
    {
        $t = debug_backtrace(true, 2);
        if (!isset($context["src_file"]) || !isset($context["src_line"])) {
            $context = $this->getCallerContext($t, $context);
        }
        return $this->addRecord(self::FATAL, $msg, $context);
    }

    /**
     * info function
     * Calls the logger with INFO level assigning caller context
     *
     * @param string $msg
     * @param array  $context
     */
    public function info($msg, array $context = array())
    {
        $t = debug_backtrace(true, 2);
        if (!isset($context["src_file"]) || !isset($context["src_line"])) {
            $context = $this->getCallerContext($t, $context);
        }
        return $this->addRecord(self::INFO, $msg, $context);
    }

    /**
     * debug function
     * Calls the logger with DEBUG level assigning caller context
     *
     * @param string $msg
     * @param array  $context
     */
    public function debug($msg, array $context = array())
    {
        $t = debug_backtrace(true, 2);
        if (!isset($context["src_file"]) || !isset($context["src_line"])) {
            $context = $this->getCallerContext($t, $context);
        }
        return $this->addRecord(self::DEBUG, $msg, $context);
    }

    /**
     * addRecord function
     * overrides the addRecord from Monologger to comply to LaLamove's log format
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    public function addRecord($level, $message, array $context = array())
    {

        // check if any handler will handle this message so we can return early and save cycles
        $handlerKey = null;
        reset($this->handlers);
        while ($handler = current($this->handlers)) {
            if ($handler->isHandling(array('level' => $level))) {
                $handlerKey = key($this->handlers);
                break;
            }

            next($this->handlers);
        }

        if (null === $handlerKey) {
            return false;
        }

        if (!static::$timezone) {
            static::$timezone = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }

        $timestamp;
        if (!isset($context["timestamp"])) {
            // php7.1+ always has microseconds enabled, so we do not need this hack
            if ($this->microsecondTimestamps && PHP_VERSION_ID < 70100) {
                $ts = \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)), static::$timezone);
            } else {
                $ts = new \DateTime(null, static::$timezone);
            }
            $ts->setTimezone(static::$timezone);
            $timestamp = $ts->format('Y-m-d\TH:i:s.uZ\Z');
        } else {
            $timestamp = $context["timestamp"];
        }

        $e = 0;
        // checks wether $message is an exception or if it as the desired methods
        if ($message instanceof \Exception ||
            $message instanceof \Throwable ||
            (
                is_object($message) &&
                method_exists($message, "getMessage") &&
                method_exists($message, "getTraceAsString")
            )
            ) {
            $e = $message;
            $message = $e->getMessage();
        }

        $record =
            array(
                'message' => (string) $message,
                'src_file' => "",
                'src_line' => "",
                'level' => $level,
                'time' => $timestamp,
            );

        foreach ($record as $k => $v) {
            if (array_key_exists($k, $context)) {
                $record[$k] = $context[$k];
                unset($context[$k]);
            }
        }

        // if src_file or src_line does not exist in record
        // set it uing backtrace
        if (!isset($record["src_file"]) || !isset($record["src_line"])) {
            $bt = debug_backtrace(false, 1);
            $caller = $bt[0];
            $record["src_file"] = $caller["file"];
            $record["src_line"] = $caller["line"];
        }

        // if footprint is defined globally and not in the context, add it
        if (defined("FOOTPRINT") && !isset($context["footprint"])) {
            $context["footprint"] = FOOTPRINT;
        }
        $record["context"] = $context;

        // place backtrace last in order for readability
        // e.g
        /*
        2017-12-27T10:27:21.83689928800Z | DEBUG | some exception
            message : some exception
            src_file : ./LoggerTest.php
            src_line : 39
            level : DEBUG
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
        if ($e) {
            $record["backtrace"] = $e->getTraceAsString();
        }
        

        foreach ($this->processors as $processor) {
            $record = call_user_func($processor, $record);
        }

        while ($handler = current($this->handlers)) {
            if (true === $handler->handle($record)) {
                break;
            }

            next($this->handlers);
        }

        return true;
    }
}

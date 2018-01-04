<?php 

namespace Lalamove\Logger;

use Monolog\Handler\StreamHandler;

/**
 * CustomHandler class
 * This class is used to override the isHandling method from the default StreamHandler in Monolog
 * The goal is to have different log levels than the default ones in monolog which dont fit
 * Lalamove's format
 */
class CustomHandler extends StreamHandler
{
    /**
     * Going around the monolog level method
     *
     * @param array $record
     *
     * @return bool
     */
    public function isHandling(array $record)
    {
        return (
            is_array($this->level) && in_array($record["level"], $this->level) ||
            is_string($this->level) && $record["level"] === $this->level
        );
    }
}

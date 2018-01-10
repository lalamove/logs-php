<?php 

namespace Lalamove\Logger;

use Monolog\Formatter\NormalizerFormatter;

/**
 * HumanReadableFormatter class
 */
class HumanReadableFormatter extends NormalizerFormatter
{

    // Set up shell colors
    protected $foregroundColors = [
        'black' => '0;30',
        'darkGray' => '1;30',
        'blue' => '0;34',
        'lightBlue' => '1;34',
        'green' => '0;32',
        'lightGreen' => '1;32',
        'cyan' => '0;36',
        'lightCyan' => '1;36',
        'red' => '0;31',
        'lightRed' => '1;31',
        'purple' => '0;35',
        'lightPurple' => '1;35',
        'brown' => '0;33',
        'yellow' => '1;33',
        'lightGray' => '0;37',
        'white' => '1;37',
    ];

    protected $levelColors = [
        'ERROR' => 'red',
        'CRITICAL' => 'red',
        'FATAL' => 'red',
        'WARNING' => 'yellow',
        'DEBUG' => 'cyan',
        'INFO' => 'green',
    ];

    /**
     * colorText function
     * adds CLI colors to a given string
     * if color does not exist for level, returns string without color
     *
     * @param string $str
     * @param string $level must be a valid logging level
     */
    public function colorText($str, $level)
    {
        if (isset($this->levelColors[$level])) {
            return "\033[". $this->foregroundColors[$this->levelColors[$level]]."m". $str . "\033[0m";
        }
        return $str;
    }
    /**
     * Formats a log record.
     *
     * @param array $record  A record to format
     * @param mixed $prepend String to be prepended to logline (used for indentation)
     * @param mixed $level   level in recursion
     *
     * @return mixed The formatted record
     */
    public function format(array $record, $prepend = "  ", $level = "INFO", $indent = 0)
    {
        $str = "";
        if ($indent === 0) {
            $level = $record["level"];
            $message = $record["message"];
            $tmstp = $record["time"];
            $str .= "\n$tmstp | $level | $message";
        }
        foreach ($record as $k => $v) {
            if (!is_array($v) && !is_object($v)) {
                $str .= "\n$prepend$k : $v";
            } else {
                $str .= "\n$prepend$k :";
                // call format on subobj or array prepending with two more spaces
                $str .= $this->format($v, $prepend."  ", $level, $indent + 1);
            }
        }
        // if color exists for log level, add color to text
        $str = $this->colorText($str."\n", $level);
        return $str;
    }
    
    /**
     * Formats a set of log records.
     *
     * @param array $records A set of records to format
     *
     * @return mixed The formatted set of records
     */
    public function formatBatch(array $records)
    {
        $str = "";
        foreach ($records as $rec) {
            $str .= "\n".$this->format($rec);
        }
        return $str;
    }
}

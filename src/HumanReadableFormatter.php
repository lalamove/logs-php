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
     *
     * @return mixed The formatted record
     */
    public function format(array $record)
    {
        $level = $record["level"];
        $message = $record["message"];
        $tmstp = $record["time"];
        $str = "\n$tmstp | $level | $message\n" . self::formatObject($record);
        // if color exists for log level, add color to text
        $str = $this->colorText($str."\n", $level);
        return $str;
    }

    private static function pad(int $size)
    {
        return join('', array_fill(0, $size, '  '));
    }

    private static function formatKeyValue($key, $value, int $indent = 0)
    {
        $padding = self::pad($indent);
        return "$padding$key : $value";
    }

    private static function formatObject(array $record, int $indent = 0)
    {
        $ar = array_map(function ($val, $key) use ($indent) {
            if (!is_array($val) && !is_object($val)) {
                return self::formatKeyValue($key, $val, $indent + 1);
            }
            $padding = self::pad($indent + 1);
            return "$padding$key :\n" . self::formatObject($val, $indent + 1);
        }, $record, array_keys($record));
        return join("\n", $ar);
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

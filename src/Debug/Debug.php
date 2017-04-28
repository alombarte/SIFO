<?php

namespace Sifo\Debug;

use Sifo\Config;

/**
 * Handles the interaction with the application debug.
 */
class Debug
{
    /**
     * Array where all the storage is done.
     *
     * @var array
     */
    private static $storage = array();

    /**
     * @var string Identifier of the current request execution. It will be set from the debug/index.ctrl in order to get it from the Controller in case of a JSON
     *      execution.
     */
    private static $execution_key;

    /**
     * Avoid external construction of class without singleton usage.
     *
     */
    private function __construct()
    {
    }

    /**
     * @static
     *
     * @param mixed $message String, variable or object you want to show in the debug.
     * @param string $type Type of debug you want. Accepted values are [log|error|warn].
     * @param string $display [html|browser_console|alert] Shown in the html debug, the console or as javascript alert.
     *
     * @author Javier Ferrer
     */
    public static function log($message, $type = 'log', $display = 'html')
    {
        $is_object = false;
        if ($display != 'html') {
            if (is_array($message) || is_object($message)) {
                $is_object = true;
                $message = "'" . str_replace("'", "\\'", json_encode($message)) . "'";
            } else {
                $message = "'" . str_replace("'", "\\'", $message) . "'";
            }
        }

        $message_log['type'] = $type;
        $message_log['is_object'] = $is_object;
        $message_log['message'] = $message;

        self::$storage['log_messages'][$display][] = $message_log;
    }

    /**
     * Adds another element to the end of the array.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return int New number of elements in the array.
     */
    public static function push($key, $value)
    {
        if (!isset(self::$storage[$key])) {
            self::$storage[$key] = array();
        }

        if (!is_array(self::$storage[$key])) {
            throw new \UnexpectedValueException('Failed to PUSH an element in the debug because the given key is not an array.');
        }

        return array_push(self::$storage[$key], $value);
    }

    /**
     * Stores the object with the name given in $key and $sub_key.
     *
     * Example: array( $key => array( $subkey => $value ) )
     *
     * @param string $key Name you want to store the value with.
     * @param $sub_key
     * @param mixed $value The object to store in the array.
     * @param boolean $append When true append the value to the end if sub_key exists.
     * @return void
     */
    public static function subSet($key, $sub_key, $value, $append = false)
    {
        if (!isset(self::$storage[$key][$sub_key]) || false == $append) {
            self::$storage[$key][$sub_key] = $value;
        } else {
            self::$storage[$key][$sub_key] = (self::$storage[$key][$sub_key] . $value);
        }
    }

    /**
     * @static Push an element of the array.
     *
     * @param string $key
     *
     * @return mixed Element in the array or null if not exists.
     */
    public static function get($key, $pull = false)
    {
        if (isset(self::$storage[$key])) {
            $value = self::$storage[$key];
            if (true === $pull) {
                unset(self::$storage[$key]);
            }

            return $value;
        }

        return null;
    }

    /**
     * @static Get all information stored in debug.
     * @return array
     */
    public static function getDebugInformation()
    {
        return self::$storage;
    }

    /**
     * Return a error type friendly string.
     *
     * @param $type
     *
     * @return string
     */
    public static function friendlyErrorType($type)
    {
        switch ($type) {
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
        }

        return "";
    }

    public static function setExecutionKey($execution_key)
    {
        self::$execution_key = $execution_key;
    }

    public static function getExecutionKey()
    {
        if (!isset(self::$execution_key)) {
            self::$execution_key = md5(time() . rand());
        }

        return self::$execution_key;
    }
}

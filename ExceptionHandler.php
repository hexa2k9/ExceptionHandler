<?php

/**
 * LICENSE
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Christian Bönning <christian@verloren-im.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

/**
 * Class ExceptionHandler
 */
final class ExceptionHandler
{

    /**
     * @var array The default Settings
     */
    protected static $defaultSettings = array(
        'username'       => null,           // Your Slack Subdomain (e.g. company.slack.com)
        'token'          => null,           // The Slack Integration Token
        'data_path'      => null,           // The Path to store full Exception Traces in
        'webhookChannel' => null,           // Your Slack Channel
        'webhookUser'    => __CLASS__,      // The Username who will post Messages
        'webhookIcon'    => ':ghost:',      // The Icon for the Username (can be :ghost: or an URL)
        'hostname'       => 'localhost',    // The Hostname your Application is running on
        'version'        => '1.0.0',        // Your Application Version
        'env'            => 'production'    // The Applications Environment (e.g. production or development)
    );

    /**
     * @var array The actual Settings after Configuration
     */
    protected static $settings = array();

    /**
     * @var int The intended HTTP Response Status
     */
    protected static $status = 200;

    /**
     * HTTP Status Codes
     * (taken from Slim PHP Framework - thanks Josh)
     *
     * @var array HTTP response codes and messages
     */
    protected static $messages = array(
        // Informational 1xx
        100 => '100 Continue',
        101 => '101 Switching Protocols',
        // Successful 2xx
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        // Redirection 3xx
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 (Unused)',
        307 => '307 Temporary Redirect',
        // Client Error 4xx
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Timeout',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Request Entity Too Large',
        414 => '414 Request-URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Requested Range Not Satisfiable',
        417 => '417 Expectation Failed',
        418 => '418 I\'m a teapot',
        422 => '422 Unprocessable Entity',
        423 => '423 Locked',
        // Server Error 5xx
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported'
    );

    /**
     * @param array $settings
     */
    final public static function configure(array $settings = array())
    {
        self::$settings = array_merge(self::$defaultSettings, $settings);
    }

    /**
     * The Exception Handler
     *
     * @param Exception $exception
     * @param bool      $caught
     *
     * @throws MainbusUndervoltException
     */
    final public static function handleException(Exception $exception, $caught = false)
    {
        if (is_null(self::$settings['token'])
            || is_null(self::$settings['username'])
            || is_null(self::$settings['webhookUser'])
            || is_null(self::$settings['webhookChannel'])
        ) {
            throw new MainbusUndervoltException(
                'Not all required parameters are set. Please configure ' . __CLASS__,
                1394918214
            );
        }

        if (is_null(self::$settings['data_path'])) {
            self::setDataPath();
        }

        $now = time();
        $trace = self::getExceptionTraceAsString($exception);
        $fileName = __FUNCTION__ . '.' . $now . uniqid('.trace.') . '.txt';
        $traceFile = self::$settings['data_path'] . '/' . $fileName;
        file_put_contents($traceFile, $trace);

        $type = $caught === false ? 'uncaught' : 'caught';
        $messageText = sprintf(
            '%s/%s@%s: %s Exception in file %s on line %d (Code: %d - Trace: %s): %s',
            self::$settings['hostname'],
            self::$settings['version'],
            self::$settings['env'],
            $type,
            $exception->getFile(),
            $exception->getLine(),
            $exception->getCode(),
            $fileName,
            $exception->getMessage()
        );

        self::sendSlackMessage($messageText);
        if ($caught === false) {
            self::setResponseBody(
                'Okay, Houston, we\'ve had a problem here. -- Don\'t panic. The Team has been notified.',
                500
            );
        }
    }

    /**
     * The Error Handler
     *
     * Throw new ErrorException in case something happens
     *
     * @param $errorNo
     * @param $errorString
     * @param $errorFile
     * @param $errorLine
     * @param array $errorContext
     *
     * @return bool
     * @throws ErrorException
     */
    final public static function handleError($errorNo, $errorString, $errorFile, $errorLine, array $errorContext)
    {
        if (0 === error_reporting()) {
            return false;
        }

        throw new ErrorException($errorString, 0, $errorNo, $errorFile, $errorLine);
    }

    /**
     * Send a Message to the Slack Channel
     *
     * @param $messageText
     *
     * @return mixed
     */
    final private static function sendSlackMessage($messageText)
    {
        if (!extension_loaded('curl')) {
            self::setResponseBody(
                'Okay, Houston, we\'ve had a problem here. -- The Team could not be notified.',
                500
            );
        }

        $messageText = trim($messageText);
        $url = 'https://' . trim(self::$settings['username']) . '.slack.com/services/hooks/incoming-webhook?token=' . trim(self::$settings['token']) . '&parse=full';
        $payload = array(
            'channel'    => self::$settings['webhookChannel'],
            'username'   => self::$settings['webhookUser'],
            'icon_emoji' => self::$settings['webhookIcon'],
            'text'       => $messageText,
        );
        $fields = 'payload=' . json_encode($payload);

        $ch = curl_init();
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_URL            => $url,
                CURLOPT_POSTFIELDS     => $fields,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT      => __CLASS__ . '::' . __FUNCTION__
            )
        );

        /** @noinspection PhpUnusedLocalVariableInspection */
        $result = curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Set the HTTP Response Status
     * (partially taken from Slim PHP Framework. Thanks Josh)
     *
     * @param $status
     */
    final private static function setStatus($status)
    {
        self::$status = intval($status);

        if (headers_sent() === false) {
            if (strpos(PHP_SAPI, 'cgi') === 0) {
                header(sprintf('Status: %s', self::getMessageForCode($status)));
            } else {
                header(sprintf('HTTP/%s %s', '1.1', self::getMessageForCode($status)));
            }
        }
    }

    /**
     * Set a Header Pair
     *
     * @param $key
     * @param $value
     */
    final private static function setHeader($key, $value)
    {
        // @todo: handle empty/null values
        header(sprintf('%s: %s', trim($key), trim($value)));
    }

    /**
     * Quit Application & Print HTTP Status & Headers
     *
     * @param      $message
     * @param null $status
     */
    final private static function setResponseBody($message, $status = null)
    {
        $message = trim($message);
        self::$status = (is_null($status) || is_null(self::getMessageForCode($status))) ? 500 : $status;

        self::setStatus(self::$status);
        self::setHeader('Content-Type', 'application/json');

        die(json_encode(
            array(
                'status'  => self::$status,
                'message' => $message
            )
        ));
    }

    /**
     * Get message for HTTP status code
     * (taken from Slim PHP Framework. Thanks Josh)
     *
     * @param  int $status
     *
     * @return string|null
     */
    final private static function getMessageForCode($status)
    {
        if (isset(self::$messages[$status])) {
            return self::$messages[$status];
        } else {
            return null;
        }
    }

    /**
     * @param string $datapath
     */
    final private static function setDataPath($datapath = null)
    {
        if (is_dir($datapath)) {
            self::$settings['data_path'] = trim($datapath);
        } else {
            self::$settings['data_path'] = sys_get_temp_dir();
        }
    }

    /**
     * @param $exception
     *
     * @return string
     */
    final private static function getExceptionTraceAsString(Exception $exception)
    {
        $rtn = "";
        $count = 0;
        foreach ($exception->getTrace() as $frame) {
            $args = "";
            if (isset($frame['args'])) {
                $args = array();
                foreach ($frame['args'] as $arg) {
                    if (is_string($arg)) {
                        $args[] = "'" . $arg . "'";
                    } elseif (is_array($arg)) {
                        $args[] = "Array";
                    } elseif (is_null($arg)) {
                        $args[] = 'NULL';
                    } elseif (is_bool($arg)) {
                        $args[] = ($arg) ? "true" : "false";
                    } elseif (is_object($arg)) {
                        $args[] = get_class($arg);
                    } elseif (is_resource($arg)) {
                        $args[] = get_resource_type($arg);
                    } else {
                        $args[] = $arg;
                    }
                }
                $args = join(", ", $args);
            }
            $rtn .= sprintf("#%s %s(%s): %s(%s)\n",
                $count,
                isset($frame['file']) ? $frame['file'] : 'unknown file',
                isset($frame['line']) ? $frame['line'] : 'unknown line',
                (isset($frame['class'])) ? $frame['class'] . $frame['type'] . $frame['function'] : $frame['function'],
                $args);
            $count++;
        }

        return $rtn;
    }
}

/**
 * Class MainbusUndervoltException
 *
 * @package izsmart\Util
 */
final class MainbusUndervoltException extends
    \InvalidArgumentException
{
}

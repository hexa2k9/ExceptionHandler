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
final class ExceptionHandler {

    /**
     * @var string The Slack Username (<username>.slack.com)
     */
    protected static $username = null;

    /**
     * @var string The Slack Integration Token
     */
    protected static $token = null;

    /**
     * @var string The Username posting to our Slack Channel
     */
    protected static $webhookUser = null;

    /**
     * @var string The Channel where our Exception will be posted
     */
    protected static $webhookChannel = null;

    /**
     * @var string The Icon to use. Can either be ':ghost:' (default) or an URL
     */
    protected static $webhookIcon = ':ghost:';

    /**
     * @var string The Hostname your Application is running on
     */
    protected static $hostname = 'localhost';

    /**
     * @var string The Version of your Application
     */
    protected static $version = '1.0.0';

    /**
     * @var string The Environment your Application is running (e.g. 'production' or 'development')
     */
    protected static $env = 'production';

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
     * @param string $token
     */
    final public static function setToken($token = null) {
        self::$token = trim($token);
    }

    /**
     * @param string $username
     */
    final public static function setUsername($username = null) {
        self::$username = trim($username);
    }

    /**
     * @param string $webhookChannel
     */
    final public static function setWebhookChannel($webhookChannel = null) {
        self::$webhookChannel = trim($webhookChannel);
    }

    /**
     * @param string $webhookUser
     */
    final public static function setWebhookUser($webhookUser = null) {
        self::$webhookUser = trim($webhookUser) . '.' . self::$env;
    }

    /**
     * @param string $webhookIcon
     */
    final public static function setWebhookIcon($webhookIcon = ':ghost:') {
        if ($webhookIcon == 'ghost') {
            $webhookIcon = ':ghost:';
        }

        self::$webhookIcon = trim($webhookIcon);
    }

    /**
     * @param string $env
     */
    final public static function setEnv($env = 'production') {
        self::$env = trim($env);
    }

    /**
     * @param string $hostname
     */
    final public static function setHostname($hostname = 'localhost') {
        self::$hostname = trim($hostname);
    }

    /**
     * @param string $version
     */
    final public static function setVersion($version = '1.0.0') {
        self::$version = trim($version);
    }

    /**
     * The Exception Handler
     *
     * @param Exception $exception
     * @param bool      $caught
     *
     * @throws MainbusUndervoltException
     */
    final public static function handleException(Exception $exception, $caught = false) {
        if (is_null(self::$token)
            || is_null(self::$username)
            || is_null(self::$webhookUser)
            || is_null(self::$webhookChannel)
        ) {
            throw new MainbusUndervoltException(
                'Not all required parameters are set. Please configure ' . __CLASS__,
                1394918214
            );
        }

        $type = $caught === false ? 'uncaught' : 'caught';
        $messageText = sprintf(
            '%s/%s@%s: %s Exception in file %s on line %d (Code: %d): %s',
            self::$hostname,
            self::$version,
            self::$env,
            $type,
            $exception->getFile(),
            $exception->getLine(),
            $exception->getCode(),
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
     * Send a Message to the Slack Channel
     *
     * @param $messageText
     *
     * @return mixed
     */
    final private static function sendSlackMessage($messageText) {
        if (!extension_loaded('curl')) {
            self::setResponseBody(
                'Okay, Houston, we\'ve had a problem here. -- The Team could not be notified.',
                500
            );
        }

        $messageText = trim($messageText);
        $url = 'https://' . self::$username . '.slack.com/services/hooks/incoming-webhook?token=' . self::$token . '&parse=full';
        $payload = array(
            'channel'    => self::$webhookChannel,
            'username'   => self::$webhookUser,
            'icon_emoji' => self::$webhookIcon,
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
    final private static function setStatus($status) {
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
    final private static function setHeader($key, $value) {
        // @todo: handle empty/null values
        header(sprintf('%s: %s', trim($key), trim($value)));
    }

    /**
     * Quit Application & Print HTTP Status & Headers
     *
     * @param      $message
     * @param null $status
     */
    final private static function setResponseBody($message, $status = null) {
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
    final private static function getMessageForCode($status) {
        if (isset(self::$messages[$status])) {
            return self::$messages[$status];
        } else {
            return null;
        }
    }
}

/**
 * Class MainbusUndervoltException
 *
 * @package izsmart\Util
 */
final class MainbusUndervoltException extends
    \InvalidArgumentException {
}

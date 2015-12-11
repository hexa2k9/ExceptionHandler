ExceptionHandler
================

A PHP Exception Handler to Post Exceptions to a Slack Channel

## Installation

* make sure your PHP Installation has `curl` loaded
* create a Channel on Slack
* create an Incoming Webook for your Slack Channel & copy the Integration Token.
* add the [Packagist Package](https://packagist.org/packages/hexa2k9/exception-handler) to your `composer.json`
* run `composer update`

## Configuration

You need to configure this Class before you can use it:

```php
ExceptionHandler::configure(
    array(
        'username'       => 'company',                  // Your Slack Subdomain (e.g. company.slack.com)
        'token'          => '<your token>',             // The Slack Integration Token
        'data_path'      => '/tmp',                     // The Path to store full Exception Traces in
        'webhookChannel' => '#exceptions',              // Your Slack Channel
        'webhookUser'    => 'exception',                // The Username who will post Messages
        'webhookIcon'    => ':ghost:',                  // The Icon for the Username (can be :ghost: or an URL)
        'hostname'       => php_uname('n'),             // The Hostname your Application is running on
        'version'        => '1.0.0',                    // Your Application Version
        'env'            => 'production'                // The Applications Environment (e.g. production or development)
    )
);
```

And finally set the Exception Handler:

```php
set_exception_handler(array('\ExceptionHandler', 'handleException'));
```

You will start to get Messages like these in your Channel:

> chrisbookair.local/2.0.81@development: uncaught Exception in file /Users/christian/Code/PhpstormProjects/api-v2/app/Classes/Util/GeneralUtility.php on line 581 (Code: 8 - Trace: handleException.1395606690.trace.532f44a20d0cc.txt): Memcache::connect(): Server 127.0.0.1 (tcp 11211) failed with: Connection refused (61)

ExceptionHandler will quit your current Applications run and returns a `json_encode()`d Message

```json
{
  "status": 500,
  "message": "Okay, Houston, we've had a problem here. -- Don't panic. The Team has been notified."
}
```

## other Usage

By default this ExceptionHandler will care about uncaught Exceptions. If you want to send Slack Messages for Exceptions you handled you can use this like `\ExceptionHandler::handleException($exception, true);` to get notified. ExceptionHandler will not `die()` in this case.

You can even use ExceptionHandler to just send Notifications. This feels a little weird however: `\ExceptionHandler::handleException(new \Exception('I\'m some text to send to Slack.'), true)`;

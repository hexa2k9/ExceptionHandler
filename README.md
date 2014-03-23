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
\ExceptionHandler::setToken('<your_integration_token>');  // The token you've copied before
\ExceptionHandler::setUsername('company');                // Your Slack Subdomain (e.g. company.slack.com)
\ExceptionHandler::setWebhookUser('exception');           // The Username who will post Messages
\ExceptionHandler::setWebhookChannel('#exceptions');      // Your Slack Channel
\ExceptionHandler::setIcon(':ghost:');                    // The Icon for the Username (can be :ghost: or an URL)
\ExceptionHandler::setEnv('production');                  // The Applications Environment (e.g. production or development)
\ExceptionHandler::setHostname(php_uname('n'));           // The Hostname your Application is running on
\ExceptionHandler::setVersion('1.0.0');                   // Your Application Version
\ExceptionHandler::setDataPath('/tmp');                   // Set your Path to store full Exception Traces in
```

And finally set the Exception Handler:

```php
set_exception_handler(array('\ExceptionHandler', 'handleException'));
```

You will start to get Messages like these in your Channel:

> chrisbookair.local/2.0.79@development: uncaught Exception in file /Users/christian/Code/PhpstormProjects/api-v2/app/Classes/Util/GeneralUtility.php on line 519 (Code: 8): Memcache::connect(): Server 127.0.0.1 (tcp 11211) failed with: Connection refused (61)

ExceptionHandler will quit your current Applications run and returns a `json_encode()`d Message

```json
{
  "status": 500,
  "message": "Okay, Houston, we've had a problem here. -- Don't panic. The Team has been notified."
}
```

## other Usage

By default this ExceptionHandler will care about uncaught Exceptions. If you want to send Slack Messages for Exceptions you handled you can use this like `\ExceptionHandler::handleException($exception, true);` to get notified. ExceptionHandler will not `die()` in this case.

You can even use ExdeptionHandler to just send Notifications. This feels a little weired however: `\ExceptionHandler::handleException(new \Exception('I\'m some text to send to Slack.'), true)`;

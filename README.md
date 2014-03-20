ExceptionHandler
================

A PHP Exception Handler to Post Exceptions to a Slack Channel

You needs to configure this Class before you can use it:

* `ExceptionHandler::setToken('<your_integration_token>');`
* `ExceptionHandler::setUsername('<your_subdomain>');`
* `ExceptionHandler::setWebhookUser('<posting_as_username>');`
* `ExceptionHandler::setWebhookChannel('<posting_to_channel>');`
* `ExceptionHandler::setIcon('<your_icon>');`
* `ExceptionHandler::setEnv(APP_environment);`
* `ExceptionHandler::setHostname(APP_host);`
* `ExceptionHandler::setVersion(APP_version);`

And finally set the Exception Handler:

* `set_exception_handler(array('ExceptionHandler', 'handleException'));`
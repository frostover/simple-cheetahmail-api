# simple-cheetahmail-api
Simple PHP Cheetah Mail (Experian Marketing) API

## Requirements
* PHP 5.3+
* [cURL](http://php.net/manual/en/book.curl.php)

## Configure

Make sure to open the `simple-cheetahmail-api.php` and set your username and password to submit requests.

## Example

*Subscribe user to a list*
```php

//Include cheetahmail API
include_once('simple-cheetahmail-api.php');

//Activate the API (login)
CheetahMailAPI::activate();

//Send the request
CheetahMailAPI::addToCampaign('test1234@email.com', '1', 'test', 'test');

```
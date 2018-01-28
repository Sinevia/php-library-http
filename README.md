# PHP Library HTTP
HTTP client

## Background ##

## Installation ##

Install via Composer

```
composer require sinevia/php-library-http
```

Or add the following to your composer file:

```json
   "require": {
      "sinevia/php-library-http": "1.0.2"
   },
```

## Usage ##

The lines bellow create an HTTP Client:

```php
$http = new \Sinevia\HttpClent("http://localhost/");

$http->setPath('/yourpath/');

$http->post(array('user'=>'UN','pass'=>'PW'); // Data to be sent as array

echo $http->getResponseBody();
```

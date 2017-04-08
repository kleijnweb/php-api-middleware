# KleijnWeb\PhpApi\Middleware 
[![Build Status](https://travis-ci.org/kleijnweb/php-api-middleware.svg?branch=master)](https://travis-ci.org/kleijnweb/php-api-middleware)
[![Coverage Status](https://coveralls.io/repos/github/kleijnweb/php-api-middleware/badge.svg?branch=master)](https://coveralls.io/github/kleijnweb/php-api-middleware?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kleijnweb/php-api-middleware/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kleijnweb/php-api-middleware/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/kleijnweb/php-api-middleware/v/stable)](https://packagist.org/packages/kleijnweb/php-api-middleware)

Middleware for kleijnweb/php-api-descriptions.

Example:

```php
require __DIR__.'/../vendor/autoload.php';

use Doctrine\Common\Cache\ApcuCache;
use KleijnWeb\PhpApi\Descriptions\Description\Repository;
use KleijnWeb\PhpApi\Middleware\DefaultPipe;
use KleijnWeb\PhpApi\Middleware\Tests\Functional\Pet;
use Middlewares\Utils\Factory;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;

$cache = new ApcuCache();

$commands = [
    '/pets/{id}:get' => function (int $id) use ($cache): Pet {
        return unserialize($cache->fetch($id));
    },
    '/pets:post'     => function (Pet $pet) use ($cache) {
        $count = $cache->fetch('count');
        $pet->setId($id = $count + 1);
        $cache->save($id, serialize($pet));
        $cache->save('count', $id);

        return $pet;
    },
];

$repository = new Repository(null, $cache);
$repository->register(__DIR__.'/../tests/Functional/petstore.yml');

$pipe = new DefaultPipe(
    $repository,
    $commands,
    ['KleijnWeb\PhpApi\Middleware\Tests\Functional']
);

$request = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

$response = $pipe->dispatch($request, function () {
    return Factory::createResponse(500);
});

(new SapiEmitter())->emit($response);
```
   
Verify that it works properly by starting a dev server and issuing some cURL requests:

```bash
php -S localhost:1234 test.php
curl -vH "Content-Type: application/json" -d '{"name":"doggie"}' http://localhost:1234/pets
*   Trying 127.0.0.1...
* Connected to localhost (127.0.0.1) port 1234 (#0)
> POST /pets HTTP/1.1
> Host: localhost:1234
> User-Agent: curl/7.47.0
> Accept: */*
> Content-Type: application/json
> Content-Length: 17
> 
* upload completely sent off: 17 out of 17 bytes
< HTTP/1.1 200 OK
< Host: localhost:1234
< Connection: close
< X-Powered-By: PHP/7.0.15-0ubuntu0.16.04.4
< Content-Type: application/json
< Content-Length: 24
< 
* Closing connection 0
{"id":1,"name":"doggie"}

$ curl -vH "Content-Type: application/json" -d '{}' http://localhost:1234/pets
*   Trying 127.0.0.1...
* Connected to localhost (127.0.0.1) port 1234 (#0)
> POST /pets HTTP/1.1
> Host: localhost:1234
> User-Agent: curl/7.47.0
> Accept: */*
> Content-Type: application/json
> Content-Length: 2
> 
* upload completely sent off: 2 out of 2 bytes
< HTTP/1.1 400 Bad Request
< Host: localhost:1234
< Connection: close
< X-Powered-By: PHP/7.0.15-0ubuntu0.16.04.4
< Content-Type: application/json
< Content-Length: 55
< 
* Closing connection 0
{"errors":{"pet.name":"The property name is required"}}

 curl -v  http://localhost:1234/pets/1
*   Trying 127.0.0.1...
* Connected to localhost (127.0.0.1) port 1234 (#0)
> GET /pets/1 HTTP/1.1
> Host: localhost:1234
> User-Agent: curl/7.47.0
> Accept: */*
> 
< HTTP/1.1 200 OK
< Host: localhost:1234
< Connection: close
< X-Powered-By: PHP/7.0.15-0ubuntu0.16.04.4
< Content-Type: application/json
< Content-Length: 24
< 
* Closing connection 0
{"id":1,"name":"doggie"}
```

# Contributing

Pull requests are *very* welcome, but the code has to be PSR2 compliant, follow used conventions concerning parameter and return type declarations, and the coverage can not go down. 

## License

KleijnWeb\PhpApi\Middleware is made available under the terms of the [LGPL, version 3.0](https://spdx.org/licenses/LGPL-3.0.html#licenseText).

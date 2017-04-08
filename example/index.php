<?php
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

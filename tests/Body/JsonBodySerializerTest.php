<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Middleware\Tests;

use KleijnWeb\PhpApi\Middleware\Body\JsonBodyParser;
use KleijnWeb\PhpApi\Middleware\Body\JsonBodySerializer;
use KleijnWeb\PhpApi\Middleware\Body\JsonException;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class JsonBodySerializerTest extends TestCase
{
    /**
     * @var JsonBodySerializer
     */
    private $serializer;

    protected function setUp()
    {
        $this->serializer = new JsonBodySerializer();
    }

    public function testCanDecode()
    {
        $result = $this->serializer->serialize((object)['id' => 1]);
        self::assertEquals('{"id":1}', $result);
    }

    public function testWillThrowJsonExceptionWhenNotEncodable()
    {
        self::expectException(JsonException::class);
        self::expectExceptionMessage('Malformed UTF-8 characters');

        $this->serializer->serialize("\xB1\x31");
    }

    public function testWillReturnContentType()
    {
        self::assertSame('application/json', $this->serializer->getContentType());
    }
}

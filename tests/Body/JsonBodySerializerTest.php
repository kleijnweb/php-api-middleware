<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Middleware\Tests;

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

    /**
     * @test
     */
    public function canDecode()
    {
        $result = $this->serializer->serialize((object)['id' => 1]);
        $this->assertEquals('{"id":1}', $result);
    }

    /**
     * @test
     */
    public function willThrowJsonExceptionWhenNotEncodable()
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionCode(JSON_ERROR_UTF8);

        $this->serializer->serialize("\xB1\x31");
    }

    /**
     * @test
     */
    public function willReturnContentType()
    {
        $this->assertSame('application/json', $this->serializer->getContentType());
    }
}

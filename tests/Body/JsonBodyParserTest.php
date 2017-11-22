<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Middleware\Tests;

use KleijnWeb\PhpApi\Middleware\Body\JsonBodyParser;
use KleijnWeb\PhpApi\Middleware\Body\JsonException;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class JsonBodyParserTest extends TestCase
{
    /**
     * @var JsonBodyParser
     */
    private $parser;

    protected function setUp()
    {
        $this->parser = new JsonBodyParser();
    }

    /**
     * @test
     */
    public function canDecode()
    {
        $result = $this->parser->parse('{ "id": 1 }');
        $this->assertEquals((object)['id' => 1], $result);
    }

    /**
     * @test
     */
    public function willThrowJsonExceptionWhenNotDecodable()
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionCode(JSON_ERROR_SYNTAX);

        $this->parser->parse('not json');
    }
}

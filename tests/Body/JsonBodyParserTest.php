<?php declare(strict_types = 1);
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

    public function testCanDecode()
    {
        $result = $this->parser->parse('{ "id": 1 }');
        self::assertEquals((object)['id' => 1], $result);
    }

    public function testWillThrowJsonExceptionWhenNotDecodable()
    {
        self::expectException(JsonException::class);
        self::expectExceptionMessage('Syntax error');

        $this->parser->parse('not json');
    }
}

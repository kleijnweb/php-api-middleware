<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Middleware\Body;

class JsonBodyParser implements BodyParser
{
    /**
     * @param string $body
     * @return mixed
     * @throws JsonException
     */
    public function parse(string $body)
    {
        // Clear json_last_error()
        json_encode(null);

        $parsed = json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException(json_last_error_msg(), json_last_error());
        }

        return $parsed;
    }
}

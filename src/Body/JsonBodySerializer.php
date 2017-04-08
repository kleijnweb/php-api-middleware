<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Middleware\Body;

class JsonBodySerializer implements BodySerializer
{
    /**
     * @param mixed $body
     * @return mixed
     * @throws JsonException
     */
    public function serialize($body): string
    {
        // Clear json_last_error()
        json_encode(null);

        $string = json_encode($body);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new JsonException(json_last_error_msg());
        }

        return $string;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return 'application/json';
    }
}
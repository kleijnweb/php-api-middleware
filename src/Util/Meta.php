<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\PhpApi\Middleware\Util;

use KleijnWeb\PhpApi\Descriptions\Description\Description;
use KleijnWeb\PhpApi\Descriptions\Description\Operation;
use KleijnWeb\PhpApi\Descriptions\Description\Path;
use Psr\Http\Message\ServerRequestInterface;

class Meta
{
    const NAME = 'php-api.attribute';

    /**
     * @var Description
     */
    private $description;

    /**
     * @var Operation
     */
    private $operation;

    /**
     * @var Path
     */
    private $path;

    /**
     * @param Description $description
     * @param Operation   $operation
     * @param Path        $path
     */
    public function __construct(Description $description, Operation $operation, Path $path)
    {
        $this->description = $description;
        $this->operation   = $operation;
        $this->path        = $path;
    }

    /**
     * @param ServerRequestInterface $request
     * @return Meta
     */
    public static function getFromRequest(ServerRequestInterface $request): Meta
    {
        return $request->getAttribute(self::NAME);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Description            $description
     * @param Operation              $operation
     * @param Path                   $path
     * @return ServerRequestInterface
     */
    public static function requestWith(
        ServerRequestInterface $request,
        Description $description,
        Operation $operation,
        Path $path
    ): ServerRequestInterface
    {
        return $request->withAttribute(self::NAME, new self($description, $operation, $path));
    }

    /**
     * @return Description
     */
    public function getDescription(): Description
    {
        return $this->description;
    }

    /**
     * @return Operation
     */
    public function getOperation(): Operation
    {
        return $this->operation;
    }

    /**
     * @return Path
     */
    public function getPath(): Path
    {
        return $this->path;
    }
}
<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Descriptions package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Middleware\Tests\Functional;

class Pet
{
    private $id;
    private $name;

    public function setId($id)
    {
        $this->id = $id;
    }
}

<?php
/*
 * This file is part of Aplus Framework Autoload Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Autoload\Debug;

use Framework\Autoload\Debug\AutoloadCollection;
use PHPUnit\Framework\TestCase;

final class AutoloadCollectionTest extends TestCase
{
    protected AutoloadCollection $collection;

    protected function setUp() : void
    {
        $this->collection = new AutoloadCollection('Autoload');
    }

    public function testIcon() : void
    {
        self::assertStringStartsWith('<svg ', $this->collection->getIcon());
    }
}

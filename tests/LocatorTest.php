<?php
/*
 * This file is part of The Framework Autoload Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Autoload;

use Framework\Autoload\Autoloader;
use Framework\Autoload\Locator;
use PHPUnit\Framework\TestCase;

class LocatorTest extends TestCase
{
    protected Autoloader $autoloader;
    protected Locator $locator;

    public function setUp() : void
    {
        $this->autoloader = new Autoloader();
        $this->locator = new Locator($this->autoloader);
    }

    public function testListFiles() : void
    {
        $this->assertNull($this->locator->listFiles(__DIR__ . '/unknown'));
        $list = [
            __DIR__ . '/AutoloaderTest.php',
            __FILE__,
            __DIR__ . '/support/NamespacedClass.php',
            __DIR__ . '/support/NoClass.php',
            __DIR__ . '/support/OneClass.php',
        ];
        $this->assertEquals($list, $this->locator->listFiles(__DIR__));
        $this->assertEquals($list, $this->locator->listFiles(__DIR__ . '/../tests'));
    }

    public function testGetClass() : void
    {
        $this->assertEquals(__CLASS__, $this->locator->getClassName(__FILE__));
        $this->assertNull($this->locator->getClassName(__DIR__ . '/unknown'));
        $this->assertEquals(
            'Foo\NamespacedClass',
            $this->locator->getClassName(__DIR__ . '/support/NamespacedClass.php')
        );
        $this->assertNull($this->locator->getClassName(__DIR__ . '/support/NoClass.php'));
        $this->assertEquals(
            'OneClass',
            $this->locator->getClassName(__DIR__ . '/support/OneClass.php')
        );
    }

    public function testGetFiles() : void
    {
        $this->autoloader->setNamespace('Autoload', __DIR__ . '/..');
        $this->assertEquals(
            [
                __DIR__ . '/AutoloaderTest.php',
                __FILE__,
                __DIR__ . '/support/NamespacedClass.php',
                __DIR__ . '/support/NoClass.php',
                __DIR__ . '/support/OneClass.php',
            ],
            $this->locator->getFiles('tests')
        );
    }

    public function testFindFiles() : void
    {
        $this->autoloader->setNamespace('Tests', __DIR__);
        $this->assertEquals(
            [
                __DIR__ . '/LocatorTest.php',
            ],
            $this->locator->findFiles('LocatorTest')
        );
        $this->assertEquals(
            [
                __DIR__ . '/LocatorTest.php',
            ],
            $this->locator->findFiles('LocatorTest', '.php')
        );
        $this->assertEmpty($this->locator->findFiles('LocatorFoo'));
        $this->assertEmpty($this->locator->findFiles('LocatorFoo', '.php'));
        $this->assertEmpty($this->locator->findFiles('LocatorTest', '.py'));
    }

    public function testNamespacedFilepath() : void
    {
        $this->autoloader->setNamespace('Tests\Foo', __DIR__);
        $this->assertEquals(
            __FILE__,
            $this->locator->getNamespacedFilepath('Tests/Foo/LocatorTest')
        );
        $this->autoloader->setNamespace('Tests', __DIR__);
        $this->assertEquals(
            __FILE__,
            $this->locator->getNamespacedFilepath('Tests/LocatorTest')
        );
        $this->assertNull(
            $this->locator->getNamespacedFilepath('LocatorTest')
        );
    }
}

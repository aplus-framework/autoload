<?php
/*
 * This file is part of Aplus Framework Autoload Library.
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

final class LocatorTest extends TestCase
{
    protected Autoloader $autoloader;
    protected Locator $locator;

    public function setUp() : void
    {
        $this->autoloader = new Autoloader();
        $this->locator = new Locator($this->autoloader);
    }

    /**
     * @return array<int,string>
     */
    protected function getFiles() : array
    {
        return [
            __DIR__ . '/AutoloaderTest.php',
            __DIR__ . '/Debug/AutoloadCollectorTest.php',
            __FILE__,
            __DIR__ . '/PreloaderTest.php',
            __DIR__ . '/support/NamespacedClass.php',
            __DIR__ . '/support/NoClass.php',
            __DIR__ . '/support/OneClass.php',
            __DIR__ . '/support/OneEnum.php',
            __DIR__ . '/support/OneInterface.php',
            __DIR__ . '/support/OneTrait.php',
            __DIR__ . '/support/ReturnObject.php',
            __DIR__ . '/support/foo.txt',
        ];
    }

    public function testListFiles() : void
    {
        self::assertNull($this->locator->listFiles(__DIR__ . '/unknown'));
        $list = $this->getFiles();
        self::assertSame($list, $this->locator->listFiles(__DIR__));
        self::assertSame($list, $this->locator->listFiles(__DIR__ . '/../tests'));
    }

    public function testGetClass() : void
    {
        self::assertSame(__CLASS__, $this->locator->getClassName(__FILE__));
        self::assertNull($this->locator->getClassName(__DIR__ . '/unknown'));
        self::assertSame(
            'Foo\NamespacedClass',
            $this->locator->getClassName(__DIR__ . '/support/NamespacedClass.php')
        );
        self::assertNull($this->locator->getClassName(__DIR__ . '/support/NoClass.php'));
        self::assertNull($this->locator->getClassName(__DIR__ . '/support/ReturnObject.php'));
        self::assertSame(
            'OneClass',
            $this->locator->getClassName(__DIR__ . '/support/OneClass.php')
        );
        self::assertSame(
            'OneInterface',
            $this->locator->getClassName(__DIR__ . '/support/OneInterface.php')
        );
        self::assertSame(
            'Foo\Bar\Baz\OneTrait',
            $this->locator->getClassName(__DIR__ . '/support/OneTrait.php')
        );
        self::assertSame(
            'Foo\Bar\Baz\OneEnum',
            $this->locator->getClassName(__DIR__ . '/support/OneEnum.php')
        );
    }

    public function testGetFiles() : void
    {
        $this->autoloader->setNamespace('Autoload', __DIR__ . '/..');
        self::assertSame(
            $this->getFiles(),
            $this->locator->getFiles('tests')
        );
    }

    public function testFindFiles() : void
    {
        $this->autoloader->setNamespace('Tests', __DIR__);
        self::assertSame(
            [
                __DIR__ . '/LocatorTest.php',
            ],
            $this->locator->findFiles('LocatorTest')
        );
        self::assertSame(
            [
                __DIR__ . '/LocatorTest.php',
            ],
            $this->locator->findFiles('LocatorTest', '.php')
        );
        self::assertEmpty($this->locator->findFiles('LocatorFoo'));
        self::assertEmpty($this->locator->findFiles('LocatorFoo', '.php'));
        self::assertEmpty($this->locator->findFiles('LocatorTest', '.py'));
    }

    public function testNamespacedFilepath() : void
    {
        $this->autoloader->setNamespace('Tests\Foo', __DIR__);
        self::assertSame(
            __FILE__,
            $this->locator->getNamespacedFilepath('Tests/Foo/LocatorTest')
        );
        $this->autoloader->setNamespace('Tests', __DIR__);
        self::assertSame(
            __FILE__,
            $this->locator->getNamespacedFilepath('Tests/LocatorTest')
        );
        self::assertNull(
            $this->locator->getNamespacedFilepath('Tests/Foo')
        );
        self::assertNull(
            $this->locator->getNamespacedFilepath('LocatorTest')
        );
    }
}

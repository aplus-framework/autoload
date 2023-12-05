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
use PHPUnit\Framework\TestCase;

final class AutoloaderTest extends TestCase
{
    protected Autoloader $autoloader;

    public function setUp() : void
    {
        $this->autoloader = new Autoloader();
    }

    public function testNamespaces() : void
    {
        self::assertEmpty($this->autoloader->getNamespaces());
        $this->autoloader->setNamespaces([
            'Foo\Bar\\' => [__DIR__, __DIR__],
            'Tests' => __DIR__,
        ]);
        $dir = __DIR__ . \DIRECTORY_SEPARATOR;
        self::assertSame([$dir], $this->autoloader->getNamespace('Tests'));
        self::assertEmpty($this->autoloader->getNamespace('Testss'));
        self::assertSame([
            'Tests' => [$dir],
            'Foo\Bar' => [$dir, $dir],
        ], $this->autoloader->getNamespaces());
        $this->autoloader->removeNamespaces(['Tests']);
        self::assertSame([
            'Foo\Bar' => [$dir, $dir],
        ], $this->autoloader->getNamespaces());
        $this->autoloader->addNamespaces([
            '\Foo\Bar' => [__DIR__],
        ]);
        self::assertSame([
            'Foo\Bar' => [$dir, $dir, $dir],
        ], $this->autoloader->getNamespaces());
    }

    public function testClasses() : void
    {
        $this->autoloader->setClasses([
            '\\' . __CLASS__ => __FILE__,
            '\Tests\LocatorTest' => __DIR__ . '/LocatorTest.php',
        ]);
        self::assertSame(
            [
                __CLASS__ => __FILE__,
                'Tests\LocatorTest' => __DIR__ . '/LocatorTest.php',
            ],
            $this->autoloader->getClasses()
        );
        $this->autoloader->removeClasses([__CLASS__]);
        self::assertSame(
            [
                'Tests\LocatorTest' => __DIR__ . '/LocatorTest.php',
            ],
            $this->autoloader->getClasses()
        );
    }

    public function testFindClassPath() : void
    {
        self::assertNull($this->autoloader->findClassPath(__CLASS__));
        $this->autoloader->setNamespace(__NAMESPACE__, __DIR__);
        self::assertSame(__FILE__, $this->autoloader->findClassPath(__CLASS__));
        $this->autoloader->setClass(__CLASS__, __FILE__);
        self::assertSame(__FILE__, $this->autoloader->findClassPath(__CLASS__));
    }

    public function testLoadClass() : void
    {
        self::assertFalse($this->autoloader->loadClass('Foo\NamespacedClass'));
        $this->autoloader->setNamespace('Foo', __DIR__ . '/support');
        self::assertTrue($this->autoloader->loadClass('Foo\NamespacedClass'));
    }

    public function testPathIsNotFile() : void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Path is not a file: foo');
        $this->autoloader->setClass('Foo', 'foo');
    }

    public function testPathIsNotDirectory() : void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Path is not a directory: foo');
        $this->autoloader->setNamespace('Foo', 'foo');
    }

    public function testNewClass() : void
    {
        $this->autoloader->setNamespace('Foo\NamespacedClass', __DIR__ . '/support');
        self::assertInstanceOf(
            \Foo\NamespacedClass::class,
            new \Foo\NamespacedClass()
        );
    }

    public function testUnregister() : void
    {
        self::assertTrue($this->autoloader->unregister());
    }
}

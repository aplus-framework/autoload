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
use Framework\Autoload\Preloader;
use PHPUnit\Framework\TestCase;

final class PreloaderTest extends TestCase
{
    public function testEmptyListFiles() : void
    {
        $preloader = new Preloader(packagesDir: __DIR__);
        self::assertEmpty($preloader->getAutoloader()->getClasses());
        self::assertEmpty($preloader->listPackagesFiles());
        self::assertEmpty($preloader->listFiles());
        self::assertEmpty($preloader->getAutoloader()->getClasses());
    }

    public function testListFiles() : void
    {
        $autoloader = new Autoloader();
        $autoloader->setNamespace(
            'Foo\Bar\Baz',
            __DIR__ . '/support'
        )->setClass(\OneInterface::class, __DIR__ . '/support/OneInterface.php');
        $preloader = new Preloader($autoloader, __DIR__ . '/../vendor/aplus');
        $packageFiles = $preloader->listPackagesFiles();
        $files = $preloader->listFiles();
        self::assertContains(
            \realpath(__DIR__ . '/../vendor/aplus/debug/src/Debugger.php'),
            $packageFiles
        );
        self::assertContains(
            \realpath(__DIR__ . '/../vendor/aplus/debug/src/Debugger.php'),
            $files
        );
        self::assertNotContains(
            \realpath(__DIR__ . '/../vendor/aplus/coding-standard/src/Config.php'),
            $files
        );
        self::assertNotContains(
            __DIR__ . '/support/OneClass.php',
            $files
        );
        self::assertContains(
            __DIR__ . '/support/OneEnum.php',
            $files
        );
        self::assertContains(
            __DIR__ . '/support/OneInterface.php',
            $files
        );
        $classes = $preloader->getAutoloader()->getClasses();
        self::assertArrayHasKey(
            \Framework\Debug\Debugger::class,
            $classes
        );
        self::assertArrayNotHasKey(
            \Framework\CodingStandard\Config::class,
            $classes
        );
        self::assertArrayHasKey(
            \Foo\Bar\Baz\OneEnum::class,
            $classes
        );
        self::assertArrayHasKey(
            \Foo\Bar\Baz\OneTrait::class,
            $classes
        );
        self::assertArrayNotHasKey(
            \OneClass::class,
            $classes
        );
        self::assertArrayHasKey(
            \OneInterface::class,
            $classes
        );
        self::assertContains(
            $classes[\Framework\Debug\Debugger::class],
            $packageFiles
        );
        self::assertContains(
            $classes[\Framework\Debug\Debugger::class],
            $files
        );
    }

    public function testPackagesDir() : void
    {
        $dir = __DIR__ . '/../vendor/aplus';
        $preloader = new Preloader(packagesDir: null);
        $preloader->setPackagesDir($dir);
        self::assertSame(
            \realpath($dir) . \DIRECTORY_SEPARATOR,
            $preloader->getPackagesDir()
        );
        self::assertEmpty($preloader->listFiles());
        self::assertNotEmpty($preloader->withPackages()->listFiles());
        $classes = $preloader->getAutoloader()->getClasses();
        self::assertContains(
            $classes[\Framework\Debug\Debugger::class],
            $preloader->listFiles()
        );
        self::assertArrayNotHasKey(
            \Framework\CodingStandard\Config::class,
            $classes
        );
        $preloader->withDevPackages()->listFiles();
        $classes = $preloader->getAutoloader()->getClasses();
        self::assertArrayHasKey(
            \Framework\CodingStandard\Config::class,
            $classes
        );
        self::assertContains(
            $classes[\Framework\CodingStandard\Config::class],
            $preloader->withDevPackages()->listFiles()
        );
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid packages dir: /foo/bar');
        $preloader->setPackagesDir('/foo/bar');
    }

    public function testLoad() : void
    {
        $preloader = new Preloader(packagesDir: __DIR__ . '/support');
        self::assertEmpty($preloader->load());
        $preloader->getAutoloader()->setClass(\OneClass::class, __DIR__ . '/support/OneClass.php');
        self::assertContains(
            __DIR__ . '/support/OneClass.php',
            $preloader->load()
        );
    }

    public function testAllDeclarations() : void
    {
        self::assertContains(
            __CLASS__,
            Preloader::getAllDeclarations()
        );
        self::assertContains(
            Preloader::class,
            Preloader::getAllDeclarations()
        );
    }

    public function testDeclarations() : void
    {
        self::assertNotContains(
            __CLASS__,
            Preloader::getDeclarations()
        );
        self::assertContains(
            Preloader::class,
            Preloader::getAllDeclarations()
        );
    }

    public function testIncludedFiles() : void
    {
        self::assertContains(
            __FILE__,
            Preloader::getIncludedFiles()
        );
    }
}

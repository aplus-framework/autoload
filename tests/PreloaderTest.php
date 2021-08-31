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
use Framework\CodingStandard\Config;
use PHPUnit\Framework\TestCase;

/**
 * Class PreloaderTest.
 */
class PreloaderTest extends TestCase
{
    protected Autoloader $autoloader;
    protected PreloaderMock $preloader;

    protected function setUp() : void
    {
        $this->autoloader = new Autoloader();
        $this->preloader = new PreloaderMock($this->autoloader);
    }

    public function testNamespaces() : void
    {
        self::assertNull($this->autoloader->getNamespace('Framework\Helpers'));
        self::assertNull($this->autoloader->getNamespace('Framework\CodingStandard'));
        $this->preloader->setNamespaces();
        $helpersDir = \realpath($this->preloader->packagesDir . 'helpers/src') . '/';
        self::assertSame(
            $helpersDir,
            $this->autoloader->getNamespace('Framework\Helpers')
        );
        self::assertNull($this->autoloader->getNamespace('Framework\CodingStandard'));
        self::assertSame(
            [
                'Framework\Helpers' => $helpersDir,
            ],
            $this->autoloader->getNamespaces()
        );
    }

    public function testIsPreloadable() : void
    {
        self::assertTrue($this->preloader->isPreloadable('Framework\Database\Foo'));
        self::assertTrue($this->preloader->isPreloadable('Framework\HTTP\Foo'));
        self::assertFalse($this->preloader->isPreloadable('Framework\CodingStandard\Foo'));
        self::assertFalse($this->preloader->isPreloadable('Framework\Testing\Foo'));
        self::assertFalse($this->preloader->isPreloadable('Other'));
    }

    public function testLoader() : void
    {
        $className = 'Framework\Helpers\ArraySimple';
        $filepath = \realpath($this->preloader->packagesDir . 'helpers/src/ArraySimple.php');
        self::assertFalse(\class_exists($className, false));
        self::assertNotContains($className, $this->preloader::getDeclarations());
        self::assertNotContains($filepath, $this->preloader::getIncludedFiles());
        $this->preloader->load();
        self::assertTrue(\class_exists($className, false));
        self::assertContains($className, $this->preloader::getDeclarations());
        self::assertContains($filepath, $this->preloader::getIncludedFiles());
        self::assertNotContains(Config::class, $this->preloader::getDeclarations());
    }

    public function testDoNotLoadExternals() : void
    {
        $filepath = $this->preloader->packagesDir . 'helpers/src/Foo.php';
        \file_put_contents($filepath, '<?php class Foo{}');
        self::assertTrue(\is_file($filepath));
        $this->preloader->load();
        self::assertNotContains('Foo', $this->preloader::getAllDeclarations());
        \unlink($filepath);
    }
}

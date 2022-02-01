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

use Framework\Autoload\Autoloader;
use Framework\Autoload\Debug\AutoloadCollector;
use PHPUnit\Framework\TestCase;

class AutoloadCollectorTest extends TestCase
{
    protected AutoloadCollector $collector;
    protected Autoloader $autoloader;

    protected function setUp() : void
    {
        $this->autoloader = new Autoloader();
        $this->autoloader->setDebugCollector();
        $this->collector = $this->autoloader->getDebugCollector();
    }

    public function testAutoloaderNotSet() : void
    {
        $collector = new AutoloadCollector();
        self::assertStringContainsString(
            'An Autoloader instance has not been set',
            $collector->getContents()
        );
    }

    public function testAutoloaderSetDebugCollector() : void
    {
        $collector = new AutoloadCollector();
        $this->autoloader->setDebugCollector($collector);
        self::assertSame($collector, $this->autoloader->getDebugCollector());
    }

    public function testEmptyNamespacesAndClasses() : void
    {
        $contents = $this->collector->getContents();
        self::assertStringContainsString(
            'No namespace directory has been set',
            $contents
        );
        self::assertStringContainsString(
            'No class file has been set',
            $contents
        );
    }

    public function testNamespaces() : void
    {
        $this->autoloader->setNamespace('Foo\Bar', __DIR__);
        $contents = $this->collector->getContents();
        self::assertStringContainsString(__DIR__, $contents);
        self::assertStringContainsString('Foo\Bar', $contents);
    }

    public function testClasses() : void
    {
        $this->autoloader->setClass('Foo', __FILE__);
        $contents = $this->collector->getContents();
        self::assertStringContainsString(__FILE__, $contents);
        self::assertStringContainsString('Foo', $contents);
    }

    public function testLoad() : void
    {
        $this->autoloader->setNamespace('Foo\Bar\Baz', __DIR__ . '/../support');
        $this->autoloader->setClass('OneClass', __DIR__ . '/../support/OneClass.php');
        $class1 = new \OneClass();
        $class2 = new class() {
            use \Foo\Bar\Baz\OneTrait;
        };
        $contents = $this->collector->getContents();
        self::assertStringContainsString('OneClass', $contents);
        self::assertStringContainsString('Foo\Bar\Baz\OneTrait', $contents);
        self::assertSame(
            'Load class OneClass',
            $this->collector->getActivities()[0]['description']
        );
        self::assertSame(
            'Load class Foo\Bar\Baz\OneTrait',
            $this->collector->getActivities()[1]['description']
        );
        foreach ($this->collector->getActivities() as $activity) {
            self::assertSame([
                'collector',
                'class',
                'description',
                'start',
                'end',
            ], \array_keys($activity));
        }
    }

    public function testPreload() : void
    {
        $collector = new class() extends AutoloadCollector {
            protected function getOpcacheConfiguration() : array | null
            {
                return [
                    'directives' => [
                        'opcache.preload' => '/var/www/preload.php',
                        'opcache.preload_user' => 'www-data',
                    ],
                ];
            }
        };
        $this->autoloader->setDebugCollector($collector);
        $contents = $collector->getContents();
        self::assertStringContainsString('/var/www/preload.php', $contents);
        self::assertStringContainsString('www-data', $contents);
    }

    public function testPreloadNotAvailable() : void
    {
        $collector = new class() extends AutoloadCollector {
            protected function getOpcacheConfiguration() : array | null
            {
                return null;
            }
        };
        $this->autoloader->setDebugCollector($collector);
        self::assertStringContainsString(
            'Preload is not available',
            $collector->getContents()
        );
    }

    public function testPreloadNotSet() : void
    {
        $collector = new class() extends AutoloadCollector {
            protected function getOpcacheConfiguration() : array | null
            {
                return [];
            }
        };
        $this->autoloader->setDebugCollector($collector);
        self::assertStringContainsString(
            'Preload file has not been set',
            $collector->getContents()
        );
    }

    public function testDeclarationNotFound() : void
    {
        $collector = new class() extends AutoloadCollector {
            public function getDeclarationType(string $declaration) : string
            {
                return parent::getDeclarationType($declaration);
            }
        };
        self::assertSame('', $collector->getDeclarationType('Unknown'));
    }
}

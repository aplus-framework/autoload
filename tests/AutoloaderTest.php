<?php namespace Tests\Autoload;

use Framework\Autoload\Autoloader;
use PHPUnit\Framework\TestCase;

class AutoloaderTest extends TestCase
{
	/**
	 * @var Autoloader
	 */
	protected $autoloader;

	public function setUp() : void
	{
		$this->autoloader = new Autoloader();
	}

	public function testNamespaces()
	{
		$this->assertEmpty($this->autoloader->getNamespaces());
		$this->autoloader->setNamespaces([
			'Tests' => __DIR__,
			'Foo\Bar\\' => __DIR__,
		]);
		$dir = __DIR__ . \DIRECTORY_SEPARATOR;
		$this->assertEquals($dir, $this->autoloader->getNamespace('Tests'));
		$this->assertNull($this->autoloader->getNamespace('Testss'));
		$this->assertEquals([
			'Tests' => $dir,
			'Foo\Bar' => $dir,
		], $this->autoloader->getNamespaces());
		$this->autoloader->removeNamespaces(['Tests']);
		$this->assertEquals([
			'Foo\Bar' => $dir,
		], $this->autoloader->getNamespaces());
	}

	public function testClasses()
	{
		$this->autoloader->setClasses([
			'\\' . __CLASS__ => __FILE__,
			'\Tests\LocatorTest' => __DIR__ . '/LocatorTest.php',
		]);
		$this->assertEquals(
			[
				__CLASS__ => __FILE__,
				'Tests\LocatorTest' => __DIR__ . '/LocatorTest.php',
			],
			$this->autoloader->getClasses()
		);
		$this->autoloader->removeClasses([__CLASS__]);
		$this->assertEquals(
			[
				'Tests\LocatorTest' => __DIR__ . '/LocatorTest.php',
			],
			$this->autoloader->getClasses()
		);
	}

	public function testFindClassPath()
	{
		$this->assertNull($this->autoloader->findClassPath(__CLASS__));
		$this->autoloader->setNamespace(__NAMESPACE__, __DIR__);
		$this->assertEquals(__FILE__, $this->autoloader->findClassPath(__CLASS__));
		$this->autoloader->setClass(__CLASS__, __FILE__);
		$this->assertEquals(__FILE__, $this->autoloader->findClassPath(__CLASS__));
	}

	public function testLoadClass()
	{
		$this->assertFalse($this->autoloader->loadClass('Foo\NamespacedClass'));
		$this->autoloader->setNamespace('Foo', __DIR__ . '/support');
		$this->assertTrue($this->autoloader->loadClass('Foo\NamespacedClass'));
	}

	public function testPathIsNotFile()
	{
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Path is not a file: foo');
		$this->autoloader->setClass('Foo', 'foo');
	}

	public function testPathIsNotDirectory()
	{
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Path is not a directory: foo');
		$this->autoloader->setNamespace('Foo', 'foo');
	}

	public function testNewClass()
	{
		$this->autoloader->setNamespace('Foo\NamespacedClass', __DIR__ . '/support');
		$this->assertInstanceOf(
			\Foo\NamespacedClass::class,
			new \Foo\NamespacedClass()
		);
	}

	public function testUnregister()
	{
		$this->assertTrue($this->autoloader->unregister());
	}
}

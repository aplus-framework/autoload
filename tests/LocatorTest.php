<?php namespace Tests\Autoload;

use Framework\Autoload\Autoloader;
use Framework\Autoload\Locator;
use PHPUnit\Framework\TestCase;

class LocatorTest extends TestCase
{
	/**
	 * @var \Framework\Autoload\Autoloader
	 */
	protected $autoloader;
	/**
	 * @var \Framework\Autoload\Locator
	 */
	protected $locator;

	public function setUp()
	{
		$this->autoloader = new Autoloader();
		$this->locator = new Locator($this->autoloader);
	}

	public function testListFiles()
	{
		$this->assertFalse($this->locator->listFiles(__DIR__ . '/unknown'));
		$this->assertEquals([
			__DIR__ . '/AutoloaderTest.php',
			__FILE__,
		], $this->locator->listFiles(__DIR__));
		$this->assertEquals([
			__DIR__ . '/AutoloaderTest.php',
			__FILE__,
		], $this->locator->listFiles(__DIR__ . '/../tests'));
	}

	public function testGetClass()
	{
		$this->assertEquals(__CLASS__, $this->locator->getClassName(__FILE__));
		$this->assertFalse($this->locator->getClassName(__DIR__ . '/unknown'));
	}

	public function testGetFiles()
	{
		$this->autoloader->setNamespace('Autoload', __DIR__ . '/..');
		$this->assertEquals(
			[
				__DIR__ . '/AutoloaderTest.php',
				__FILE__,
			],
			$this->locator->getFiles('tests')
		);
	}

	public function testFindFiles()
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

	public function testNamespacedFilepath()
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
		$this->assertFalse(
			$this->locator->getNamespacedFilepath('LocatorTest')
		);
	}
}

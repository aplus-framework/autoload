<?php namespace Tests\Autoload;

use Framework\Autoload\Autoloader;
use PHPUnit\Framework\TestCase;

class AutoloaderTest extends TestCase
{
	/**
	 * @var Autoloader
	 */
	protected $autoloader;

	public function setUp()
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
		$this->assertFalse($this->autoloader->getNamespace('Testss'));
		$this->assertEquals([
			'Tests' => $dir,
			'Foo\Bar' => $dir,
		], $this->autoloader->getNamespaces());
	}

	public function testClasses()
	{
		$classes = $this->autoloader->getClasses();
		$this->autoloader->setClass('\\' . __CLASS__, __FILE__);
		$this->assertEquals(
			\array_merge($classes, [__CLASS__ => __FILE__]),
			$this->autoloader->getClasses()
		);
		$this->autoloader->setClasses([__CLASS__ => __FILE__]);
		$this->assertEquals(
			\array_merge($classes, [__CLASS__ => __FILE__]),
			$this->autoloader->getClasses()
		);
	}

	/*public function testRegister()
	{

	}*/
	/*public function testLoadClass()
	{
		$this->assertInstanceOf(
			\Framework\Database\Database::class,
			new \Framework\Database\Database()
		);
	}*/
}

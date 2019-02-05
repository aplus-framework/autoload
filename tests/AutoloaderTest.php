<?php namespace Tests\Autoload;

use Framework\Autoload\Autoloader;
use PHPUnit\Framework\TestCase;

class AutoloaderTest extends TestCase
{
	/**
	 * @var \Framework\Autoload\Autoloader
	 */
	protected $autoloader;

	public function setUp()
	{
		$this->autoloader = new Autoloader();
	}

	public function testNamespaces()
	{
		$this->assertEmpty($this->autoloader->getNamespace());

		$this->autoloader->setNamespace([
			'Tests'     => __DIR__,
			'Foo\Bar\\' => __DIR__,
		]);

		$dir = __DIR__ . \DIRECTORY_SEPARATOR;

		$this->assertEquals($dir, $this->autoloader->getNamespace('Tests'));
		$this->assertFalse($this->autoloader->getNamespace('Testss'));
		$this->assertEquals([
			'Tests'   => $dir,
			'Foo\Bar' => $dir,
		], $this->autoloader->getNamespace());
	}

	public function testClasses()
	{
		$classes = $this->autoloader->getClass();

		$this->autoloader->setClass('\\' . __CLASS__, __FILE__);

		$this->assertEquals(
			\array_merge($classes, [__CLASS__ => __FILE__]),
			$this->autoloader->getClass()
		);

		$this->autoloader->setClass([__CLASS__ => __FILE__]);

		$this->assertEquals(
			\array_merge($classes, [__CLASS__ => __FILE__]),
			$this->autoloader->getClass()
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

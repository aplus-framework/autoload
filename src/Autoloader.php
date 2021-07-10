<?php declare(strict_types=1);
/*
 * This file is part of The Framework Autoload Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Autoload;

use JetBrains\PhpStorm\Pure;
use RuntimeException;

/**
 * Class Autoloader.
 *
 * The Autoloader class allows to set namespace directories to search for files
 * (PSR4) and set the absolute path of classes without namespaces (PSR0).
 */
class Autoloader
{
	/**
	 * List of classes to file paths.
	 *
	 * @var array<string,string>
	 */
	protected array $classes = [];
	/**
	 * List of namespaces to directory paths.
	 *
	 * @var array<string,string>
	 */
	protected array $namespaces = [];

	/**
	 * Autoloader constructor.
	 *
	 * @param bool $register Register the Autoloader::loadClass() as autoload
	 * implementation
	 * @param string $extensions A comma delimited list of file extensions for
	 * spl_autoload
	 *
	 * @see Autoloader::loadClass()
	 */
	public function __construct(bool $register = true, string $extensions = '.php')
	{
		if ($register) {
			$this->register($extensions);
		}
	}

	/**
	 * Registers the Autoloader::loadClass() as autoload implementation.
	 *
	 * @param string $extensions A comma delimited list of file extensions for
	 * spl_autoload
	 *
	 * @see Autoloader::loadClass()
	 *
	 * @return bool True on success or false on failure
	 */
	public function register(string $extensions = '.php') : bool
	{
		\spl_autoload_extensions($extensions);
		// @phpstan-ignore-next-line
		return \spl_autoload_register([$this, 'loadClass'], true, false);
	}

	/**
	 * Unregisters the Autoloader::loadClass() as autoload implementation.
	 *
	 * @see Autoloader::loadClass()
	 *
	 * @return bool True on success or false on failure
	 */
	public function unregister() : bool
	{
		return \spl_autoload_unregister([$this, 'loadClass']);
	}

	/**
	 * Sets one namespace mapping for a directory path.
	 *
	 * @param string $namespace Namespace name
	 * @param string $directory Directory path
	 *
	 * @return static
	 */
	public function setNamespace(string $namespace, string $directory) : static
	{
		$this->namespaces[$this->renderRealName($namespace)] = $this->renderDirectoryPath($directory);
		$this->sortNamespaces();
		return $this;
	}

	protected function sortNamespaces() : void
	{
		\krsort($this->namespaces);
	}

	/**
	 * Sets namespaces mapping for directory paths.
	 *
	 * @param array<string,string> $namespaces Associative array with namespace names
	 * as keys and directory paths as values
	 *
	 * @return static
	 */
	public function setNamespaces(array $namespaces) : static
	{
		foreach ($namespaces as $name => $directory) {
			$this->setNamespace($name, $directory);
		}
		$this->sortNamespaces();
		return $this;
	}

	/**
	 * Gets the directory path for a given namespace.
	 *
	 * @param string $name Namespace name
	 *
	 * @return string|null The directory path or null if namespace is not mapped
	 */
	#[Pure]
	public function getNamespace(string $name) : ?string
	{
		return $this->namespaces[$this->renderRealName($name)] ?? null;
	}

	/**
	 * Gets all mapped namespaces.
	 *
	 * @return array<string,string>
	 */
	#[Pure]
	public function getNamespaces() : array
	{
		return $this->namespaces;
	}

	/**
	 * Removes one namespace from the mapping.
	 *
	 * @param string $name Namespace name
	 *
	 * @return static
	 */
	public function removeNamespace(string $name) : static
	{
		unset($this->namespaces[$this->renderRealName($name)]);
		return $this;
	}

	/**
	 * Removes namespaces from the mapping.
	 *
	 * @param array<int,string> $names List of namespace names
	 *
	 * @return static
	 */
	public function removeNamespaces(array $names) : static
	{
		foreach ($names as $name) {
			$this->removeNamespace($name);
		}
		return $this;
	}

	/**
	 * Sets one class mapping for a file path.
	 *
	 * @param string $name Fully qualified class name (with namespace)
	 * @param string $filepath Class file path
	 *
	 * @return static
	 */
	public function setClass(string $name, string $filepath) : static
	{
		$this->classes[$this->renderRealName($name)] = $this->renderFilePath($filepath);
		return $this;
	}

	/**
	 * Sets classes mapping for file paths.
	 *
	 * @param array<string,string> $classes Associative array with class names
	 * as keys and file paths as values
	 *
	 * @return static
	 */
	public function setClasses(array $classes) : static
	{
		foreach ($classes as $name => $filepath) {
			$this->setClass($name, $filepath);
		}
		return $this;
	}

	/**
	 * Gets a class file path.
	 *
	 * @param string $name Fully qualified class name (with namespace)
	 *
	 * @return string|null The file path or null if class is not mapped
	 */
	#[Pure]
	public function getClass(string $name) : ?string
	{
		return $this->classes[$this->renderRealName($name)] ?? null;
	}

	/**
	 * Gets all mapped classes.
	 *
	 * @return array<string,string> An array of class names as keys and
	 * file paths as values
	 */
	#[Pure]
	public function getClasses() : array
	{
		return $this->classes;
	}

	/**
	 * Removes one class from the mapping.
	 *
	 * @param string $name Fully qualified class name (with namespace)
	 *
	 * @return static
	 */
	public function removeClass(string $name) : static
	{
		unset($this->classes[$this->renderRealName($name)]);
		return $this;
	}

	/**
	 * Removes classes from the mapping.
	 *
	 * @param array<int,string> $names List of class names
	 *
	 * @return static
	 */
	public function removeClasses(array $names) : static
	{
		foreach ($names as $name) {
			$this->removeClass($name);
		}
		return $this;
	}

	/**
	 * Finds the file path of a class searching in the class mapping and
	 * resolving namespaces.
	 *
	 * @param string $class Fully qualified class name (with namespace)
	 *
	 * @return string|null The class file path or null if not found
	 */
	#[Pure]
	public function findClassPath(string $class) : ?string
	{
		$path = $this->getClass($class);
		if ($path) {
			return $path;
		}
		foreach ($this->getNamespaces() as $namespace => $path) {
			$namespace .= '\\';
			if (\str_starts_with($class, $namespace)) {
				$path .= \strtr(
					\substr($class, \strlen($namespace)),
					['\\' => \DIRECTORY_SEPARATOR]
				);
				$path .= '.php';
				if (\is_file($path)) {
					return $path;
				}
			}
		}
		return null;
	}

	/**
	 * Loads a class file.
	 *
	 * @param string $class Fully qualified class name (with namespace)
	 *
	 * @return bool TRUE if the file is loaded, otherwise FALSE
	 */
	public function loadClass(string $class) : bool
	{
		$class = $this->findClassPath($class);
		if ($class) {
			// Require $class in a isolated scope - no access to $this
			(static function () use ($class) : void {
				require $class;
			})();
			return true;
		}
		return false;
	}

	/**
	 * Renders a class or namespace name without lateral slashes.
	 *
	 * @param string $name Class or namespace name
	 *
	 * @return string
	 */
	#[Pure]
	protected function renderRealName(string $name) : string
	{
		return \trim($name, '\\');
	}

	/**
	 * Renders the canonicalized absolute pathname for a file path.
	 *
	 * @param string $path File path
	 *
	 * @return string
	 */
	protected function renderFilePath(string $path) : string
	{
		$real = \realpath($path);
		if ($real === false || ! \is_file($real)) {
			throw new RuntimeException("Path is not a file: {$path}");
		}
		return $real;
	}

	/**
	 * Gets the canonicalized absolute pathname for a directory path.
	 *
	 * Adds a trailing slash.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	protected function renderDirectoryPath(string $path) : string
	{
		$real = \realpath($path);
		if ($real === false || ! \is_dir($real)) {
			throw new RuntimeException("Path is not a directory: {$path}");
		}
		return $real . \DIRECTORY_SEPARATOR;
	}
}

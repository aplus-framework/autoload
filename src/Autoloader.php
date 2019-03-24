<?php namespace Framework\Autoload;

/**
 * Class Autoloader.
 */
class Autoloader
{
	/**
	 * List of classes to file paths.
	 *
	 * @var array
	 */
	protected $classes = [];
	/**
	 * List of namespaces to directory paths.
	 *
	 * @var array
	 */
	protected $namespaces = [];

	/**
	 * Autoloader constructor.
	 *
	 * @param bool   $register   Register the {@see Autoloader::loadClass} as autoload
	 *                           implementation
	 * @param string $extensions A comma delimited list of file extensions for spl_autoload
	 */
	public function __construct(bool $register = true, string $extensions = '.php')
	{
		if ($register) {
			$this->register($extensions);
		}
	}

	/**
	 * Registers the {@see Autoloader::loadClass} as autoload implementation.
	 *
	 * @param string $extensions A comma delimited list of file extensions for spl_autoload
	 *
	 * @return bool True on success or false on failure
	 */
	public function register(string $extensions = '.php') : bool
	{
		\spl_autoload_extensions($extensions);
		return \spl_autoload_register([$this, 'loadClass'], true, false);
	}

	/**
	 * Unregisters the {@see Autoloader::loadClass} as autoload implementation.
	 *
	 * @return bool True on success or false on failure
	 */
	public function unregister() : bool
	{
		return \spl_autoload_unregister([$this, 'loadClass']);
	}

	/**
	 * Sets namespaces mapping for directory paths.
	 *
	 * @param string $namespace
	 * @param string $directory
	 *
	 * @return $this
	 */
	public function setNamespace(string $namespace, string $directory)
	{
		$this->namespaces[$this->renderRealName($namespace)] = $this->renderDirectoryPath($directory);
		$this->sortNamespaces();
		return $this;
	}

	protected function sortNamespaces()
	{
		\krsort($this->namespaces);
	}

	public function setNamespaces(array $namespaces)
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
	 * @param string $name
	 *
	 * @return false|string
	 */
	public function getNamespace(string $name)
	{
		return $this->namespaces[$this->renderRealName($name)] ?? false;
	}

	public function getNamespaces() : array
	{
		return $this->namespaces;
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function removeNamespace(string $name)
	{
		unset($this->namespaces[$this->renderRealName($name)]);
		return $this;
	}

	public function removeNamespaces(array $names)
	{
		foreach ($names as $name) {
			$this->removeNamespace($name);
		}
		return $this;
	}

	/**
	 * Sets classes mapping for file paths.
	 *
	 * @param string $name     Fully qualified class name (with namespace)
	 * @param string $filepath
	 *
	 * @return $this
	 */
	public function setClass(string $name, string $filepath)
	{
		$this->classes[$this->renderRealName($name)] = $this->renderFilePath($filepath);
		return $this;
	}

	public function setClasses(array $classes)
	{
		foreach ($classes as $name => $filepath) {
			$this->setClass($name, $filepath);
		}
		return $this;
	}

	/**
	 * Get class filepath.
	 *
	 * @param string $name Fully qualified class name (with namespace)
	 *
	 * @return false|string
	 */
	public function getClass(string $name)
	{
		return $this->classes[$this->renderRealName($name)] ?? false;
	}

	public function getClasses() : array
	{
		return $this->classes;
	}

	public function removeClass(string $name)
	{
		unset($this->classes[$this->renderRealName($name)]);
		return $this;
	}

	public function removeClasses(array $names)
	{
		foreach ($names as $name) {
			$this->removeClass($name);
		}
		return $this;
	}

	public function findClassPath(string $class)
	{
		$path = $this->getClass($class);
		if ($path) {
			return $path;
		}
		foreach ($this->getNamespaces() as $namespace => $path) {
			$namespace .= '\\';
			if (\strpos($class, $namespace) === 0) {
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
		return false;
	}

	/**
	 * Load a class file.
	 *
	 * @param string $class
	 *
	 * @return bool true if the file is loaded, otherwise false
	 */
	public function loadClass(string $class) : bool
	{
		$class = $this->findClassPath($class);
		if ($class) {
			include $class;
			return true;
		}
		return false;
	}

	/**
	 * Gets a class or namespace name without lateral slashes.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function renderRealName(string $name) : string
	{
		return \trim($name, '\\');
	}

	/**
	 * Gets the canonicalized absolute pathname for a file path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	protected function renderFilePath(string $path) : string
	{
		$real = \realpath($path);
		if ($real === false || ! \is_file($real)) {
			throw new \RuntimeException("Path is not a file: {$path}");
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
			throw new \RuntimeException("Path is not a directory: {$path}");
		}
		return $real . \DIRECTORY_SEPARATOR;
	}
}

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
	 * @param bool   $register        register the {@see Autoloader::loadClass} as autoload
	 *                                implementation
	 * @param string $file_extensions a comma delimited list of file extensions for spl_autoload
	 */
	public function __construct(bool $register = true, string $file_extensions = '.php')
	{
		if ($register) {
			$this->register($file_extensions);
		}
	}

	/**
	 * Registers the {@see Autoloader::loadClass} as autoload implementation.
	 *
	 * @param string $file_extensions a comma delimited list of file extensions for spl_autoload
	 *
	 * @return bool true on success or false on failure
	 */
	public function register(string $file_extensions = '.php') : bool
	{
		\spl_autoload_extensions($file_extensions);
		return \spl_autoload_register([$this, 'loadClass'], true, true);
	}

	/**
	 * Unregisters the {@see Autoloader::loadClass} as autoload implementation.
	 *
	 * @return bool true on success or false on failure
	 */
	public function unregister() : bool
	{
		return \spl_autoload_unregister([$this, 'loadClass']);
	}

	/**
	 * Sets namespaces mapping for directory paths.
	 *
	 * @param array|string $namespace
	 * @param string|null  $directory
	 *
	 * @return $this
	 */
	public function setNamespace($namespace, string $directory = null)
	{
		if (\is_array($namespace)) {
			foreach ($namespace as $name => $directory) {
				$this->namespaces[$this->getRealName($name)] = $this->getRealPath($directory);
			}
			return $this;
		}
		$this->namespaces[$this->getRealName($namespace)] = $this->getRealPath($directory);
		\krsort($this->namespaces);
		return $this;
	}

	/**
	 * Gets the directory path for a given namespace.
	 *
	 * @param string|null $namespace leave null to return an array of all setted namespaces
	 *
	 * @return array|false|string
	 */
	public function getNamespace(string $namespace = null)
	{
		if ($namespace === null) {
			return $this->namespaces;
		}
		return $this->namespaces[$this->getRealName($namespace)] ?? false;
	}

	/**
	 * @param array|string $namespace
	 *
	 * @return $this
	 */
	public function removeNamespace($namespace)
	{
		if (\is_array($namespace)) {
			foreach ($namespace as $name) {
				unset($this->namespaces[$this->getRealName($name)]);
			}
			return $this;
		}
		unset($this->namespaces[$this->getRealName($namespace)]);
		return $this;
	}

	/**
	 * Sets classes mapping for file paths.
	 *
	 * @param array|string $class    Fully qualified class name (with namespace) or a list with
	 *                               file paths
	 * @param string|null  $filepath
	 *
	 * @return $this
	 */
	public function setClass($class, string $filepath = null)
	{
		if (\is_array($class)) {
			foreach ($class as $name => $filepath) {
				$this->classes[$this->getRealName($name)] = $this->getRealPath($filepath, false);
			}
			return $this;
		}
		$this->classes[$this->getRealName($class)] = $this->getRealPath($filepath, false);
		return $this;
	}

	/**
	 * Get class filepath.
	 *
	 * @param string|null $class fully qualified class name (with namespace) or null to return all
	 *
	 * @return array|string|null
	 */
	public function getClass(string $class = null)
	{
		if ($class === null) {
			return $this->classes;
		}
		return $this->classes[$this->getRealName($class)] ?? null;
	}

	public function removeClass($class)
	{
		if (\is_array($class)) {
			foreach ($class as $name) {
				unset($this->classes[$this->getRealName($name)]);
			}
			return $this;
		}
		unset($this->classes[$this->getRealName($class)]);
		return $this;
	}

	public function findClassPath(string $class)
	{
		if ($path = $this->getClass($class)) {
			return $path;
		}
		foreach ($this->getNamespace() as $namespace => $path) {
			$namespace .= '\\';
			$ns_len = \strlen($namespace);
			//\strpos($class,$namespace);
			if (\substr($class, 0, $ns_len) === $namespace) {
				$path .= \strtr(\substr($class, $ns_len), '\\', \DIRECTORY_SEPARATOR) . '.php';
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
	protected function getRealName(string $name) : string
	{
		return \trim($name, '\\');
	}

	/**
	 * Gets the canonicalized absolute pathname for a file or directory.
	 *
	 * Adds a trailing slash if the path is a directory.
	 *
	 * @param string $path
	 * @param bool   $directory true if the path is a directory or false for a filepath
	 *
	 * @return string
	 */
	protected function getRealPath(string $path, bool $directory = true) : string
	{
		$real = \realpath($path);
		if ($real === false) {
			if ($directory && ! \is_dir($real)) {
				throw new \Exception('Directory path  "' . $path . '" could not be resolved.');
			}
			throw new \Exception('File path "' . $path . '" could not be resolved.');
		}
		if ($directory && ! \is_dir($real)) {
			throw new \Exception('The path "' . $path . '" is not a directory.');
		}
		if ( ! $directory && ! \is_file($real)) {
			throw new \Exception('The path "' . $path . '" is not a file.');
		}
		return $directory ? $real . \DIRECTORY_SEPARATOR : $real;
	}
}

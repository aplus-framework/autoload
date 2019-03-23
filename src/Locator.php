<?php namespace Framework\Autoload;

/**
 * Class Locator.
 */
class Locator
{
	/**
	 * @var \Framework\Autoload\Autoloader
	 */
	protected $autoloader;

	/**
	 * Locator constructor.
	 *
	 * @param \Framework\Autoload\Autoloader $autoloader
	 */
	public function __construct(Autoloader $autoloader)
	{
		$this->autoloader = $autoloader;
	}

	/**
	 * Gets the Fully Qualified Name of the given filename.
	 *
	 * @param string $filename
	 *
	 * @see http://php.net/manual/pt_BR/language.namespaces.rules.php
	 *
	 * @return false|string
	 */
	public function getClassName(string $filename)
	{
		if ( ! \is_file($filename)) {
			return false;
		}
		$php_code = \file_get_contents($filename);
		$tokens = \token_get_all($php_code);
		$count = \count($tokens);
		$namespace = '';
		$class = '';
		for ($i = 0; $i < $count; $i++) {
			if ($tokens[$i][0] === \T_NAMESPACE) {
				for ($t = $i + 1; $t < $count; ++$t) {
					if ($tokens[$t][0] === \T_STRING) {
						$namespace .= '\\' . $tokens[$t][1];
					} elseif ($tokens[$t] === '{' || $tokens[$t] === ';') {
						break;
					}
				}
			} elseif ($tokens[$i][0] === \T_CLASS) {
				for ($t = $i + 1; $t < $count; ++$t) {
					if ($tokens[$t] === '{') {
						// Get many classes in the same file - remove the breaks
						//$classes[] = $namespace . '\\' . $tokens[$i + 2][1];
						$class = $namespace . '\\' . $tokens[$i + 2][1];
						break;
					}
				}
			}
			if ($class) {
				break;
			}
		}
		return $class ? \ltrim($class, '\\') : false;
	}

	public function getNamespacedFilepath(string $file, string $extension = '.php')
	{
		if ($extension) {
			$file = $this->ensureExtension($file, $extension);
		}
		$file = \strtr($file, '\\', '/');
		$file = \ltrim($file, '/');
		$segments = \explode('/', $file);
		$count = \count($segments);
		$file = $segments[$count - 1];
		unset($segments[$count - 1]);
		$namespaces = $this->autoloader->getNamespace();
		$namespace = '';
		while ($segments) {
			$namespace .= empty($namespace)
				? \array_shift($segments)
				: '\\' . \array_shift($segments);
			if (empty($namespaces[$namespace])) {
				continue;
			}
			return $namespaces[$namespace] . \implode('/', $segments) . $file;
		}
		return \is_file($file) ? $file : false;
	}

	protected function ensureExtension(string $filename, string $extension)
	{
		if (\mb_substr($filename, -\mb_strlen($extension)) !== $extension) {
			$filename .= $extension;
		}
		return $filename;
	}

	/**
	 * Find namesake files inside namespaced directories.
	 *
	 * @param string $filename
	 * @param string $extension
	 *
	 * @return array
	 */
	public function findFiles(string $filename, string $extension = '.php') : array
	{
		if ($extension) {
			$filename = $this->ensureExtension($filename, $extension);
		}
		$files = [];
		foreach ($this->autoloader->getNamespace() as $namespace => $directory) {
			if (\is_file($directory .= $filename)) {
				$files[] = $directory;
			}
		}
		return $files;
	}

	/**
	 * Get a list of all files inside namespaced sub directories.
	 *
	 * @param string $sub_directory Sub directory path
	 *
	 * @return array
	 */
	public function getFiles(string $sub_directory) : array
	{
		$namespaced_files = [];
		foreach ($this->autoloader->getNamespace() as $namespace => $directory) {
			$files = $this->listFiles($directory . $sub_directory);
			if ($files) {
				$namespaced_files = \array_merge($namespaced_files, $files);
			}
		}
		return $namespaced_files;
	}

	/**
	 * Get a list of all files inside a directory.
	 *
	 * @param string $absolute_directory Absolute directory path
	 *
	 * @return array|false returns an array of file paths or false if the directory can not be
	 *                     resolved
	 */
	public function listFiles(string $absolute_directory)
	{
		$absolute_directory = \realpath($absolute_directory);
		if ($absolute_directory === false) {
			return false;
		}
		$absolute_directory .= \DIRECTORY_SEPARATOR;
		$filenames = \scandir($absolute_directory);
		$files = [];
		foreach ($filenames as $filename) {
			if ($filename === '.' || $filename === '..') {
				continue;
			}
			if (\is_file($absolute_directory . $filename)) {
				$files[] = $absolute_directory . $filename;
				continue;
			}
			foreach ($this->listFiles($absolute_directory . $filename) as $sub) {
				$files[] = $sub;
			}
		}
		return $files;
	}
}

<?php namespace Framework\Autoload;

/**
 * Class Locator.
 */
class Locator
{
	/**
	 * @var Autoloader
	 */
	protected $autoloader;

	/**
	 * Locator constructor.
	 *
	 * @param Autoloader $autoloader
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
		$tokens = \token_get_all(\file_get_contents($filename));
		$last = \count($tokens);
		$namespace = '';
		$class = '';
		foreach ($tokens as $current => $token) {
			if ($token[0] === \T_NAMESPACE) {
				for ($next = $current + 1; $next < $last; $next++) {
					if ($tokens[$next][0] === \T_STRING) {
						$namespace .= '\\' . $tokens[$next][1];
					} elseif ($tokens[$next] === '{' || $tokens[$next] === ';') {
						break;
					}
				}
				continue;
			}
			if ($token[0] === \T_CLASS) {
				for ($next = $current + 1; $next < $last; $next++) {
					if ($tokens[$next] === '{') {
						$class = $namespace . '\\' . $tokens[$current + 2][1];
						break 2;
					}
				}
			}
		}
		return $class ? \ltrim($class, '\\') : false;
	}

	public function getNamespacedFilepath(string $file, string $extension = '.php')
	{
		if ($extension) {
			$file = $this->ensureExtension($file, $extension);
		}
		$file = \strtr(\ltrim($file, '/'), ['\\' => '/']);
		$segments = \explode('/', $file);
		$count = \count($segments) - 1;
		$file = $segments[$count];
		unset($segments[$count]);
		$namespaces = $this->autoloader->getNamespaces();
		$namespace = '';
		while ($segments) {
			$namespace .= $namespace === ''
				? \array_shift($segments)
				: '\\' . \array_shift($segments);
			if ( ! isset($namespaces[$namespace])) {
				continue;
			}
			$file = \rtrim(
				$namespaces[$namespace] . \implode(\DIRECTORY_SEPARATOR, $segments),
				\DIRECTORY_SEPARATOR
			) . \DIRECTORY_SEPARATOR . $file;
			break;
		}
		return \is_file($file) ? $file : false;
	}

	protected function ensureExtension(string $filename, string $extension) : string
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
		foreach ($this->autoloader->getNamespaces() as $directory) {
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
		foreach ($this->autoloader->getNamespaces() as $directory) {
			$files = $this->listFiles($directory . $sub_directory);
			if ($files) {
				$namespaced_files[] = $files;
			}
		}
		return $namespaced_files ? \array_merge(...$namespaced_files) : [];
	}

	/**
	 * Get a list of all files inside a directory.
	 *
	 * @param string $directory Absolute directory path
	 *
	 * @return array|false returns an array of file paths or false if the directory can not be
	 *                     resolved
	 */
	public function listFiles(string $directory)
	{
		$directory = \realpath($directory);
		if ($directory === false) {
			return false;
		}
		$directory .= \DIRECTORY_SEPARATOR;
		$files = [];
		foreach (\scandir($directory, 0) as $filename) {
			if ($filename === '.' || $filename === '..') {
				continue;
			}
			$filename = $directory . $filename;
			if (\is_file($filename)) {
				$files[] = $filename;
				continue;
			}
			foreach ($this->listFiles($filename) as $sub_directory) {
				$files[] = $sub_directory;
			}
		}
		return $files;
	}
}

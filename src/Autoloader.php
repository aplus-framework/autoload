<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Autoload Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Autoload;

use Framework\Autoload\Debug\AutoloadCollector;
use Framework\Debug\Collector;
use JetBrains\PhpStorm\Pure;
use RuntimeException;

/**
 * Class Autoloader.
 *
 * The Autoloader class allows to set namespace directories to search for files
 * (PSR4) and set the absolute path of classes without namespaces (PSR0).
 *
 * @package autoload
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
     * @var array<string,array<int,string>>
     */
    protected array $namespaces = [];
    protected AutoloadCollector $debugCollector;

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
     * @param array<string>|string $dir Directory path
     *
     * @return static
     */
    public function setNamespace(string $namespace, array | string $dir) : static
    {
        $directories = $this->makeRenderedDirectoryPaths((array) $dir);
        if ($directories) {
            $this->namespaces[$this->renderRealName($namespace)] = $directories;
            $this->sortNamespaces();
        }
        return $this;
    }

    /**
     * @param array<string> $directories
     *
     * @return array<int,string>
     */
    protected function makeRenderedDirectoryPaths(array $directories) : array
    {
        $paths = [];
        foreach ($directories as $directory) {
            $paths[] = $this->renderDirectoryPath($directory);
        }
        return $paths;
    }

    /**
     * Adds directory paths to a namespace.
     *
     * @param string $namespace Namespace name
     * @param array<string>|string $dir Directory path
     *
     * @return static
     */
    public function addNamespace(string $namespace, array | string $dir) : static
    {
        $directories = $this->makeRenderedDirectoryPaths((array) $dir);
        if ($directories) {
            $name = $this->renderRealName($namespace);
            if (isset($this->namespaces[$name])) {
                $directories = [...$this->namespaces[$name], ...$directories];
            }
            $this->namespaces[$name] = $directories;
            $this->sortNamespaces();
        }
        return $this;
    }

    protected function sortNamespaces() : void
    {
        \krsort($this->namespaces);
    }

    /**
     * Sets namespaces mapping for directory paths.
     *
     * @param array<string,array<string>|string> $namespaces Namespace names
     * as keys and directory paths as values
     *
     * @return static
     */
    public function setNamespaces(array $namespaces) : static
    {
        foreach ($namespaces as $name => $dir) {
            $this->setNamespace($name, $dir);
        }
        $this->sortNamespaces();
        return $this;
    }

    /**
     * Adds directory paths to namespaces.
     *
     * @param array<string,array<string>|string> $namespaces Namespace names
     * as keys and directory paths as values
     *
     * @return static
     */
    public function addNamespaces(array $namespaces) : static
    {
        foreach ($namespaces as $name => $dir) {
            $this->addNamespace($name, $dir);
        }
        return $this;
    }

    /**
     * Gets the directory paths for a given namespace.
     *
     * @param string $name Namespace name
     *
     * @return array<int,string> The namespace directory paths
     */
    #[Pure]
    public function getNamespace(string $name) : array
    {
        return $this->namespaces[$this->renderRealName($name)] ?? [];
    }

    /**
     * Gets all mapped namespaces.
     *
     * @return array<string,array<int,string>>
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
     * @param array<string> $names List of namespace names
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
        foreach ($this->getNamespaces() as $namespace => $paths) {
            $namespace .= '\\';
            if (\str_starts_with($class, $namespace)) {
                foreach ($paths as $path) {
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
        if (isset($this->debugCollector)) {
            return $this->loadDebug($class);
        }
        return $this->loadClassFile($class);
    }

    protected function loadClassFile(string $class) : bool
    {
        $class = $this->findClassPath($class);
        if ($class) {
            // Require $class in an isolated scope - no access to $this
            (static function () use ($class) : void {
                require $class;
            })();
            return true;
        }
        return false;
    }

    protected function loadDebug(string $class) : bool
    {
        $start = \microtime(true);
        $loaded = $this->loadClassFile($class);
        $end = \microtime(true);
        $this->debugCollector->addData([
            'start' => $start,
            'end' => $end,
            'class' => $class,
            'file' => $this->findClassPath($class),
            'loaded' => $loaded,
        ]);
        return $loaded;
    }

    public function setDebugCollector(AutoloadCollector $debugCollector = null, string $name = 'default') : static
    {
        if ($debugCollector) {
            $this->debugCollector = $debugCollector;
            return $this;
        }
        $data = [];
        foreach ([Collector::class, AutoloadCollector::class] as $class) {
            $start = \microtime(true);
            $loaded = $this->loadClassFile($class);
            $end = \microtime(true);
            $data[] = [
                'start' => $start,
                'end' => $end,
                'class' => $class,
                'file' => $this->findClassPath($class),
                'loaded' => $loaded,
            ];
        }
        $this->debugCollector = new AutoloadCollector($name);
        $this->debugCollector->setAutoloader($this);
        foreach ($data as $item) {
            $this->debugCollector->addData($item);
        }
        return $this;
    }

    public function getDebugCollector() : ?AutoloadCollector
    {
        return $this->debugCollector ?? null;
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

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

require_once __DIR__ . '/Autoloader.php';
require_once __DIR__ . '/Locator.php';

/**
 * Class Preloader.
 *
 * @see https://www.php.net/manual/en/opcache.preloading.php
 *
 * @package autoload
 */
class Preloader
{
    /**
     * The main 'aplus' packages directory.
     * Normally installed with Composer in 'vendor/aplus'.
     *
     * @var string
     */
    protected string $packagesDir = __DIR__ . '/../../';
    /**
     * The Autoloader instance necessary to autoload required classes.
     *
     * @var Autoloader
     */
    protected Autoloader $autoloader;
    /**
     * The Locator instance used to list files.
     *
     * @var Locator
     */
    protected Locator $locator;
    /**
     * Namespaces-to-packages directory names mapping.
     *
     * Also, the keys are the preloadable namespaces.
     *
     * @var array<string,string>
     */
    protected array $namespacesToPackages = [
        'Framework\Autoload' => 'autoload',
        'Framework\Cache' => 'cache',
        'Framework\CLI' => 'cli',
        // 'Framework\CodingStandard' => 'coding-standard',
        'Framework\Config' => 'config',
        'Framework\Crypto' => 'crypto',
        'Framework\Database' => 'database',
        'Framework\Date' => 'date',
        'Framework\Debug' => 'debug',
        'Framework\Email' => 'email',
        'Framework\Factories' => 'factories',
        'Framework\HTTP' => 'http',
        'Framework\HTTP\Client' => 'http-client',
        'Framework\Helpers' => 'helpers',
        'Framework\Image' => 'image',
        'Framework\Language' => 'language',
        'Framework\Log' => 'log',
        'Framework\MVC' => 'mvc',
        'Framework\Pagination' => 'pagination',
        'Framework\REST' => 'rest',
        'Framework\Routing' => 'routing',
        'Framework\Session' => 'session',
        // 'Framework\Testing' => 'testing',
        'Framework\Validation' => 'validation',
    ];

    /**
     * Preloader constructor.
     *
     * @param Autoloader|null $autoloader A custom Autoloader instance or null
     * to auto initialize a new
     */
    public function __construct(Autoloader $autoloader = null)
    {
        $this->packagesDir = \realpath($this->packagesDir) . '/';
        $this->autoloader = $autoloader ?? new Autoloader();
        $this->locator = new Locator($this->autoloader);
    }

    /**
     * Set Autoloader namespaces based on namespaces-to-packages directories.
     *
     * @return static
     */
    protected function setNamespaces() : static
    {
        $namespacesToDirs = [];
        foreach ($this->namespacesToPackages as $namespace => $package) {
            $dir = $this->packagesDir . $package . '/src';
            if (\is_dir($dir)) {
                $namespacesToDirs[$namespace] = $dir;
            }
        }
        $this->autoloader->setNamespaces($namespacesToDirs);
        return $this;
    }

    /**
     * Tells is a FQCN is preloadable.
     *
     * @param string $className The Fully Qualified Class Name
     *
     * @return bool
     */
    protected function isPreloadable(string $className) : bool
    {
        foreach (\array_keys($this->namespacesToPackages) as $namespace) {
            if ($className === 'Aplus' || \str_starts_with($className, $namespace . '\\')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Load files to be seen by the PHP OPcache Preloading when the engine starts.
     *
     * @return array<int,string> The loaded files
     */
    public function load() : array
    {
        $this->setNamespaces();
        $files = $this->locator->listFiles($this->packagesDir);
        $loadedFiles = [];
        foreach ($files as $file) {
            if ( ! \str_ends_with($file, '.php')) {
                continue;
            }
            $className = $this->locator->getClassName($file);
            if ( ! $className || ! $this->isPreloadable($className)) {
                continue;
            }
            (static function () use ($file) : void {
                require_once $file;
            })();
            $loadedFiles[] = $file;
        }
        return $loadedFiles;
    }

    /**
     * Get a list of all declared classes, interfaces and traits.
     *
     * @return array<int,string>
     */
    public static function getAllDeclarations() : array
    {
        $declarations = [
            ...\get_declared_classes(),
            ...\get_declared_interfaces(),
            ...\get_declared_traits(),
        ];
        \sort($declarations);
        return $declarations;
    }

    /**
     * Get a list of Framework declarations.
     *
     * @return array<int,string>
     */
    public static function getDeclarations() : array
    {
        $result = [];
        foreach (static::getAllDeclarations() as $declaration) {
            if ($declaration === 'Aplus' || \str_starts_with($declaration, 'Framework\\')) {
                $result[] = $declaration;
            }
        }
        return $result;
    }

    /**
     * Get a list of all included/required files.
     *
     * @return array<int,string>
     */
    public static function getIncludedFiles() : array
    {
        return \get_included_files();
    }
}

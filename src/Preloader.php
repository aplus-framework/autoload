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
     * @var array<string,string>
     */
    protected array $namespacesToPackages = [
        'Framework\Autoload' => 'autoload',
        'Framework\Cache' => 'cache',
        'Framework\CLI' => 'cli',
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
            if (\str_ends_with($file, '.phpstorm.meta.php')) {
                continue;
            }
            if (\str_contains($file, '/aplus/coding-standard/')) {
                continue;
            }
            if (\str_contains($file, '/aplus/testing/')) {
                continue;
            }
            if (\str_contains($file, '/src/Languages/')) {
                continue;
            }
            if (\str_contains($file, '/src/Views/')) {
                continue;
            }
            $loadedFiles[] = $file;
            (static function () use ($file) : void {
                require_once $file;
            })();
        }
        return $loadedFiles;
    }

    /**
     * Get a list of all declared classes;.
     *
     * @return array<int,string>
     */
    public function getDeclaredClasses() : array
    {
        return \get_declared_classes();
    }

    /**
     * Get a list of Framework declared classes.
     *
     * @return array<int,string>
     */
    public function getFrameworkDeclaredClasses() : array
    {
        $frameworkClasses = [];
        foreach ($this->getDeclaredClasses() as $declaredClass) {
            if (\str_starts_with($declaredClass, 'Framework\\')) {
                $frameworkClasses[] = $declaredClass;
            }
        }
        return $frameworkClasses;
    }

    /**
     * Get a list of included/required files.
     *
     * @return array<int,string>
     */
    public function getIncludedFiles() : array
    {
        return \get_included_files();
    }
}

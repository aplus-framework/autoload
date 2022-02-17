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

use InvalidArgumentException;

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
     *
     * @var string
     */
    protected string $packagesDir;
    protected bool $loadPackages = true;
    protected bool $loadDevPackages = false;
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
     * Preloader constructor.
     *
     * @param Autoloader|null $autoloader A custom Autoloader instance or null
     * to auto initialize a new
     * @param string|null $packagesDir The main 'aplus' packages directory or
     * null to disable packages loading
     */
    public function __construct(
        Autoloader $autoloader = null,
        ?string $packagesDir = __DIR__ . '/../../'
    ) {
        $this->loadPackages = $packagesDir !== null;
        if ($this->loadPackages) {
            $this->setPackagesDir($packagesDir);
        }
        $this->autoloader = $autoloader ?? new Autoloader();
        $this->locator = new Locator($this->autoloader);
    }

    public function getAutoloader() : Autoloader
    {
        return $this->autoloader;
    }

    public function getLocator() : Locator
    {
        return $this->locator;
    }

    public function setPackagesDir(string $packagesDir) : static
    {
        $realpath = \realpath($packagesDir);
        if ( ! $realpath || ! \is_dir($packagesDir)) {
            throw new InvalidArgumentException('Invalid packages dir: ' . $packagesDir);
        }
        $this->packagesDir = $realpath . \DIRECTORY_SEPARATOR;
        return $this;
    }

    public function getPackagesDir() : string
    {
        return $this->packagesDir;
    }

    public function withPackages() : static
    {
        $this->loadPackages = true;
        return $this;
    }

    public function withDevPackages() : static
    {
        $this->loadDevPackages = true;
        return $this;
    }

    /**
     * @param bool $setClasses
     *
     * @return array<int,string>
     */
    public function listPackagesFiles(bool $setClasses = true) : array
    {
        $result = [];
        foreach ($this->getLocator()->listFiles($this->getPackagesDir()) as $file) {
            if ( ! \str_ends_with($file, '.php')) {
                continue;
            }
            $className = $this->getLocator()->getClassName($file);
            if ( ! $className
                || ($className !== 'Aplus' && ! \str_starts_with($className, 'Framework\\'))
            ) {
                continue;
            }
            if ( ! $this->loadDevPackages && $this->isDevelopmentClass($className)) {
                continue;
            }
            if ($setClasses) {
                $this->getAutoloader()->setClass($className, $file);
            }
            $result[] = $file;
        }
        \sort($result);
        return \array_unique($result);
    }

    protected function isDevelopmentClass(string $className) : bool
    {
        return \str_starts_with($className, 'Framework\\CodingStandard\\')
            || \str_starts_with($className, 'Framework\\Testing\\');
    }

    /**
     * @param bool $setClasses
     *
     * @return array<int,string>
     */
    public function listFiles(bool $setClasses = true) : array
    {
        $result = [];
        foreach ($this->getAutoloader()->getClasses() as $file) {
            $result[] = $file;
        }
        foreach ($this->getAutoloader()->getNamespaces() as $namespace => $directories) {
            foreach ($directories as $directory) {
                $files = $this->getLocator()->listFiles($directory);
                foreach ($files as $file) {
                    if ( ! \str_ends_with($file, '.php')) {
                        continue;
                    }
                    $className = $this->getLocator()->getClassName($file);
                    if ( ! $className
                        || ! \str_starts_with($className, $namespace . '\\')
                    ) {
                        continue;
                    }
                    if ($setClasses) {
                        $this->getAutoloader()->setClass($className, $file);
                    }
                    $result[] = $file;
                }
            }
        }
        if ($this->loadPackages) {
            $result = [...$result, ...$this->listPackagesFiles($setClasses)];
        }
        \sort($result);
        return \array_unique($result);
    }

    /**
     * Load files to be seen by the PHP OPcache Preloading when the engine starts.
     *
     * @return array<int,string> The loaded files
     */
    public function load() : array
    {
        $loadedFiles = [];
        foreach ($this->listFiles() as $file) {
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

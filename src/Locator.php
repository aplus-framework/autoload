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

use JetBrains\PhpStorm\Pure;

/**
 * Class Locator.
 *
 * The Locator class has methods for finding files and the class FQN using an
 * Autoloader instance.
 *
 * @package autoload
 */
class Locator
{
    protected Autoloader $autoloader;

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
     * Gets the first Qualified Class Name in a given filename.
     *
     * The "class name" can be the name of a class, an interface or a trait.
     *
     * @param string $filename
     *
     * @see https://www.php.net/manual/en/language.namespaces.rules.php
     * @see https://www.php.net/manual/en/language.oop5.interfaces.php
     *
     * @return string|null The class name or null if not found
     */
    public function getClassName(string $filename) : ?string
    {
        if ( ! \is_file($filename)) {
            return null;
        }
        $tokens = \token_get_all((string) \file_get_contents($filename));
        $last = \count($tokens);
        $namespace = '';
        $class = '';
        foreach ($tokens as $current => $token) {
            if ($token[0] === \T_NAMESPACE) {
                for ($next = $current + 1; $next < $last; $next++) {
                    if ($tokens[$next][0] === \T_STRING || $tokens[$next][0] === \T_NAME_QUALIFIED) {
                        $namespace .= '\\' . $tokens[$next][1];
                    } elseif ($tokens[$next] === '{' || $tokens[$next] === ';') {
                        break;
                    }
                }
                continue;
            }
            if ($token[0] === \T_RETURN) {
                return null;
            }
            if (\in_array($token[0], [
                \T_CLASS,
                \T_ENUM,
                \T_INTERFACE,
                \T_TRAIT,
            ], true)) {
                for ($next = $current + 1; $next < $last; $next++) {
                    if ($tokens[$next] === '{') {
                        $token = $tokens[$current + 2];
                        $class = $namespace . '\\' . $token[1];
                        break 2;
                    }
                }
            }
        }
        return $class ? \ltrim($class, '\\') : null;
    }

    /**
     * Get the first filepath found in all namespaces.
     *
     * @param string $file The file name without extension
     * @param string $extension The file extension
     *
     * @return string|null The filepath or null if not found
     */
    public function getNamespacedFilepath(string $file, string $extension = '.php') : ?string
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
            foreach ($namespaces[$namespace] as $directory) {
                $filepath = \rtrim(
                    $directory . \implode(\DIRECTORY_SEPARATOR, $segments),
                    \DIRECTORY_SEPARATOR
                ) . \DIRECTORY_SEPARATOR . $file;
                if (\is_file($filepath)) {
                    return $filepath;
                }
            }
            break;
        }
        return \is_file($file) ? $file : null;
    }

    #[Pure]
    protected function ensureExtension(string $filename, string $extension) : string
    {
        if ( ! \str_ends_with($filename, $extension)) {
            $filename .= $extension;
        }
        return $filename;
    }

    /**
     * Find namesake files inside namespaced directories.
     *
     * @param string $filename The file name
     * @param string $extension The file extension
     *
     * @return array<int,string> An array of filenames found
     */
    #[Pure]
    public function findFiles(string $filename, string $extension = '.php') : array
    {
        if ($extension) {
            $filename = $this->ensureExtension($filename, $extension);
        }
        $files = [];
        foreach ($this->autoloader->getNamespaces() as $directories) {
            foreach ($directories as $directory) {
                if (\is_file($directory .= $filename)) {
                    $files[] = $directory;
                }
            }
        }
        return $files;
    }

    /**
     * Get a list of all files inside namespaced sub directories.
     *
     * @param string $subDirectory Sub directory path
     *
     * @return array<int,string>
     */
    public function getFiles(string $subDirectory) : array
    {
        $namespacedFiles = [];
        foreach ($this->autoloader->getNamespaces() as $directories) {
            foreach ($directories as $directory) {
                $files = $this->listFiles($directory . $subDirectory);
                if ($files) {
                    $namespacedFiles[] = $files;
                }
            }
        }
        return $namespacedFiles ? \array_merge(...$namespacedFiles) : [];
    }

    /**
     * Get a list of all files inside a directory.
     *
     * @param string $directory Absolute directory path
     *
     * @return array<int,string>|null Returns an array of filenames or null
     * if the directory can not be resolved
     */
    public function listFiles(string $directory) : ?array
    {
        $directory = \realpath($directory);
        if ($directory === false) {
            return null;
        }
        $directory .= \DIRECTORY_SEPARATOR;
        $files = [];
        foreach ((array) \scandir($directory, 0) as $filename) {
            if ($filename === '.' || $filename === '..') {
                continue;
            }
            $filename = $directory . $filename;
            if (\is_file($filename)) {
                $files[] = $filename;
                continue;
            }
            foreach ($this->listFiles($filename) as $subDirectory) {
                $files[] = $subDirectory;
            }
        }
        return $files;
    }
}

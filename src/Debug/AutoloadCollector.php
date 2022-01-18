<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Autoload Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Autoload\Debug;

use Framework\Autoload\Autoloader;
use Framework\Autoload\Preloader;
use Framework\Debug\Collector;

/**
 * Class AutoloadCollector.
 *
 * @package autoload
 */
class AutoloadCollector extends Collector
{
    protected Autoloader $autoloader;

    public function setAutoloader(Autoloader $autoloader) : static
    {
        $this->autoloader = $autoloader;
        return $this;
    }

    public function getContents() : string
    {
        \ob_start(); ?>
        <h1>Autoloader</h1>
        <?= $this->renderAutoloader() ?>
        <h1>Included Files</h1>
        <?php
        $includedFiles = Preloader::getIncludedFiles(); ?>
        <p>Total of <?= \count($includedFiles) ?> included files.</p>
        <table>
            <thead>
            <tr>
                <th>Order</th>
                <th>File</th>
                <th title="Seconds">Time</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($includedFiles as $index => $file): ?>
                <?php
                $data = $this->getDataByFile($file); ?>
                <tr<?= $data
                    ? ' class="active" title="Included with the current Autoloader"'
                    : '' ?>>
                    <td><?= $index + 1 ?></td>
                    <td><?= \htmlentities($file) ?></td>
                    <td><?= $data ? \round($data['end'] - $data['start'], 6) : '' ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <h1>Preload</h1>
        <?= $this->renderPreload() ?>
        <h1>Declarations</h1>
        <?php
        $declarations = Preloader::getAllDeclarations(); ?>
        <p>Total of <?= \count($declarations) ?> declarations.</p>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Type</th>
                <th>Declaration</th>
                <th title="Loaded with the current Autoloader">Loaded</th>
                <th title="Seconds">Time</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($declarations as $index => $declaration): ?>
                <?php
                $data = $this->getDataByDeclaration($declaration); ?>
                <tr<?= $data ? ' class="active" title="Searched with the current Autoloader"'
                    : '' ?>>
                    <td><?= $index + 1 ?></td>
                    <td><?= $this->getDeclarationType($declaration) ?></td>
                    <td><?= $declaration ?></td>
                    <td><?php
                        if ($data && isset($data['loaded'])) {
                            echo $data['loaded'] ? 'Yes' : 'No';
                        } ?></td>
                    <td><?= $data ? \round($data['end'] - $data['start'], 6) : '' ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    /**
     * @param string $file
     *
     * @return array<string,mixed>|false
     */
    protected function getDataByFile(string $file) : array | false
    {
        foreach ($this->getData() as $data) {
            if ($data['file'] === $file) {
                return $data;
            }
        }
        return false;
    }

    /**
     * @param string $declaration
     *
     * @return array<string,mixed>|false
     */
    protected function getDataByDeclaration(string $declaration) : array | false
    {
        foreach ($this->getData() as $data) {
            if ($data['class'] === $declaration) {
                return $data;
            }
        }
        return false;
    }

    protected function renderAutoloader() : string
    {
        if ( ! isset($this->autoloader)) {
            return '<p>An Autoloader instance has not been set on this collector.</p>';
        }
        \ob_start(); ?>
        <h2>Namespaces</h2>
        <?= $this->renderNamespaces() ?>
        <h2>Classes</h2>
        <?php
        echo $this->renderClasses();
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    protected function renderNamespaces() : string
    {
        $namespaces = $this->autoloader->getNamespaces();
        if (empty($namespaces)) {
            return '<p>No namespace directory has been set on this Autoloader instance.</p>';
        }
        \ksort($namespaces);
        \ob_start(); ?>
        <table>
            <thead>
            <tr>
                <th>Namespace</th>
                <th>Directory</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($namespaces as $namespace => $directory): ?>
                <tr>
                    <td><?= \htmlentities($namespace) ?></td>
                    <td><?= \htmlentities($directory) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    protected function renderClasses() : string
    {
        $classes = $this->autoloader->getClasses();
        if (empty($classes)) {
            return '<p>No class file has been set on this Autoloader instance.</p>';
        }
        \ksort($classes);
        \ob_start(); ?>
        <table>
            <thead>
            <tr>
                <th>Class</th>
                <th>File</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($classes as $class => $file): ?>
                <tr>
                    <td><?= \htmlentities($class) ?></td>
                    <td><?= \htmlentities($file) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    protected function renderPreload() : string
    {
        if ( ! \function_exists('opcache_get_configuration')) {
            return '<p>Preload is not available.</p>';
        }
        $conf = \opcache_get_configuration();
        if ($conf && $conf['directives']['opcache.preload']) {
            return '<p><strong>File:</strong> ' . \htmlentities($conf['directives']['opcache.preload']) . '</p>'
                . '<p><strong>User:</strong> ' . \htmlentities($conf['directives']['opcache.preload_user']) . '</p>';
        }
        return '<p>Preload file has not been set.</p>';
    }

    protected function getDeclarationType(string $declaration) : string
    {
        if (\in_array($declaration, \get_declared_classes(), true)) {
            return 'class';
        }
        if (\in_array($declaration, \get_declared_interfaces(), true)) {
            return 'interface';
        }
        if (\in_array($declaration, \get_declared_traits(), true)) {
            return 'trait';
        }
        return '';
    }
}

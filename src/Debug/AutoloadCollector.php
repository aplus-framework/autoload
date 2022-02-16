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
use UnitEnum;

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

    public function getActivities() : array
    {
        $activities = [];
        foreach ($this->getData() as $data) {
            if ($data['loaded']) {
                $activities[] = [
                    'collector' => $this->getName(),
                    'class' => static::class,
                    'description' => 'Load class ' . $data['class'],
                    'start' => $data['start'],
                    'end' => $data['end'],
                ];
            }
        }
        return $activities;
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
                <th>#</th>
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
                <th>Directories</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($namespaces as $namespace => $directories): ?>
                <?php $count = \count($directories); ?>
                <tr>
                    <td rowspan="<?= $count ?>"><?= \htmlentities($namespace) ?></td>
                    <td><?= \htmlentities($directories[0]) ?></td>
                </tr>
                <?php for ($i = 1; $i < $count; $i++): ?>
                    <tr>
                        <td><?= \htmlentities($directories[$i]) ?></td>
                    </tr>
                <?php endfor ?>
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
        $conf = $this->getOpcacheConfiguration();
        if ($conf === null) {
            return '<p>Preload is not available.</p>';
        }
        if ($conf && ! empty($conf['directives']['opcache.preload'])) {
            $result = '<p><strong>File:</strong> '
                . \htmlentities($conf['directives']['opcache.preload']) . '</p>';
            if ( ! empty($conf['directives']['opcache.preload_user'])) {
                $result .= '<p><strong>User:</strong> '
                    . \htmlentities($conf['directives']['opcache.preload_user']) . '</p>';
            }
            return $result;
        }
        return '<p>Preload file has not been set.</p>';
    }

    /**
     * @return array<string,mixed>|null
     */
    protected function getOpcacheConfiguration() : array | null
    {
        return \function_exists('opcache_get_configuration')
            ? (array) \opcache_get_configuration()
            : null;
    }

    protected function getDeclarationType(string $declaration) : string
    {
        if (\in_array($declaration, \get_declared_classes(), true)) {
            return \is_subclass_of($declaration, UnitEnum::class) ? 'enum' : 'class';
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

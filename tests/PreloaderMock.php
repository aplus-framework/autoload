<?php
/*
 * This file is part of Aplus Framework Autoload Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Autoload;

use Framework\Autoload\Preloader;

/**
 * Class PreloaderMock.
 */
class PreloaderMock extends Preloader
{
    public string $packagesDir = __DIR__ . '/../vendor/aplus/';

    public function setNamespaces() : static
    {
        return parent::setNamespaces();
    }
}

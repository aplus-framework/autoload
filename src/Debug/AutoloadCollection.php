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

use Framework\Debug\Collection;

/**
 * Class AutoloadCollection.
 *
 * @package autoload
 */
class AutoloadCollection extends Collection
{
    protected string $iconPath = __DIR__ . '/icons/autoload.svg';
}

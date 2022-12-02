<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix202212\Symfony\Component\DependencyInjection\LazyProxy\Instantiator;

use ECSPrefix202212\Symfony\Component\DependencyInjection\ContainerInterface;
use ECSPrefix202212\Symfony\Component\DependencyInjection\Definition;
use ECSPrefix202212\Symfony\Component\DependencyInjection\LazyProxy\PhpDumper\LazyServiceDumper;
use ECSPrefix202212\Symfony\Component\VarExporter\LazyGhostTrait;
/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
final class LazyServiceInstantiator implements InstantiatorInterface
{
    public function instantiateProxy(ContainerInterface $container, Definition $definition, string $id, callable $realInstantiator) : object
    {
        $dumper = new LazyServiceDumper();
        if (!\class_exists($proxyClass = $dumper->getProxyClass($definition, $class), \false)) {
            eval($dumper->getProxyCode($definition, $id));
        }
        return isset(\class_uses($proxyClass)[LazyGhostTrait::class]) ? $proxyClass::createLazyGhost($realInstantiator) : $proxyClass::createLazyProxy($realInstantiator);
    }
}

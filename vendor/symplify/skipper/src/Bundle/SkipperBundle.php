<?php

declare (strict_types=1);
namespace ECSPrefix20210911\Symplify\Skipper\Bundle;

use ECSPrefix20210911\Symfony\Component\HttpKernel\Bundle\Bundle;
use ECSPrefix20210911\Symplify\Skipper\DependencyInjection\Extension\SkipperExtension;
final class SkipperBundle extends \ECSPrefix20210911\Symfony\Component\HttpKernel\Bundle\Bundle
{
    protected function createContainerExtension() : ?\ECSPrefix20210911\Symfony\Component\DependencyInjection\Extension\ExtensionInterface
    {
        return new \ECSPrefix20210911\Symplify\Skipper\DependencyInjection\Extension\SkipperExtension();
    }
}

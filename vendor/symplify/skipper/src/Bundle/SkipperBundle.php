<?php

declare (strict_types=1);
namespace ECSPrefix20210526\Symplify\Skipper\Bundle;

use ECSPrefix20210526\Symfony\Component\HttpKernel\Bundle\Bundle;
use ECSPrefix20210526\Symplify\Skipper\DependencyInjection\Extension\SkipperExtension;
final class SkipperBundle extends \ECSPrefix20210526\Symfony\Component\HttpKernel\Bundle\Bundle
{
    /**
     * @return \Symfony\Component\DependencyInjection\Extension\ExtensionInterface|null
     */
    protected function createContainerExtension()
    {
        return new \ECSPrefix20210526\Symplify\Skipper\DependencyInjection\Extension\SkipperExtension();
    }
}

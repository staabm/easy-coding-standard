<?php

declare (strict_types=1);
namespace ECSPrefix20210911\Symplify\EasyTesting\HttpKernel;

use ECSPrefix20210911\Symfony\Component\Config\Loader\LoaderInterface;
use ECSPrefix20210911\Symplify\SymplifyKernel\HttpKernel\AbstractSymplifyKernel;
final class EasyTestingKernel extends \ECSPrefix20210911\Symplify\SymplifyKernel\HttpKernel\AbstractSymplifyKernel
{
    /**
     * @param \Symfony\Component\Config\Loader\LoaderInterface $loader
     */
    public function registerContainerConfiguration($loader) : void
    {
        $loader->load(__DIR__ . '/../../config/config.php');
    }
}

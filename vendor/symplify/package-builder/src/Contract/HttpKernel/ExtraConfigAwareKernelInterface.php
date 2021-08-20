<?php

declare (strict_types=1);
namespace ECSPrefix20210820\Symplify\PackageBuilder\Contract\HttpKernel;

use ECSPrefix20210820\Symfony\Component\HttpKernel\KernelInterface;
use ECSPrefix20210820\Symplify\SmartFileSystem\SmartFileInfo;
interface ExtraConfigAwareKernelInterface extends \ECSPrefix20210820\Symfony\Component\HttpKernel\KernelInterface
{
    /**
     * @param string[]|SmartFileInfo[] $configs
     */
    public function setConfigs($configs) : void;
}

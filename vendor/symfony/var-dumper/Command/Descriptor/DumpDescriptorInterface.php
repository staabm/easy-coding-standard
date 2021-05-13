<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20210513\Symfony\Component\VarDumper\Command\Descriptor;

use ECSPrefix20210513\Symfony\Component\Console\Output\OutputInterface;
use ECSPrefix20210513\Symfony\Component\VarDumper\Cloner\Data;
/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
interface DumpDescriptorInterface
{
    /**
     * @return void
     * @param int $clientId
     */
    public function describe(\ECSPrefix20210513\Symfony\Component\Console\Output\OutputInterface $output, \ECSPrefix20210513\Symfony\Component\VarDumper\Cloner\Data $data, array $context, $clientId);
}

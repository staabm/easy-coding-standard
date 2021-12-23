<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20211223\Symfony\Component\DependencyInjection\Argument;

use ECSPrefix20211223\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use ECSPrefix20211223\Symfony\Component\DependencyInjection\Reference;
/**
 * Represents a service wrapped in a memoizing closure.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class ServiceClosureArgument implements \ECSPrefix20211223\Symfony\Component\DependencyInjection\Argument\ArgumentInterface
{
    /**
     * @var mixed[]
     */
    private $values;
    public function __construct(\ECSPrefix20211223\Symfony\Component\DependencyInjection\Reference $reference)
    {
        $this->values = [$reference];
    }
    /**
     * {@inheritdoc}
     */
    public function getValues() : array
    {
        return $this->values;
    }
    /**
     * {@inheritdoc}
     */
    public function setValues(array $values)
    {
        if ([0] !== \array_keys($values) || !($values[0] instanceof \ECSPrefix20211223\Symfony\Component\DependencyInjection\Reference || null === $values[0])) {
            throw new \ECSPrefix20211223\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException('A ServiceClosureArgument must hold one and only one Reference.');
        }
        $this->values = $values;
    }
}

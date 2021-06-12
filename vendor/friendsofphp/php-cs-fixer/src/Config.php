<?php

declare (strict_types=1);
/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace PhpCsFixer;

use PhpCsFixer\Fixer\FixerInterface;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Katsuhiro Ogawa <ko.fivestar@gmail.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class Config implements \PhpCsFixer\ConfigInterface
{
    private $cacheFile = '.php-cs-fixer.cache';
    private $customFixers = [];
    private $finder;
    private $format = 'txt';
    private $hideProgress = \false;
    private $indent = '    ';
    private $isRiskyAllowed = \false;
    private $lineEnding = "\n";
    private $name;
    private $phpExecutable;
    private $rules = ['@PSR12' => \true];
    private $usingCache = \true;
    public function __construct(string $name = 'default')
    {
        $this->name = $name;
    }
    /**
     * {@inheritdoc}
     * @return string|null
     */
    public function getCacheFile()
    {
        return $this->cacheFile;
    }
    /**
     * {@inheritdoc}
     */
    public function getCustomFixers() : array
    {
        return $this->customFixers;
    }
    /**
     * @return mixed[]
     */
    public function getFinder()
    {
        if (null === $this->finder) {
            $this->finder = new \PhpCsFixer\Finder();
        }
        return $this->finder;
    }
    /**
     * {@inheritdoc}
     */
    public function getFormat() : string
    {
        return $this->format;
    }
    /**
     * {@inheritdoc}
     */
    public function getHideProgress() : bool
    {
        return $this->hideProgress;
    }
    /**
     * {@inheritdoc}
     */
    public function getIndent() : string
    {
        return $this->indent;
    }
    /**
     * {@inheritdoc}
     */
    public function getLineEnding() : string
    {
        return $this->lineEnding;
    }
    /**
     * {@inheritdoc}
     */
    public function getName() : string
    {
        return $this->name;
    }
    /**
     * {@inheritdoc}
     * @return string|null
     */
    public function getPhpExecutable()
    {
        return $this->phpExecutable;
    }
    /**
     * {@inheritdoc}
     */
    public function getRiskyAllowed() : bool
    {
        return $this->isRiskyAllowed;
    }
    /**
     * {@inheritdoc}
     */
    public function getRules() : array
    {
        return $this->rules;
    }
    /**
     * {@inheritdoc}
     */
    public function getUsingCache() : bool
    {
        return $this->usingCache;
    }
    /**
     * {@inheritdoc}
     * @param mixed[] $fixers
     */
    public function registerCustomFixers($fixers) : \PhpCsFixer\ConfigInterface
    {
        foreach ($fixers as $fixer) {
            $this->addCustomFixer($fixer);
        }
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function setCacheFile(string $cacheFile) : \PhpCsFixer\ConfigInterface
    {
        $this->cacheFile = $cacheFile;
        return $this;
    }
    /**
     * {@inheritdoc}
     * @param mixed[] $finder
     */
    public function setFinder($finder) : \PhpCsFixer\ConfigInterface
    {
        $this->finder = $finder;
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function setFormat(string $format) : \PhpCsFixer\ConfigInterface
    {
        $this->format = $format;
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function setHideProgress(bool $hideProgress) : \PhpCsFixer\ConfigInterface
    {
        $this->hideProgress = $hideProgress;
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function setIndent(string $indent) : \PhpCsFixer\ConfigInterface
    {
        $this->indent = $indent;
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function setLineEnding(string $lineEnding) : \PhpCsFixer\ConfigInterface
    {
        $this->lineEnding = $lineEnding;
        return $this;
    }
    /**
     * {@inheritdoc}
     * @param string|null $phpExecutable
     */
    public function setPhpExecutable($phpExecutable) : \PhpCsFixer\ConfigInterface
    {
        $this->phpExecutable = $phpExecutable;
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function setRiskyAllowed(bool $isRiskyAllowed) : \PhpCsFixer\ConfigInterface
    {
        $this->isRiskyAllowed = $isRiskyAllowed;
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function setRules(array $rules) : \PhpCsFixer\ConfigInterface
    {
        $this->rules = $rules;
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function setUsingCache(bool $usingCache) : \PhpCsFixer\ConfigInterface
    {
        $this->usingCache = $usingCache;
        return $this;
    }
    /**
     * @return void
     */
    private function addCustomFixer(\PhpCsFixer\Fixer\FixerInterface $fixer)
    {
        $this->customFixers[] = $fixer;
    }
}
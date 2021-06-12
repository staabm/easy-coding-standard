<?php

declare (strict_types=1);
namespace ECSPrefix20210612\Symplify\RuleDocGenerator\ValueObject;

use ECSPrefix20210612\Nette\Utils\Strings;
use ECSPrefix20210612\Symplify\RuleDocGenerator\Contract\CodeSampleInterface;
use ECSPrefix20210612\Symplify\RuleDocGenerator\Exception\PoorDocumentationException;
use ECSPrefix20210612\Symplify\RuleDocGenerator\Exception\ShouldNotHappenException;
use ECSPrefix20210612\Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
final class RuleDefinition
{
    /**
     * @var string
     */
    private $description;
    /**
     * @var string
     */
    private $ruleClass;
    /**
     * @var string
     */
    private $ruleFilePath;
    /**
     * @var CodeSampleInterface[]
     */
    private $codeSamples = [];
    /**
     * @param CodeSampleInterface[] $codeSamples
     */
    public function __construct(string $description, array $codeSamples)
    {
        $this->description = $description;
        if ($codeSamples === []) {
            throw new \ECSPrefix20210612\Symplify\RuleDocGenerator\Exception\PoorDocumentationException('Provide at least one code sample, so people can practically see what the rule does');
        }
        $this->codeSamples = $codeSamples;
    }
    public function getDescription() : string
    {
        return $this->description;
    }
    /**
     * @return void
     */
    public function setRuleClass(string $ruleClass)
    {
        $this->ruleClass = $ruleClass;
    }
    public function getRuleClass() : string
    {
        if ($this->ruleClass === null) {
            throw new \ECSPrefix20210612\Symplify\RuleDocGenerator\Exception\ShouldNotHappenException();
        }
        return $this->ruleClass;
    }
    /**
     * @return void
     */
    public function setRuleFilePath(string $ruleFilePath)
    {
        // fir relative file path for GitHub
        $this->ruleFilePath = \ltrim($ruleFilePath, '/');
    }
    public function getRuleFilePath() : string
    {
        if ($this->ruleFilePath === null) {
            throw new \ECSPrefix20210612\Symplify\RuleDocGenerator\Exception\ShouldNotHappenException();
        }
        return $this->ruleFilePath;
    }
    public function getRuleShortClass() : string
    {
        return (string) \ECSPrefix20210612\Nette\Utils\Strings::after($this->ruleClass, '\\', -1);
    }
    /**
     * @return CodeSampleInterface[]
     */
    public function getCodeSamples() : array
    {
        return $this->codeSamples;
    }
    public function isConfigurable() : bool
    {
        foreach ($this->codeSamples as $codeSample) {
            if ($codeSample instanceof \ECSPrefix20210612\Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample) {
                return \true;
            }
        }
        return \false;
    }
}
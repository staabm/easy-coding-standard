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
namespace PhpCsFixer\Documentation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Console\Command\HelpCommand;
use PhpCsFixer\Differ\FullDiffer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerConfiguration\AliasedFixerOption;
use PhpCsFixer\FixerConfiguration\AllowedValueSubset;
use PhpCsFixer\FixerConfiguration\DeprecatedFixerOptionInterface;
use PhpCsFixer\FixerDefinition\CodeSampleInterface;
use PhpCsFixer\FixerDefinition\FileSpecificCodeSampleInterface;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSampleInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\RuleSet\RuleSet;
use PhpCsFixer\RuleSet\RuleSetDescriptionInterface;
use PhpCsFixer\RuleSet\RuleSets;
use PhpCsFixer\StdinFileInfo;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Utils;
/**
 * @internal
 */
final class DocumentationGenerator
{
    /**
     * @var FullDiffer
     */
    private $differ;
    private $path;
    public function __construct()
    {
        $this->differ = new \PhpCsFixer\Differ\FullDiffer();
        $this->path = \dirname(__DIR__, 2) . '/doc';
    }
    public function getFixersDocumentationDirectoryPath() : string
    {
        return $this->path . '/rules';
    }
    public function getFixersDocumentationIndexFilePath() : string
    {
        return $this->getFixersDocumentationDirectoryPath() . '/index.rst';
    }
    /**
     * @param AbstractFixer[] $fixers
     */
    public function generateFixersDocumentationIndex(array $fixers) : string
    {
        $overrideGroups = ['PhpUnit' => 'PHPUnit', 'PhpTag' => 'PHP Tag', 'Phpdoc' => 'PHPDoc'];
        \usort($fixers, function (\PhpCsFixer\Fixer\FixerInterface $a, \PhpCsFixer\Fixer\FixerInterface $b) {
            return \strcmp(\get_class($a), \get_class($b));
        });
        $documentation = <<<'RST'
=======================
List of Available Rules
=======================
RST;
        $currentGroup = null;
        foreach ($fixers as $fixer) {
            $namespace = \PhpCsFixer\Preg::replace('/^.*\\\\(.+)\\\\.+Fixer$/', '$1', \get_class($fixer));
            if (isset($overrideGroups[$namespace])) {
                $group = $overrideGroups[$namespace];
            } else {
                $group = \PhpCsFixer\Preg::replace('/(?<=[[:lower:]])(?=[[:upper:]])/', ' ', $namespace);
            }
            if ($group !== $currentGroup) {
                $underline = \str_repeat('-', \strlen($group));
                $documentation .= "\n\n{$group}\n{$underline}\n";
                $currentGroup = $group;
            }
            $summary = \str_replace('`', '``', $fixer->getDefinition()->getSummary());
            $attributes = [];
            if ($fixer instanceof \PhpCsFixer\Fixer\DeprecatedFixerInterface) {
                $attributes[] = 'deprecated';
            }
            if ($fixer->isRisky()) {
                $attributes[] = 'risky';
            }
            if ([] !== $attributes) {
                $attributes = ' *(' . \implode(', ', $attributes) . ')*';
            } else {
                $attributes = '';
            }
            $path = './' . $this->getFixerDocumentationFileRelativePath($fixer);
            $documentation .= <<<RST

- `{$fixer->getName()} <{$path}>`_{$attributes}
    {$summary}
RST;
        }
        return "{$documentation}\n";
    }
    public function getFixerDocumentationFilePath(\PhpCsFixer\Fixer\FixerInterface $fixer) : string
    {
        return $this->getFixersDocumentationDirectoryPath() . '/' . \PhpCsFixer\Preg::replaceCallback('/^.*\\\\(.+)\\\\(.+)Fixer$/', function (array $matches) {
            return \PhpCsFixer\Utils::camelCaseToUnderscore($matches[1]) . '/' . \PhpCsFixer\Utils::camelCaseToUnderscore($matches[2]);
        }, \get_class($fixer)) . '.rst';
    }
    public function getFixerDocumentationFileRelativePath(\PhpCsFixer\Fixer\FixerInterface $fixer) : string
    {
        return \PhpCsFixer\Preg::replace('#^' . \preg_quote($this->getFixersDocumentationDirectoryPath(), '#') . '/#', '', $this->getFixerDocumentationFilePath($fixer));
    }
    public function generateFixerDocumentation(\PhpCsFixer\Fixer\FixerInterface $fixer) : string
    {
        $name = $fixer->getName();
        $title = "Rule ``{$name}``";
        $titleLine = \str_repeat('=', \strlen($title));
        $doc = "{$titleLine}\n{$title}\n{$titleLine}";
        if ($fixer instanceof \PhpCsFixer\Fixer\DeprecatedFixerInterface) {
            $doc .= "\n\n.. warning:: This rule is deprecated and will be removed on next major version.";
            $alternatives = $fixer->getSuccessorsNames();
            if ([] !== $alternatives) {
                $doc .= $this->toRst(\sprintf("\n\nYou should use %s instead.", \PhpCsFixer\Utils::naturalLanguageJoinWithBackticks($alternatives)), 3);
            }
        }
        $riskyDescription = null;
        $samples = [];
        if ($fixer instanceof \PhpCsFixer\Fixer\FixerInterface) {
            $definition = $fixer->getDefinition();
            $doc .= "\n\n" . $this->toRst($definition->getSummary());
            $description = $definition->getDescription();
            if (null !== $description) {
                $description = $this->toRst($description);
                $doc .= <<<RST


Description
-----------

{$description}
RST;
            }
            $riskyDescription = $definition->getRiskyDescription();
            $samples = $definition->getCodeSamples();
        }
        if (null !== $riskyDescription) {
            $riskyDescription = $this->toRst($riskyDescription, 3);
            $doc .= <<<RST


.. warning:: Using this rule is risky.

   {$riskyDescription}
RST;
        }
        if ($fixer instanceof \PhpCsFixer\Fixer\ConfigurableFixerInterface) {
            $doc .= <<<'RST'


Configuration
-------------
RST;
            $configurationDefinition = $fixer->getConfigurationDefinition();
            foreach ($configurationDefinition->getOptions() as $option) {
                $optionInfo = "``{$option->getName()}``";
                $optionInfo .= "\n" . \str_repeat('~', \strlen($optionInfo));
                if ($option instanceof \PhpCsFixer\FixerConfiguration\DeprecatedFixerOptionInterface) {
                    $optionInfo .= "\n\n.. warning:: This option is deprecated and will be removed on next major version. {$this->toRst($option->getDeprecationMessage())}";
                }
                $optionInfo .= "\n\n" . $this->toRst($option->getDescription());
                if ($option instanceof \PhpCsFixer\FixerConfiguration\AliasedFixerOption) {
                    $optionInfo .= "\n\n.. note:: The previous name of this option was ``{$option->getAlias()}`` but it is now deprecated and will be removed on next major version.";
                }
                $allowed = \PhpCsFixer\Console\Command\HelpCommand::getDisplayableAllowedValues($option);
                $allowedKind = 'Allowed values';
                if (null !== $allowed) {
                    foreach ($allowed as &$value) {
                        if ($value instanceof \PhpCsFixer\FixerConfiguration\AllowedValueSubset) {
                            $value = 'a subset of ``' . \PhpCsFixer\Console\Command\HelpCommand::toString($value->getAllowedValues()) . '``';
                        } else {
                            $value = '``' . \PhpCsFixer\Console\Command\HelpCommand::toString($value) . '``';
                        }
                    }
                } else {
                    $allowedKind = 'Allowed types';
                    $allowed = \array_map(function ($value) {
                        return '``' . $value . '``';
                    }, $option->getAllowedTypes());
                }
                if (null !== $allowed) {
                    $allowed = \implode(', ', $allowed);
                    $optionInfo .= "\n\n{$allowedKind}: {$allowed}";
                }
                if ($option->hasDefault()) {
                    $default = \PhpCsFixer\Console\Command\HelpCommand::toString($option->getDefault());
                    $optionInfo .= "\n\nDefault value: ``{$default}``";
                } else {
                    $optionInfo .= "\n\nThis option is required.";
                }
                $doc .= "\n\n{$optionInfo}";
            }
        }
        if (0 !== \count($samples)) {
            $doc .= <<<'RST'


Examples
--------
RST;
            foreach ($samples as $index => $sample) {
                $title = \sprintf('Example #%d', $index + 1);
                $titleLine = \str_repeat('~', \strlen($title));
                $doc .= "\n\n{$title}\n{$titleLine}";
                if ($fixer instanceof \PhpCsFixer\Fixer\ConfigurableFixerInterface) {
                    if (null === $sample->getConfiguration()) {
                        $doc .= "\n\n*Default* configuration.";
                    } else {
                        $doc .= \sprintf("\n\nWith configuration: ``%s``.", \PhpCsFixer\Console\Command\HelpCommand::toString($sample->getConfiguration()));
                    }
                }
                $doc .= "\n" . $this->generateSampleDiff($fixer, $sample, $index + 1, $name);
            }
        }
        $ruleSetConfigs = [];
        foreach (\PhpCsFixer\RuleSet\RuleSets::getSetDefinitionNames() as $set) {
            $ruleSet = new \PhpCsFixer\RuleSet\RuleSet([$set => \true]);
            if ($ruleSet->hasRule($name)) {
                $ruleSetConfigs[$set] = $ruleSet->getRuleConfiguration($name);
            }
        }
        if ([] !== $ruleSetConfigs) {
            $plural = 1 !== \count($ruleSetConfigs) ? 's' : '';
            $doc .= <<<RST


Rule sets
---------

The rule is part of the following rule set{$plural}:
RST;
            foreach ($ruleSetConfigs as $set => $config) {
                $ruleSetPath = $this->getRuleSetsDocumentationFilePath($set);
                $ruleSetPath = \substr($ruleSetPath, \strrpos($ruleSetPath, '/'));
                $doc .= <<<RST


{$set}
  Using the `{$set} <./../../ruleSets{$ruleSetPath}>`_ rule set will enable the ``{$name}`` rule
RST;
                if (null !== $config) {
                    $doc .= " with the config below:\n\n  ``" . \PhpCsFixer\Console\Command\HelpCommand::toString($config) . '``';
                } elseif ($fixer instanceof \PhpCsFixer\Fixer\ConfigurableFixerInterface) {
                    $doc .= ' with the default config.';
                } else {
                    $doc .= '.';
                }
            }
        }
        return "{$doc}\n";
    }
    public function getRuleSetsDocumentationDirectoryPath() : string
    {
        return $this->path . '/ruleSets';
    }
    public function getRuleSetsDocumentationIndexFilePath() : string
    {
        return $this->getRuleSetsDocumentationDirectoryPath() . '/index.rst';
    }
    /**
     * @param AbstractFixer[] $fixers
     */
    public function generateRuleSetsDocumentation(\PhpCsFixer\RuleSet\RuleSetDescriptionInterface $definition, array $fixers) : string
    {
        $fixerNames = [];
        foreach ($fixers as $fixer) {
            $fixerNames[$fixer->getName()] = $fixer;
        }
        $title = "Rule set ``{$definition->getName()}``";
        $titleLine = \str_repeat('=', \strlen($title));
        $doc = "{$titleLine}\n{$title}\n{$titleLine}\n\n" . $definition->getDescription();
        if ($definition->isRisky()) {
            $doc .= ' This set contains rules that are risky.';
        }
        $doc .= "\n\n";
        $rules = $definition->getRules();
        if (\count($rules) < 1) {
            $doc .= 'This is an empty set.';
        } else {
            $doc .= "Rules\n-----\n";
            foreach ($rules as $rule => $config) {
                if ('@' === $rule[0]) {
                    $ruleSetPath = $this->getRuleSetsDocumentationFilePath($rule);
                    $ruleSetPath = \substr($ruleSetPath, \strrpos($ruleSetPath, '/'));
                    $doc .= "\n- `{$rule} <.{$ruleSetPath}>`_";
                } else {
                    $path = \PhpCsFixer\Preg::replace('#^' . \preg_quote($this->getFixersDocumentationDirectoryPath(), '#') . '/#', './../rules/', $this->getFixerDocumentationFilePath($fixerNames[$rule]));
                    $doc .= "\n- `{$rule} <{$path}>`_";
                }
                if (!\is_bool($config)) {
                    $doc .= "\n  config:\n  ``" . \PhpCsFixer\Console\Command\HelpCommand::toString($config) . '``';
                }
            }
        }
        return $doc . "\n";
    }
    public function getRuleSetsDocumentationFilePath(string $name) : string
    {
        return $this->getRuleSetsDocumentationDirectoryPath() . '/' . \str_replace(':risky', 'Risky', \ucfirst(\substr($name, 1))) . '.rst';
    }
    public function generateRuleSetsDocumentationIndex(array $setDefinitions) : string
    {
        $documentation = <<<'RST'
===========================
List of Available Rule sets
===========================
RST;
        foreach ($setDefinitions as $name => $path) {
            $path = \substr($path, \strrpos($path, '/'));
            $documentation .= "\n- `{$name} <.{$path}>`_";
        }
        return $documentation . "\n";
    }
    private function generateSampleDiff(\PhpCsFixer\Fixer\FixerInterface $fixer, \PhpCsFixer\FixerDefinition\CodeSampleInterface $sample, int $sampleNumber, string $ruleName) : string
    {
        if ($sample instanceof \PhpCsFixer\FixerDefinition\VersionSpecificCodeSampleInterface && !$sample->isSuitableFor(\PHP_VERSION_ID)) {
            $existingFile = @\file_get_contents($this->getFixerDocumentationFilePath($fixer));
            if (\false !== $existingFile) {
                \PhpCsFixer\Preg::match("/\\RExample #{$sampleNumber}\\R.+?(?<diff>\\R\\.\\. code-block:: diff\\R\\R.*?)\\R(?:\\R\\S|\$)/s", $existingFile, $matches);
                if (isset($matches['diff'])) {
                    return $matches['diff'];
                }
            }
            $error = <<<RST

.. error::
   Cannot generate diff for code sample #{$sampleNumber} of rule {$ruleName}:
   the sample is not suitable for current version of PHP (%s).
RST;
            return \sprintf($error, \PHP_VERSION);
        }
        $old = $sample->getCode();
        $tokens = \PhpCsFixer\Tokenizer\Tokens::fromCode($old);
        $file = $sample instanceof \PhpCsFixer\FixerDefinition\FileSpecificCodeSampleInterface ? $sample->getSplFileInfo() : new \PhpCsFixer\StdinFileInfo();
        if ($fixer instanceof \PhpCsFixer\Fixer\ConfigurableFixerInterface) {
            $configuration = $sample->getConfiguration();
            if (null === $configuration) {
                $configuration = [];
            }
            $fixer->configure($configuration);
        }
        $fixer->fix($file, $tokens);
        $diff = $this->differ->diff($old, $tokens->generateCode());
        $diff = \PhpCsFixer\Preg::replace('/@@[ \\+\\-\\d,]+@@\\n/', '', $diff);
        $diff = \PhpCsFixer\Preg::replace('/\\r/', '^M', $diff);
        $diff = \PhpCsFixer\Preg::replace('/^ $/m', '', $diff);
        $diff = \PhpCsFixer\Preg::replace('/\\n$/', '', $diff);
        return <<<RST

.. code-block:: diff

   {$this->indent($diff, 3)}
RST;
    }
    private function toRst(string $string, int $indent = 0) : string
    {
        $string = \wordwrap(\PhpCsFixer\Preg::replace('/(?<!`)(`.*?`)(?!`)/', '`$1`', $string), 80 - $indent);
        if (0 !== $indent) {
            $string = $this->indent($string, $indent);
        }
        return $string;
    }
    private function indent(string $string, int $indent) : string
    {
        return \PhpCsFixer\Preg::replace('/(\\n)(?!\\n|$)/', '$1' . \str_repeat(' ', $indent), $string);
    }
}
<?php

namespace Symplify\EasyCodingStandard\Configuration;

use ECSPrefix20210513\Symfony\Component\Console\Input\InputInterface;
use Symplify\EasyCodingStandard\Console\Output\ConsoleOutputFormatter;
use Symplify\EasyCodingStandard\Console\Output\JsonOutputFormatter;
use Symplify\EasyCodingStandard\Exception\Configuration\SourceNotFoundException;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\PackageBuilder\Parameter\ParameterProvider;
final class Configuration
{
    /**
     * @var bool
     */
    private $isFixer = \false;
    /**
     * @var bool
     */
    private $shouldClearCache = \false;
    /**
     * @var bool
     */
    private $showProgressBar = \true;
    /**
     * @var bool
     */
    private $showErrorTable = \true;
    /**
     * @var string[]
     */
    private $sources = [];
    /**
     * @var string[]
     */
    private $paths = [];
    /**
     * @var string
     */
    private $outputFormat = \Symplify\EasyCodingStandard\Console\Output\ConsoleOutputFormatter::NAME;
    /**
     * @var bool
     */
    private $doesMatchGitDiff = \false;
    public function __construct(\Symplify\PackageBuilder\Parameter\ParameterProvider $parameterProvider)
    {
        $this->paths = $parameterProvider->provideArrayParameter(\Symplify\EasyCodingStandard\ValueObject\Option::PATHS);
    }
    /**
     * Needs to run in the start of the life cycle, since the rest of workflow uses it.
     * @return void
     */
    public function resolveFromInput(\ECSPrefix20210513\Symfony\Component\Console\Input\InputInterface $input)
    {
        /** @var string[] $paths */
        $paths = (array) $input->getArgument(\Symplify\EasyCodingStandard\ValueObject\Option::PATHS);
        if ($paths !== []) {
            $this->setSources($paths);
        } else {
            // if not paths are provided from CLI, use the config ones
            $this->setSources($this->paths);
        }
        $this->isFixer = (bool) $input->getOption(\Symplify\EasyCodingStandard\ValueObject\Option::FIX);
        $this->shouldClearCache = (bool) $input->getOption(\Symplify\EasyCodingStandard\ValueObject\Option::CLEAR_CACHE);
        $this->showProgressBar = $this->canShowProgressBar($input);
        $this->showErrorTable = !(bool) $input->getOption(\Symplify\EasyCodingStandard\ValueObject\Option::NO_ERROR_TABLE);
        $this->doesMatchGitDiff = (bool) $input->getOption(\Symplify\EasyCodingStandard\ValueObject\Option::MATCH_GIT_DIFF);
        $this->setOutputFormat($input);
    }
    /**
     * @return mixed[]
     */
    public function getSources()
    {
        return $this->sources;
    }
    /**
     * @return bool
     */
    public function isFixer()
    {
        return $this->isFixer;
    }
    /**
     * @return bool
     */
    public function shouldClearCache()
    {
        return $this->shouldClearCache;
    }
    /**
     * @return bool
     */
    public function shouldShowProgressBar()
    {
        return $this->showProgressBar;
    }
    /**
     * @return bool
     */
    public function shouldShowErrorTable()
    {
        return $this->showErrorTable;
    }
    /**
     * @param string[] $sources
     * @return void
     */
    public function setSources(array $sources)
    {
        $this->ensureSourcesExists($sources);
        $this->sources = $this->normalizeSources($sources);
    }
    /**
     * @return mixed[]
     */
    public function getPaths()
    {
        return $this->paths;
    }
    /**
     * @return string
     */
    public function getOutputFormat()
    {
        return $this->outputFormat;
    }
    /**
     * @api
     * For tests
     * @return void
     */
    public function enableFixing()
    {
        $this->isFixer = \true;
    }
    /**
     * @return bool
     */
    public function doesMatchGitDiff()
    {
        return $this->doesMatchGitDiff;
    }
    /**
     * @return bool
     */
    private function canShowProgressBar(\ECSPrefix20210513\Symfony\Component\Console\Input\InputInterface $input)
    {
        $notJsonOutput = $input->getOption(\Symplify\EasyCodingStandard\ValueObject\Option::OUTPUT_FORMAT) !== \Symplify\EasyCodingStandard\Console\Output\JsonOutputFormatter::NAME;
        if (!$notJsonOutput) {
            return \false;
        }
        return !(bool) $input->getOption(\Symplify\EasyCodingStandard\ValueObject\Option::NO_PROGRESS_BAR);
    }
    /**
     * @param string[] $sources
     * @return void
     */
    private function ensureSourcesExists(array $sources)
    {
        foreach ($sources as $source) {
            if (\file_exists($source)) {
                continue;
            }
            throw new \Symplify\EasyCodingStandard\Exception\Configuration\SourceNotFoundException(\sprintf('Source "%s" does not exist.', $source));
        }
    }
    /**
     * @param string[] $sources
     * @return mixed[]
     */
    private function normalizeSources(array $sources)
    {
        foreach ($sources as $key => $value) {
            $sources[$key] = \rtrim($value, \DIRECTORY_SEPARATOR);
        }
        return $sources;
    }
    /**
     * @return void
     */
    private function setOutputFormat(\ECSPrefix20210513\Symfony\Component\Console\Input\InputInterface $input)
    {
        $outputFormat = (string) $input->getOption(\Symplify\EasyCodingStandard\ValueObject\Option::OUTPUT_FORMAT);
        // Backwards compatibility with older version
        if ($outputFormat === 'table') {
            $this->outputFormat = \Symplify\EasyCodingStandard\Console\Output\ConsoleOutputFormatter::NAME;
        }
        $this->outputFormat = $outputFormat;
    }
}

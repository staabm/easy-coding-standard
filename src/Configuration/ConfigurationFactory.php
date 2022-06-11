<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Configuration;

use ECSPrefix20220611\Symfony\Component\Console\Input\InputInterface;
use Symplify\EasyCodingStandard\Console\Output\JsonOutputFormatter;
use Symplify\EasyCodingStandard\Exception\Configuration\SourceNotFoundException;
use Symplify\EasyCodingStandard\ValueObject\Configuration;
use Symplify\EasyCodingStandard\ValueObject\Option;
use ECSPrefix20220611\Symplify\PackageBuilder\Parameter\ParameterProvider;
final class ConfigurationFactory
{
    /**
     * @var \Symplify\PackageBuilder\Parameter\ParameterProvider
     */
    private $parameterProvider;
    public function __construct(ParameterProvider $parameterProvider)
    {
        $this->parameterProvider = $parameterProvider;
    }
    /**
     * Needs to run in the start of the life cycle, since the rest of workflow uses it.
     */
    public function createFromInput(InputInterface $input) : Configuration
    {
        $paths = $this->resolvePaths($input);
        $isFixer = (bool) $input->getOption(Option::FIX);
        $shouldClearCache = (bool) $input->getOption(Option::CLEAR_CACHE);
        $showProgressBar = $this->canShowProgressBar($input);
        $showErrorTable = !(bool) $input->getOption(Option::NO_ERROR_TABLE);
        $parallelPort = (string) $input->getOption(Option::PARALLEL_PORT);
        $parallelIdentifier = (string) $input->getOption(Option::PARALLEL_IDENTIFIER);
        $outputFormat = (string) $input->getOption(Option::OUTPUT_FORMAT);
        /** @var string|null $memoryLimit */
        $memoryLimit = $input->getOption(Option::MEMORY_LIMIT);
        $isParallel = $this->parameterProvider->provideBoolParameter(Option::PARALLEL);
        $config = $input->getOption(Option::CONFIG);
        if ($config !== null) {
            $config = (string) $config;
        }
        return new Configuration($isFixer, $shouldClearCache, $showProgressBar, $showErrorTable, $paths, $outputFormat, $isParallel, $config, $parallelPort, $parallelIdentifier, $memoryLimit);
    }
    private function canShowProgressBar(InputInterface $input) : bool
    {
        // --debug option shows more
        $debug = (bool) $input->getOption(Option::DEBUG);
        if ($debug) {
            return \false;
        }
        $notJsonOutput = $input->getOption(Option::OUTPUT_FORMAT) !== JsonOutputFormatter::NAME;
        if (!$notJsonOutput) {
            return \false;
        }
        return !(bool) $input->getOption(Option::NO_PROGRESS_BAR);
    }
    /**
     * @param string[] $paths
     */
    private function ensurePathsExists(array $paths) : void
    {
        foreach ($paths as $path) {
            if (\file_exists($path)) {
                continue;
            }
            throw new SourceNotFoundException(\sprintf('Source "%s" does not exist.', $path));
        }
    }
    /**
     * @return string[]
     */
    private function resolvePaths(InputInterface $input) : array
    {
        /** @var string[] $paths */
        $paths = (array) $input->getArgument(Option::PATHS);
        if ($paths === []) {
            // if not paths are provided from CLI, use the config ones
            $paths = $this->parameterProvider->provideArrayParameter(Option::PATHS);
        }
        $this->ensurePathsExists($paths);
        return $this->normalizePaths($paths);
    }
    /**
     * @param string[] $paths
     * @return string[]
     */
    private function normalizePaths(array $paths) : array
    {
        foreach ($paths as $key => $path) {
            $paths[$key] = \rtrim($path, \DIRECTORY_SEPARATOR);
        }
        return $paths;
    }
}

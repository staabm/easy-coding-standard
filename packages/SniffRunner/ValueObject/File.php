<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\SniffRunner\ValueObject;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\File as BaseFile;
use PHP_CodeSniffer\Fixer;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\PropertyDeclarationSniff;
use PHP_CodeSniffer\Standards\PSR2\Sniffs\Methods\MethodDeclarationSniff;
use PHP_CodeSniffer\Util\Common;
use Symplify\EasyCodingStandard\Application\AppliedCheckersCollector;
use Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle;
use Symplify\EasyCodingStandard\Error\ErrorAndDiffCollector;
use Symplify\EasyCodingStandard\SniffRunner\Exception\File\NotImplementedException;
use ECSPrefix20210612\Symplify\Skipper\Skipper\Skipper;
use ECSPrefix20210612\Symplify\SmartFileSystem\SmartFileInfo;
/**
 * @see \Symplify\EasyCodingStandard\Tests\SniffRunner\ValueObject\FileTest
 */
final class File extends \PHP_CodeSniffer\Files\File
{
    /**
     * Explicit list for classes that use only warnings. ECS only knows only errors, so this one promotes them to error.
     *
     * @var array<class-string<Sniff>>
     */
    const REPORT_WARNINGS_SNIFFS = [\PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes\PropertyDeclarationSniff::class, \PHP_CodeSniffer\Standards\PSR2\Sniffs\Methods\MethodDeclarationSniff::class];
    /**
     * @var string
     */
    public $tokenizerType = 'PHP';
    /**
     * @var string|null
     */
    private $activeSniffClass;
    /**
     * @var string|null
     */
    private $previousActiveSniffClass;
    /**
     * @var Sniff[][]
     */
    private $tokenListeners = [];
    /**
     * @var SmartFileInfo
     */
    private $fileInfo;
    /**
     * @var \Symplify\EasyCodingStandard\Error\ErrorAndDiffCollector
     */
    private $errorAndDiffCollector;
    /**
     * @var \Symplify\Skipper\Skipper\Skipper
     */
    private $skipper;
    /**
     * @var \Symplify\EasyCodingStandard\Application\AppliedCheckersCollector
     */
    private $appliedCheckersCollector;
    /**
     * @var \Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle
     */
    private $easyCodingStandardStyle;
    public function __construct(string $path, string $content, \PHP_CodeSniffer\Fixer $fixer, \Symplify\EasyCodingStandard\Error\ErrorAndDiffCollector $errorAndDiffCollector, \ECSPrefix20210612\Symplify\Skipper\Skipper\Skipper $skipper, \Symplify\EasyCodingStandard\Application\AppliedCheckersCollector $appliedCheckersCollector, \Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle $easyCodingStandardStyle)
    {
        $this->errorAndDiffCollector = $errorAndDiffCollector;
        $this->skipper = $skipper;
        $this->appliedCheckersCollector = $appliedCheckersCollector;
        $this->easyCodingStandardStyle = $easyCodingStandardStyle;
        $this->path = $path;
        $this->content = $content;
        // this property cannot be promoted as defined in constructor
        $this->fixer = $fixer;
        $this->eolChar = \PHP_CodeSniffer\Util\Common::detectLineEndings($content);
        // compat
        if (!\defined('PHP_CODESNIFFER_CBF')) {
            \define('PHP_CODESNIFFER_CBF', \false);
        }
        // parent required
        $this->config = new \PHP_CodeSniffer\Config([], \false);
        $this->config->tabWidth = 4;
        $this->config->annotations = \false;
        $this->config->encoding = 'UTF-8';
    }
    /**
     * Mimics @see
     * https://github.com/squizlabs/PHP_CodeSniffer/blob/e4da24f399d71d1077f93114a72e305286020415/src/Files/File.php#L310
     * @return void
     */
    public function process()
    {
        $this->parse();
        $this->fixer->startFile($this);
        foreach ($this->tokens as $stackPtr => $token) {
            if (!isset($this->tokenListeners[$token['code']])) {
                continue;
            }
            foreach ($this->tokenListeners[$token['code']] as $sniff) {
                if ($this->skipper->shouldSkipElementAndFileInfo($sniff, $this->fileInfo)) {
                    continue;
                }
                $this->reportActiveSniffClass($sniff);
                $sniff->process($this, $stackPtr);
            }
        }
        $this->fixedCount += $this->fixer->getFixCount();
    }
    public function getErrorCount() : int
    {
        throw new \Symplify\EasyCodingStandard\SniffRunner\Exception\File\NotImplementedException(\sprintf('Method "%s" is not needed to be public. Use "%s" service.', __METHOD__, \Symplify\EasyCodingStandard\Error\ErrorAndDiffCollector::class));
    }
    /**
     * @return mixed[]
     */
    public function getErrors() : array
    {
        throw new \Symplify\EasyCodingStandard\SniffRunner\Exception\File\NotImplementedException(\sprintf('Method "%s" is not needed to be public. Use "%s" service.', __METHOD__, \Symplify\EasyCodingStandard\Error\ErrorAndDiffCollector::class));
    }
    /**
     * Delegate to addError().
     *
     * {@inheritdoc}
     */
    public function addFixableError($error, $stackPtr, $code, $data = [], $severity = 0) : bool
    {
        $this->appliedCheckersCollector->addFileInfoAndChecker($this->fileInfo, $this->resolveFullyQualifiedCode($code));
        return !$this->shouldSkipError($error, $code, $data);
    }
    public function addError($error, $stackPtr, $code, $data = [], $severity = 0, $fixable = \false) : bool
    {
        if ($this->shouldSkipError($error, $code, $data)) {
            return \false;
        }
        return parent::addError($error, $stackPtr, $code, $data, $severity, $fixable);
    }
    /**
     * Allow only specific classes
     *
     * {@inheritdoc}
     */
    public function addWarning($warning, $stackPtr, $code, $data = [], $severity = 0, $fixable = \false) : bool
    {
        if (!$this->isSniffClassWarningAllowed($this->activeSniffClass)) {
            return \false;
        }
        return $this->addError($warning, $stackPtr, $code, $data, $severity, $fixable);
    }
    /**
     * @param Sniff[][] $tokenListeners
     * @return void
     */
    public function processWithTokenListenersAndFileInfo(array $tokenListeners, \ECSPrefix20210612\Symplify\SmartFileSystem\SmartFileInfo $fileInfo)
    {
        $this->tokenListeners = $tokenListeners;
        $this->fileInfo = $fileInfo;
        $this->process();
    }
    /**
     * Delegated from addError().
     *
     * {@inheritdoc}
     */
    protected function addMessage($isError, $message, $line, $column, $sniffClassOrCode, $data, $severity, $isFixable = \false) : bool
    {
        // skip warnings
        if (!$isError) {
            return \false;
        }
        $message = $data !== [] ? \vsprintf($message, $data) : $message;
        if ($isFixable) {
            return $isFixable;
        }
        // do not add non-fixable errors twice
        if ($this->fixer->loops > 0) {
            return \false;
        }
        $this->errorAndDiffCollector->addErrorMessage($this->fileInfo, $line, $message, $this->resolveFullyQualifiedCode($sniffClassOrCode));
        return \true;
    }
    /**
     * @return void
     */
    private function reportActiveSniffClass(\PHP_CodeSniffer\Sniffs\Sniff $sniff)
    {
        // used in other places later
        $this->activeSniffClass = \get_class($sniff);
        if (!$this->easyCodingStandardStyle->isDebug()) {
            return;
        }
        if ($this->previousActiveSniffClass === $this->activeSniffClass) {
            return;
        }
        $this->easyCodingStandardStyle->writeln('     [sniff] ' . $this->activeSniffClass);
        $this->previousActiveSniffClass = $this->activeSniffClass;
    }
    private function resolveFullyQualifiedCode(string $sniffClassOrCode) : string
    {
        if (\class_exists($sniffClassOrCode)) {
            return $sniffClassOrCode;
        }
        return $this->activeSniffClass . '.' . $sniffClassOrCode;
    }
    /**
     * @param string[] $data
     */
    private function shouldSkipError(string $error, string $code, array $data) : bool
    {
        $fullyQualifiedCode = $this->resolveFullyQualifiedCode($code);
        if ($this->skipper->shouldSkipElementAndFileInfo($fullyQualifiedCode, $this->fileInfo)) {
            return \true;
        }
        $message = $data !== [] ? \vsprintf($error, $data) : $error;
        return $this->skipper->shouldSkipElementAndFileInfo($message, $this->fileInfo);
    }
    private function isSniffClassWarningAllowed(string $sniffClass) : bool
    {
        foreach (self::REPORT_WARNINGS_SNIFFS as $reportWarningsSniff) {
            if (\is_a($sniffClass, $reportWarningsSniff, \true)) {
                return \true;
            }
        }
        return \false;
    }
}
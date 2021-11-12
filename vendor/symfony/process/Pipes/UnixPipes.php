<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20211112\Symfony\Component\Process\Pipes;

use ECSPrefix20211112\Symfony\Component\Process\Process;
/**
 * UnixPipes implementation uses unix pipes as handles.
 *
 * @author Romain Neutron <imprec@gmail.com>
 *
 * @internal
 */
class UnixPipes extends \ECSPrefix20211112\Symfony\Component\Process\Pipes\AbstractPipes
{
    private $ttyMode;
    private $ptyMode;
    private $haveReadSupport;
    public function __construct(?bool $ttyMode, bool $ptyMode, $input, bool $haveReadSupport)
    {
        $this->ttyMode = $ttyMode;
        $this->ptyMode = $ptyMode;
        $this->haveReadSupport = $haveReadSupport;
        parent::__construct($input);
    }
    /**
     * @return array
     */
    public function __sleep()
    {
        throw new \BadMethodCallException('Cannot serialize ' . __CLASS__);
    }
    public function __wakeup()
    {
        throw new \BadMethodCallException('Cannot unserialize ' . __CLASS__);
    }
    public function __destruct()
    {
        $this->close();
    }
    /**
     * {@inheritdoc}
     */
    public function getDescriptors() : array
    {
        if (!$this->haveReadSupport) {
            $nullstream = \fopen('/dev/null', 'c');
            return [['pipe', 'r'], $nullstream, $nullstream];
        }
        if ($this->ttyMode) {
            return [['file', '/dev/tty', 'r'], ['file', '/dev/tty', 'w'], ['file', '/dev/tty', 'w']];
        }
        if ($this->ptyMode && \ECSPrefix20211112\Symfony\Component\Process\Process::isPtySupported()) {
            return [['pty'], ['pty'], ['pty']];
        }
        return [
            ['pipe', 'r'],
            ['pipe', 'w'],
            // stdout
            ['pipe', 'w'],
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function getFiles() : array
    {
        return [];
    }
    /**
     * {@inheritdoc}
     * @param bool $blocking
     * @param bool $close
     */
    public function readAndWrite($blocking, $close = \false) : array
    {
        $this->unblock();
        $w = $this->write();
        $read = $e = [];
        $r = $this->pipes;
        unset($r[0]);
        // let's have a look if something changed in streams
        \set_error_handler([$this, 'handleError']);
        if (($r || $w) && \false === \stream_select($r, $w, $e, 0, $blocking ? \ECSPrefix20211112\Symfony\Component\Process\Process::TIMEOUT_PRECISION * 1000000.0 : 0)) {
            \restore_error_handler();
            // if a system call has been interrupted, forget about it, let's try again
            // otherwise, an error occurred, let's reset pipes
            if (!$this->hasSystemCallBeenInterrupted()) {
                $this->pipes = [];
            }
            return $read;
        }
        \restore_error_handler();
        foreach ($r as $pipe) {
            // prior PHP 5.4 the array passed to stream_select is modified and
            // lose key association, we have to find back the key
            $read[$type = \array_search($pipe, $this->pipes, \true)] = '';
            do {
                $data = @\fread($pipe, self::CHUNK_SIZE);
                $read[$type] .= $data;
            } while (isset($data[0]) && ($close || isset($data[self::CHUNK_SIZE - 1])));
            if (!isset($read[$type][0])) {
                unset($read[$type]);
            }
            if ($close && \feof($pipe)) {
                \fclose($pipe);
                unset($this->pipes[$type]);
            }
        }
        return $read;
    }
    /**
     * {@inheritdoc}
     */
    public function haveReadSupport() : bool
    {
        return $this->haveReadSupport;
    }
    /**
     * {@inheritdoc}
     */
    public function areOpen() : bool
    {
        return (bool) $this->pipes;
    }
}

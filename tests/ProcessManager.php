<?php
declare(strict_types=1);

namespace Rabbit\Base\Tests;

use Swoole\Atomic;
use Swoole\Event;
use Swoole\Process;

/**
 * Class ProcessManager
 * @package Rabbit\Base\Tests
 */
class ProcessManager
{
    public $parentFunc;
    public $childFunc;

    protected Atomic $atomic;
    /**
     * wait wakeup 1s default
     */
    protected float $waitTimeout = 1.0;
    protected ?int $childPid = null;
    protected int $childStatus = 255;
    protected bool $parentFirst = false;

    protected Process $childProcess;

    public function __construct()
    {
        $this->atomic = new Atomic(0);
    }

    //等待信息

    public function wakeup()
    {
        return $this->atomic->wakeup();
    }

    //唤醒等待的进程

    public function run($redirectStdout = false)
    {
        $this->childProcess = new Process(function () {
            if ($this->parentFirst) {
                $this->wait();
            }
            $this->runChildFunc();
            exit;
        }, $redirectStdout, $redirectStdout);
        if (!$this->childProcess || !$this->childProcess->start()) {
            exit("ERROR: CAN NOT CREATE PROCESS\n");
        }
        register_shutdown_function(function () {
            $this->kill();
        });
        if (!$this->parentFirst) {
            $this->wait();
        }
        $this->runParentFunc($this->childPid = $this->childProcess->pid);
        Event::wait();
        $waitInfo = Process::wait(true);
        $this->childStatus = $waitInfo['code'];
        return true;
    }

    public function wait()
    {
        return $this->atomic->wait($this->waitTimeout);
    }

    public function runChildFunc()
    {
        return call_user_func($this->childFunc);
    }

    /**
     *  Kill Child Process
     * @param bool $force
     */
    public function kill(bool $force = false)
    {
        if (!defined('PCNTL_ESRCH')) {
            define('PCNTL_ESRCH', 3);
        }
        if ($this->childPid) {
            if ($force || (!@Process::kill($this->childPid) && swoole_errno() !== PCNTL_ESRCH)) {
                if (!@Process::kill($this->childPid, SIGKILL) && swoole_errno() !== PCNTL_ESRCH) {
                    exit('KILL CHILD PROCESS ERROR');
                }
            }
        }
    }

    public function runParentFunc($pid = 0)
    {
        if (!$this->parentFunc) {
            return (function () {
                $this->kill();
            })();
        } else {
            return call_user_func($this->parentFunc, $pid);
        }
    }

    public function getChildOutput()
    {
        $this->childProcess->setBlocking(false);
        while (1) {
            $data = @$this->childProcess->read();
            if (!$data) {
                sleep(1);
            } else {
                return $data;
            }
        }
    }

    /**
     * @param $data
     */
    public function setChildOutput($data)
    {
        $this->childProcess->write($data);
    }
}
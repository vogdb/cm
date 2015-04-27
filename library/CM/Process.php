<?php

class CM_Process {

    const RESPAWN_TIMEOUT = 10;

    /** @var CM_EventHandler_EventHandler|null */
    private $_eventHandler;

    /** @var CM_Process_ForkHandler[] */
    private $_forkHandlerList = array();

    /** @var int */
    private $_forkHandlerCounter = 0;

    /**
     * @param string  $event
     * @param callable $callback
     */
    public function bind($event, callable $callback) {
        if (null === $this->_eventHandler) {
            $this->_eventHandler = new CM_EventHandler_EventHandler();

            $handler = function ($signal) {
                $this->trigger('exit', $signal);
                exit(0);
            };
            pcntl_signal(SIGTERM, $handler, false);
            pcntl_signal(SIGINT, $handler, false);
        }
        $this->_eventHandler->bind($event, $callback);
    }

    /**
     * @param string       $event
     * @param callable|null $callback
     */
    public function unbind($event, callable $callback = null) {
        if (null === $this->_eventHandler) {
            return;
        }
        $this->_eventHandler->unbind($event, $callback);
    }

    /**
     * @param string     $event
     * @param mixed|null $param1
     * @param mixed|null $param2
     */
    public function trigger($event, $param1 = null, $param2 = null) {
        if (null === $this->_eventHandler) {
            return;
        }
        $arguments = func_get_args();
        call_user_func_array(array($this->_eventHandler, 'trigger'), $arguments);
    }

    /**
     * @param Closure $workload
     * @return int
     * @throws CM_Exception
     */
    public function fork(Closure $workload) {
        if (!$this->_hasForks()) {
            $this->bind('exit', [$this, 'killChildren']);
        }
        $sequence = ++$this->_forkHandlerCounter;
        $this->_fork($workload, $sequence);
        return $sequence;
    }

    /**
     * @return int
     */
    public function getHostId() {
        return (int) hexdec(CM_Util::exec('hostid'));
    }

    /**
     * @return int
     */
    public function getProcessId() {
        return posix_getpid();
    }

    /**
     * @param int $processId
     * @return bool
     */
    public function isRunning($processId) {
        $processId = (int) $processId;
        return (false !== posix_getsid($processId));
    }

    /**
     * @param float|null $timeoutKill
     */
    public function killChildren($timeoutKill = null) {
        if (null === $timeoutKill) {
            $timeoutKill = 30;
        }
        $timeoutKill = (float) $timeoutKill;
        $signal = SIGTERM;
        $timeStart = microtime(true);
        $timeoutReached = false;
        $timeOutput = $timeStart;

        while (!empty($this->_forkHandlerList)) {
            $timeNow = microtime(true);
            $timePassed = $timeNow - $timeStart;

            if ($timePassed > $timeoutKill) {
                $signal = SIGKILL;
                $timeoutReached = true;
            }
            if ($timeNow > $timeOutput + 2 || $timeoutReached) {
                $message = join(' ', [
                    count($this->_forkHandlerList) . ' children remaining',
                    'after ' . round($timePassed, 1) . ' seconds,',
                    'killing with signal `' . $signal . '`...',
                ]);
                echo $message . PHP_EOL;
                if ($timeoutReached) {
                    $logError = new CM_Paging_Log_Error();
                    $logError->add($message, [
                        'pid'  => $this->getProcessId(),
                        'argv' => join(' ', $this->getArgv()),
                    ]);
                }
                $timeOutput = $timeNow;
            }

            foreach ($this->_forkHandlerList as $forkHandler) {
                posix_kill($forkHandler->getPid(), $signal);
            }

            usleep(1000000 * 0.05);

            foreach ($this->_forkHandlerList as $forkHandler) {
                $pid = pcntl_waitpid($forkHandler->getPid(), $status, WNOHANG);
                if ($pid > 0 || !$this->isRunning($pid)) {
                    $forkHandlerSequence = $this->_getForkHandlerSequenceByPid($forkHandler->getPid());
                    $forkHandler = $this->_forkHandlerList[$forkHandlerSequence];
                    $forkHandler->closeIpcStream();
                    unset($this->_forkHandlerList[$forkHandlerSequence]);
                    if (!$this->_hasForks()) {
                        $this->unbind('exit', [$this, 'killChildren']);
                    }
                }
            }
        }
    }

    /**
     * @param boolean|null $keepAlive
     * @return CM_Process_WorkloadResult[]
     * @throws CM_Exception
     */
    public function listenForChildren($keepAlive = null) {
        return $this->_wait($keepAlive, true);
    }

    /**
     * @param bool|null $keepAlive
     * @return CM_Process_WorkloadResult[]
     * @throws CM_Exception
     */
    public function waitForChildren($keepAlive = null) {
        return $this->_wait($keepAlive, false);
    }

    /**
     * @return string[]
     */
    public function getArgv() {
        return $_SERVER['argv'];
    }

    /**
     * @return CM_Process
     */
    public static function getInstance() {
        static $instance;
        if (!$instance) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * @return bool
     */
    protected function _hasForks() {
        return count($this->_forkHandlerList) > 0;
    }

    /**
     * @param Closure $workload
     * @param int     $sequence
     * @throws CM_Exception
     * @return int
     */
    private function _fork(Closure $workload, $sequence) {
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if (false === $sockets) {
            throw new CM_Exception('Cannot open stream socket pair');
        }
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new CM_Exception('Could not spawn child process');
        }
        if ($pid) {
            // parent
            fclose($sockets[0]);
            $this->_forkHandlerList[$sequence] = new CM_Process_ForkHandler($pid, $workload, $sockets[1]);
        } else {
            // child
            try {
                fclose($sockets[1]);
                $this->_reset();
                CM_Service_Manager::getInstance()->resetServiceInstances();
                $forkHandler = new CM_Process_ForkHandler($this->getProcessId(), $workload, $sockets[0]);
                $forkHandler->runAndSendWorkload();
                $forkHandler->closeIpcStream();
            } catch (Exception $e) {
                CM_Bootloader::getInstance()->getExceptionHandler()->handleException($e);
            }
            exit;
        }
    }

    protected function _reset() {
        $this->_eventHandler = null;
        $this->_forkHandlerList = array();
        pcntl_signal(SIGTERM, SIG_DFL);
        pcntl_signal(SIGINT, SIG_DFL);
    }

    /**
     * @param bool|null $keepAlive
     * @param boolean   $nohang
     * @return CM_Process_WorkloadResult[]
     * @throws CM_Exception
     * @throws Exception
     * @internal param callable|null $terminationCallback
     */
    private function _wait($keepAlive = null, $nohang = null) {
        $keepAlive = (bool) $keepAlive;
        $workloadResultList = array();
        $waitOption = $nohang ? WNOHANG : 0;
        if (!empty($this->_forkHandlerList)) {
            do {
                $pid = pcntl_wait($status, $waitOption);
                pcntl_signal_dispatch();
                if (-1 === $pid) {
                    throw new CM_Exception('Waiting on child processes failed');
                } elseif ($pid > 0) {
                    $forkHandlerSequence = $this->_getForkHandlerSequenceByPid($pid);
                    $forkHandler = $this->_forkHandlerList[$forkHandlerSequence];
                    $workloadResultList[$forkHandlerSequence] = $forkHandler->receiveWorkloadResult();
                    $forkHandler->closeIpcStream();
                    unset($this->_forkHandlerList[$forkHandlerSequence]);
                    if (!$this->_hasForks()) {
                        $this->unbind('exit', [$this, 'killChildren']);
                    }
                    if ($keepAlive) {
                        $warning = new CM_Exception('Respawning dead child `' . $pid . '`.', null, array('severity' => CM_Exception::WARN));
                        CM_Bootloader::getInstance()->getExceptionHandler()->handleException($warning);
                        usleep(self::RESPAWN_TIMEOUT * 1000000);
                        $this->_fork($forkHandler->getWorkload(), $forkHandlerSequence);
                    }
                }
            } while (!empty($this->_forkHandlerList) && $pid > 0);
        }
        ksort($workloadResultList);
        return $workloadResultList;
    }

    /**
     * @param int $pid
     * @return int
     * @throws CM_Exception
     */
    private function _getForkHandlerSequenceByPid($pid) {
        foreach ($this->_forkHandlerList as $sequence => $forkHandler) {
            if ($pid === $forkHandler->getPid()) {
                return $sequence;
            }
        }
        throw new CM_Exception('Cannot find reference to fork-handler with PID `' . $pid . '`.');
    }
}

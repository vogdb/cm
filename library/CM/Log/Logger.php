<?php

class CM_Log_Logger {

    const DEBUG = 100;
    const INFO = 200;
    const WARNING = 300;
    const ERROR = 400;
    const CRITICAL = 500;

    /**
     * @var array $levels Logging levels
     */
    protected static $_levels = array(
        self::DEBUG    => 'DEBUG',
        self::INFO     => 'INFO',
        self::WARNING  => 'WARNING',
        self::ERROR    => 'ERROR',
        self::CRITICAL => 'CRITICAL',
    );

    /** @var array */
    private $_handlerStructList;

    /** @var CM_Log_Context */
    private $_contextGlobal;

    /**
     * @param CM_Log_Context $contextGlobal
     * @param array|null     $handlerStructList
     */
    public function __construct(CM_Log_Context $contextGlobal, array $handlerStructList = null) {
        if (null === $handlerStructList) {
            $handlerStructList = [];
        }
        $this->_handlerStructList = [];
        $this->_contextGlobal = $contextGlobal;

        $this->addHandlers($handlerStructList);
    }

    /**
     * @param CM_Log_Record $record
     * @return CM_Log_Logger
     * @throws CM_Log_HandlingException
     */
    public function addRecord(CM_Log_Record $record) {
        $exceptionList = [];
        foreach ($this->_handlerStructList as $handlerStruct) {
            $handlerFailed = false;
            /** @var CM_Log_Handler_HandlerInterface $handler */
            $handler = $handlerStruct['handler'];
            $propagate = (bool) $handlerStruct['propagate'];
            try {
                $handler->handleRecord($record);
            } catch (Exception $e) {
                $exceptionList[] = $e;
                $handlerFailed = true;
            }
            if ($handler->isHandling($record) && !$propagate && !$handlerFailed) {
                break;
            }
        }
        if (!empty($exceptionList)) {
            $exceptionMessage = count($exceptionList) . ' handler(s) failed to process a record.';
            throw new CM_Log_HandlingException($exceptionMessage, $exceptionList);
        }
        return $this;
    }

    /**
     * @param string              $message
     * @param int                 $level
     * @param CM_Log_Context|null $context
     * @param int|null            $type
     * @return CM_Log_Logger
     * @throws CM_Log_HandlingException
     */
    public function addMessage($message, $level, CM_Log_Context $context = null, $type = null) {
        $message = (string) $message;
        $level = (int) $level;
        $type = null !== $type ? (int) $type : null;
        $context = $this->_mergeWithGlobalContext($context);
        return $this->addRecord(new CM_Log_Record($level, $message, $context, $type));
    }

    /**
     * @param Exception           $exception
     * @param CM_Log_Context|null $context
     * @param int|null            $type
     * @return CM_Log_Logger
     */
    public function addException(Exception $exception, CM_Log_Context $context = null, $type = null) {
        $context = $this->_mergeWithGlobalContext($context);
        return $this->addRecord(new CM_Log_Record_Exception($exception, $context, $type));
    }

    /**
     * @param array $handlerStructList
     */
    public function addHandlers(array $handlerStructList) {
        foreach ($handlerStructList as $handlerStruct) {
            $this->addHandler($handlerStruct);
        }
    }

    /**
     * @param array $handlerStruct
     * @throws CM_Exception_Invalid
     */
    public function addHandler(array $handlerStruct) {
        if (!isset($handlerStruct['handler'])
            || !$handlerStruct['handler'] instanceof CM_Log_Handler_HandlerInterface
            || !isset($handlerStruct['propagate'])
        ) {
            throw new CM_Exception_Invalid('Malformed handlerStruct parameter');
        }
        $this->_handlerStructList[] = $handlerStruct;
    }

    /**
     * @param string              $message
     * @param CM_Log_Context|null $context
     * @param int|null            $type
     * @return CM_Log_Logger
     */
    public function debug($message, CM_Log_Context $context = null, $type = null) {
        return $this->addMessage($message, self::DEBUG, $context, $type);
    }

    /**
     * @param string              $message
     * @param CM_Log_Context|null $context
     * @param int|null            $type
     * @return CM_Log_Logger
     */
    public function info($message, CM_Log_Context $context = null, $type = null) {
        return $this->addMessage($message, self::INFO, $context, $type);
    }

    /**
     * @param string              $message
     * @param CM_Log_Context|null $context
     * @param int|null            $type
     * @return CM_Log_Logger
     */
    public function warning($message, CM_Log_Context $context = null, $type = null) {
        return $this->addMessage($message, self::WARNING, $context, $type);
    }

    /**
     * @param string              $message
     * @param CM_Log_Context|null $context
     * @param int|null            $type
     * @return CM_Log_Logger
     */
    public function error($message, CM_Log_Context $context = null, $type = null) {
        return $this->addMessage($message, self::ERROR, $context, $type);
    }

    /**
     * @param string              $message
     * @param CM_Log_Context|null $context
     * @param int|null            $type
     * @return CM_Log_Logger
     */
    public function critical($message, CM_Log_Context $context = null, $type = null) {
        return $this->addMessage($message, self::CRITICAL, $context, $type);
    }

    /**
     * @param CM_Log_Context|null $context
     * @return CM_Log_Context
     */
    protected function _mergeWithGlobalContext(CM_Log_Context $context = null) {
        if (null === $context) {
            $context = new CM_Log_Context();
        }
        return $this->_contextGlobal->merge($context);
    }

    /**
     * Gets all supported logging levels.
     *
     * @return array Assoc array with human-readable level names => level codes.
     */
    public static function getLevels() {
        return array_flip(self::$_levels);
    }

    /**
     * Gets the name of the logging level.
     *
     * @param  int $level
     * @return string
     * @throws CM_Exception_Invalid
     */
    public static function getLevelName($level) {
        $level = (int) $level;
        if (!isset(self::$_levels[$level])) {
            throw new CM_Exception_Invalid('Level `' . $level . '` is not defined, use one of: ' . implode(', ', array_keys(self::$_levels)));
        }
        return self::$_levels[$level];
    }

    /**
     * @param int $level
     * @return bool
     */
    public static function hasLevel($level) {
        $level = (int) $level;
        return isset(self::$_levels[$level]);
    }
}

<?php

class CM_Log_Handler_Layered_Layer {

    /** @var CM_Log_Handler_Abstract[] */
    private $_handlers;

    /**
     * @param CM_Log_Handler_Abstract[]|null $handlers
     */
    public function __construct(array $handlers = null) {
        $this->setHandlers((array) $handlers);
    }

    /**
     * @param CM_Log_Handler_Abstract[] $handlers
     */
    public function setHandlers($handlers) {
        $this->_handlers = [];
        \Functional\map($handlers, function (CM_Log_Handler_Abstract $handler) {
            $this->addHandler($handler);
        });
    }

    /**
     * @return CM_Log_Handler_Abstract[]
     */
    public function getHandlers() {
        return $this->_handlers;
    }

    /**
     * @param CM_Log_Handler_Abstract $handler
     */
    public function addHandler(CM_Log_Handler_Abstract $handler) {
        $this->_handlers[] = $handler;
    }

    /**
     * @return int
     */
    public function getHandlersCount() {
        return count($this->_handlers);
    }
}

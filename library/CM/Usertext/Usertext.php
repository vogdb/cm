<?php

class CM_Usertext_Usertext {

	/** @var CM_Render */
	private $_render;

	/**
	 * @param CM_Render $render
	 */
	function __construct(CM_Render $render) {
		$this->_render = $render;
	}

	/** @var CM_Usertext_Filter_Interface[] */
	private $_filterList = array();

	/**
	 * @param CM_Usertext_Filter_Interface $filter
	 */
	public function addFilter(CM_Usertext_Filter_Interface $filter) {
		$this->_filterList[] = $filter;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function transform($text) {
		foreach ($this->_getFilters() as $filter) {
			$text = $filter->transform($text, $this->_render);
		}
		return $text;
	}

	/**
	 * @return CM_Usertext_Filter_Interface[]
	 */
	private function _getFilters() {
		return $this->_filterList;
	}

}

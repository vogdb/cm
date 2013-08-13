<?php

class CM_QueueAdapter_Redis extends CM_QueueAdapter_Abstract {

	private $_redis = null;

	public function push($key, $value) {
		$redis = $this->_getRedisInstance();
		$redis->lPush($this->_getInternalKey($key), $value);
	}

	public function pushDelayed($key, $value, $timestamp) {
		$redis = $this->_getRedisInstance();
		$redis->zAdd($this->_getInternalKey($key), $timestamp, $value);
	}

	public function pop($key) {
		$redis = $this->_getRedisInstance();
		return $redis->rPop($this->_getInternalKey($key));
	}

	public function popDelayed($key, $timestampMax, $count = null) {
		$redis = $this->_getRedisInstance();
		$value = $redis->zPopRangeByScore($this->_getInternalKey($key), 0, $timestampMax);
		return $value;
	}

	private function _getInternalKey($key) {
		return 'Queue.' . (string) $key;
	}

	/**
	 * @return CM_Cache_Redis
	 */
	private function _getRedisInstance() {
		if (null === $this->_redis) {
			$this->_redis = new CM_Cache_Redis();
		}
		return $this->_redis;
	}
}

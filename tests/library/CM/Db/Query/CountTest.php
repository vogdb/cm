<?php

class CM_Db_Query_CountTest extends CMTest_TestCase {

	public function testAll() {
		$client = CMTest_TH::createClient(false);
		$query = new CM_Db_Query_Count($client, 't`est', array('foo' => 'foo1', 'bar' => 'bar1'));
		$this->assertSame('SELECT COUNT(*) FROM `t``est` WHERE `foo` = ? AND `bar` = ?', $query->getSqlTemplate());
		$this->assertEquals(array('foo1', 'bar1'), $query->getParameters());
	}
}

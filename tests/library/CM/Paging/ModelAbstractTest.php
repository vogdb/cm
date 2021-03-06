<?php

class CM_Paging_ModelAbstractTest extends CMTest_TestCase {

    public static function setUpBeforeClass() {
        CM_Db_Db::exec("CREATE TABLE IF NOT EXISTS `cm_paging_modelabstracttest_modelmock` (
				`id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
				`foo` VARCHAR(32)
			) ENGINE=MyISAM AUTO_INCREMENT=" . rand(1, 1000) . " DEFAULT CHARSET=utf8;
		");
        CM_Db_Db::exec("CREATE TABLE IF NOT EXISTS `cm_paging_modelabstracttest_modelmock2` (
				`id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
				`bar` VARCHAR(32)
			) ENGINE=MyISAM AUTO_INCREMENT=" . rand(1, 1000) . " DEFAULT CHARSET=utf8;
		");
    }

    public function testPagingFixedType() {
        CM_Config::get()->CM_Model_Abstract->types[CM_Paging_ModelAbstractTest_ModelMock::getTypeStatic()] = 'CM_Paging_ModelAbstractTest_ModelMock';
        $model1 = CM_Paging_ModelAbstractTest_ModelMock::create('foo1');
        $model2 = CM_Paging_ModelAbstractTest_ModelMock::create('foo2');
        $source = new CM_PagingSource_Array(array($model2->getId(), $model1->getId(), 2000));
        $modelPaging = $this->getMockBuilder('CM_Paging_ModelAbstract')->setMethods(array('_getModelType'))->setConstructorArgs(array($source))
            ->getMockForAbstractClass();
        $modelPaging->expects($this->any())->method('_getModelType')->will($this->returnValue(CM_Paging_ModelAbstractTest_ModelMock::getTypeStatic()));
        /** @var CM_Paging_ModelAbstract $modelPaging */
        $this->assertCount(3, $modelPaging);
        $this->assertEquals($model2, $modelPaging->getItem(0));
        $this->assertEquals($model1, $modelPaging->getItem(1));
        $this->assertNull($modelPaging->getItem(2));
    }

    public function testPagingVariableType() {
        CM_Config::get()->CM_Model_Abstract->types[CM_Paging_ModelAbstractTest_ModelMock::getTypeStatic()] = 'CM_Paging_ModelAbstractTest_ModelMock';
        CM_Config::get()->CM_Model_Abstract->types[CM_Paging_ModelAbstractTest_ModelMock2::getTypeStatic()] = 'CM_Paging_ModelAbstractTest_ModelMock2';
        $model1 = CM_Paging_ModelAbstractTest_ModelMock::create('foo');
        $model2 = CM_Paging_ModelAbstractTest_ModelMock2::create('bar');
        $source = new CM_PagingSource_Array(array(
            array('type' => $model1->getType(), 'id' => $model1->getId()),
            array('type' => $model2->getType(), 'id' => $model2->getId()),
            array('type' => $model1->getType(), 'id' => 9999)
        ));
        $modelPaging = $this->getMockBuilder('CM_Paging_ModelAbstract')->setConstructorArgs(array($source))
            ->getMockForAbstractClass();
        /** @var CM_Paging_ModelAbstract $modelPaging */
        $this->assertCount(3, $modelPaging);
        $this->assertEquals($model1, $modelPaging->getItem(0));
        $this->assertEquals($model2, $modelPaging->getItem(1));
        $this->assertNull($modelPaging->getItem(2));
    }

    public function testModelListInvalidation() {
        CM_Config::get()->CM_Model_Abstract->types[CM_Paging_ModelAbstractTest_ModelMock::getTypeStatic()] = 'CM_Paging_ModelAbstractTest_ModelMock';
        CM_Config::get()->CM_Model_Abstract->types[CM_Paging_ModelAbstractTest_ModelMock2::getTypeStatic()] = 'CM_Paging_ModelAbstractTest_ModelMock2';
        $model1 = CM_Paging_ModelAbstractTest_ModelMock::create('foo');
        $model2 = CM_Paging_ModelAbstractTest_ModelMock2::create('bar');
        $model3 = CM_Paging_ModelAbstractTest_ModelMock::create('baz');
        $model4 = CM_Paging_ModelAbstractTest_ModelMock::create('quux');
        $model5 = CM_Paging_ModelAbstractTest_ModelMock::create('foobar');

        $source = new CM_PagingSource_Array(array(
            array('type' => $model1->getType(), 'id' => $model1->getId()),
            array('type' => $model2->getType(), 'id' => $model2->getId()),
            array('type' => $model3->getType(), 'id' => $model3->getId()),
            array('type' => $model4->getType(), 'id' => $model4->getId()),
            array('type' => $model5->getType(), 'id' => $model5->getId()),
            array('type' => $model1->getType(), 'id' => 9999)
        ));
        $modelPaging = $this->getMockBuilder('CM_Paging_ModelAbstract')->setConstructorArgs(array($source))
            ->getMockForAbstractClass();
        /** @var CM_Paging_ModelAbstract $modelPaging */

        $modelPaging->setPage(1, 2);
        $this->assertEquals($model1, $modelPaging->getItem(0));
        $this->assertEquals($model2, $modelPaging->getItem(1));
        $modelPaging->setPage(2, 2);
        $this->assertEquals($model3, $modelPaging->getItem(0));
        $this->assertEquals($model4, $modelPaging->getItem(1));
    }
}

class CM_Paging_ModelAbstractTest_ModelMock extends CM_Model_Abstract {

    protected function _getSchema() {
        return new CM_Model_Schema_Definition(array('foo' => array()));
    }

    public static function create($foo) {
        $model = new self();
        $model->_set('foo', $foo);
        $model->commit();
        return $model;
    }

    public static function getPersistenceClass() {
        return 'CM_Model_StorageAdapter_Database';
    }

    public static function getTypeStatic() {
        return 1;
    }
}

class CM_Paging_ModelAbstractTest_ModelMock2 extends CM_Model_Abstract {

    protected function _getSchema() {
        return new CM_Model_Schema_Definition(array('bar' => array()));
    }

    public static function create($bar) {
        $model = new self();
        $model->_set('bar', $bar);
        $model->commit();
        return $model;
    }

    public static function getPersistenceClass() {
        return 'CM_Model_StorageAdapter_Database';
    }

    public static function getTypeStatic() {
        return 2;
    }
}

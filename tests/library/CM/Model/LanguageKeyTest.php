<?php

class CM_Model_LanguageKeyTest extends CMTest_TestCase {

    public function tearDown() {
        CMTest_TH::clearDb();
    }

    public function testCreate() {
        $languageKey = CM_Model_LanguageKey::create('foo', ['bar']);
        $this->assertTrue(CM_Model_LanguageKey::exists('foo'));
        $this->assertSame(['bar'], $languageKey->getVariables());
    }

    public function testSetGetVariables() {
        $languageKey = CM_Model_LanguageKey::create('foo');
        $this->assertSame([], $languageKey->getVariables());
        $this->assertSame(0, $this->forceInvokeMethod($languageKey, '_getUpdateCount'));

        $languageKey->setVariables(['foo']);
        $this->assertSame(['foo'], $languageKey->getVariables());
        $this->assertSame(1, $this->forceInvokeMethod($languageKey, '_getUpdateCount'));

        $languageKey->setVariables(['foo']);
        $this->assertSame(['foo'], $languageKey->getVariables());
        $this->assertSame(1, $this->forceInvokeMethod($languageKey, '_getUpdateCount'));

        $languageKey->setVariables(['foo', 'bar']);
        $this->assertSame(['bar', 'foo'], $languageKey->getVariables());
        $this->assertSame(2, $this->forceInvokeMethod($languageKey, '_getUpdateCount'));

        $languageKey->setVariables(['bar', 'foo']);
        $this->assertSame(['bar', 'foo'], $languageKey->getVariables());
        $this->assertSame(2, $this->forceInvokeMethod($languageKey, '_getUpdateCount'));

        $languageKey->setVariables(null);
        $this->assertSame([], $languageKey->getVariables());
        $this->assertSame(3, $this->forceInvokeMethod($languageKey, '_getUpdateCount'));
    }

    public function testExists() {
        $this->assertFalse(CM_Model_LanguageKey::exists('foo'));
        CM_Model_LanguageKey::create('foo');
        $this->assertTrue(CM_Model_LanguageKey::exists('foo'));
    }

    public function testDelete() {
        $language = CM_Model_Language::create('Foo', 'foo', true);
        $language->setTranslation('foo', 'bar');
        $this->assertSame(array('foo' => array('value' => 'bar', 'variables' => array())), $language->getTranslations()->getAssociativeArray());

        $languageKey = CM_Model_LanguageKey::findByName('foo');
        $languageKey->delete();

        $this->assertSame(array(), $language->getTranslations()->getAssociativeArray());
        $this->assertSame(0, CM_Db_Db::count('cm_model_languagekey', array('name' => 'foo')));
        $this->assertSame(0, CM_Db_Db::count('cm_languageValue', array(
            'languageKeyId' => $languageKey->getId(),
            'languageId'    => $language->getId(),
        )));
    }

    public function testSetVariablesWithDifferentVariablesLoop() {
        $languageKey = CM_Model_LanguageKey::create('foo');
        for ($i = 0; $i < 25; $i++) {
            $languageKey->setVariables(array('oneVariable', 'secondOne'));
            $languageKey->setVariables(array('oneVariable'));
        }
        try {
            $languageKey->setVariables(array('oneVariable', 'secondOne'));
            $this->fail('Did not throw exception after ' . ($i * 2) . ' changes');
        } catch (CM_Exception_Invalid $e) {
            $this->assertContains('`foo`', $e->getMessage());
        }
    }

    public function testClearDuplicates() {
        CM_Model_LanguageKey::create('foo');
        CM_Model_LanguageKey::create('foo');
        $this->assertRow('cm_model_languagekey', ['name' => 'foo'], 2);
        CM_Model_LanguageKey::clearDuplicates('foo');
        $this->assertRow('cm_model_languagekey', ['name' => 'foo'], 1);
    }
}

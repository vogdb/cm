<?php

class CM_Config_Cli extends CM_Cli_Runnable_Abstract {

	public function generate() {
		// Create class types and action verbs config PHP
		$fileHeader = '<?php' . PHP_EOL;
		$fileHeader .= '// This is autogenerated action verbs config file. You should not adjust changes manually.' . PHP_EOL;
		$fileHeader .= '// You should adjust TYPE constants and regenerate file using `config generate` command' . PHP_EOL;
		$path = DIR_ROOT . 'resources/config/internal.php';
		$classTypesConfig  = CM_App::getInstance()->generateConfigClassTypes();
		$actionVerbsConfig = CM_App::getInstance()->generateConfigActionVerbs();
		CM_File::create($path, $fileHeader . $classTypesConfig . PHP_EOL . PHP_EOL . $actionVerbsConfig);
		$this->_echo('create  ' . $path);

		// Create model class types and action verbs config JS
		$path = DIR_ROOT . 'resources/config/js/internal.js';
		$modelTypesConfig = 'cm.model.types = ' . CM_Params::encode(CM_App::getInstance()->getClassTypes('CM_Model_Abstract'), true) . ';';
		$actionVerbs = array();
		foreach (CM_App::getInstance()->getActionVerbs() as $verb) {
			$actionVerbs[$verb['name']] = $verb['value'];
		}
		$actionVerbsConfig = 'cm.action.verbs = ' . CM_Params::encode($actionVerbs, true) . ';';
		CM_File::create($path, $modelTypesConfig . PHP_EOL . $actionVerbsConfig);
		$this->_echo('create  ' . $path);
	}

	public static function getPackageName() {
		return 'config';
	}

}
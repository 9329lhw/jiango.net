<?php
namespace library\helper;

class HelperBasic {
	protected $config;

	public function getCurrentHelperName() {
		$helperFullNameArray = explode("\\", get_class($this));
		$helperFullName = end($helperFullNameArray);
		return strstr($helperFullName, 'Helper', true);
	}

	protected function setConfig($appendedConfig = array()) {
		$this->config = include __DIR__ . "/../Config/" . $this->getCurrentHelperName() . ".php";
		$this->config = array_merge($this->config, $appendedConfig);
	}
}
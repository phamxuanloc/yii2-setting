<?php
namespace phamxuanloc\setting;

use Yii;

class Module extends \phamxuanloc\base\Module {

	public    $controllerNamespace = 'phamxuanloc\setting\controllers';

	protected $_isBackend;

	public function init() {
		parent::init();
		$this->setViewPath('@phamxuanloc/setting/views');
	}

	/**
	 * Check if module is used for backend application.
	 *
	 * @return boolean true if it's used for backend application
	 */
	public function isBackend() {
		if ($this->_isBackend === null) {
			$this->_isBackend = strpos(Yii::$app->controllerNamespace, 'backend') === false ? false : true;
		}
		return $this->_isBackend;
	}
}

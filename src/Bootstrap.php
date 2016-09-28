<?php
/**
 * Created by Navatech.
 * @project yii2-setting
 * @author  Phuong
 * @email   phuong17889[at]gmail.com
 * @date    05/07/2016
 * @time    11:50 PM
 */
namespace phamxuanloc\setting;

use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\i18n\PhpMessageSource;

class Bootstrap implements BootstrapInterface {

	/**
	 * Bootstrap method to be called during application bootstrap stage.
	 *
	 * @param Application $app the application currently running
	 */
	public function bootstrap($app) {
		if (!isset($app->get('i18n')->translations['setting*'])) {
			$app->get('i18n')->translations['setting*'] = [
				'class'          => PhpMessageSource::className(),
				'basePath'       => __DIR__ . '/messages',
				'sourceLanguage' => 'en-US',
			];
		}
		$configUrlRule          = [
			'prefix'      => 'setting',
			'routePrefix' => 'setting',
			'rules'       => [
				'<action:\w+>' => 'default/<action>',
			],
		];
		$configUrlRule['class'] = 'yii\web\GroupUrlRule';
		$rule                   = Yii::createObject($configUrlRule);
		$app->urlManager->addRules([$rule], false);
	}
}
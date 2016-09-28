<?php
namespace phamxuanloc\setting\controllers;

use phamxuanloc\language\Translate;
use phamxuanloc\role\filters\RoleFilter;
use phamxuanloc\setting\actions\DefaultAction;
use phamxuanloc\setting\models\Setting;
use phamxuanloc\setting\Module;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * {@inheritDoc}
 */
class DefaultController extends Controller {

	/**
	 * {@inheritDoc}
	 */
	public function behaviors() {
		$behaviors = [
			'verbs'  => [
				'class'   => VerbFilter::className(),
				'actions' => [
					'delete' => ['post'],
				],
			],
			'access' => [
				'class' => AccessControl::className(),
				'rules' => [
					[
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
		];
		if (Module::hasUserRole()) {
			if (Module::hasMultiLanguage()) {
				return ArrayHelper::merge($behaviors, [
					'role' => [
						'class'   => RoleFilter::className(),
						'name'    => Translate::setting(),
						'actions' => [
							'index' => Translate::index(),
						],
					],
				]);
			} else {
				return ArrayHelper::merge($behaviors, [
					'role' => [
						'class'   => RoleFilter::className(),
						'name'    => 'Setting',
						'actions' => [
							'index' => Yii::t('setting', 'List'),
						],
					],
				]);
			}
		} else {
			return $behaviors;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function actions() {
		$actions = [];
		/**@var Setting[] $settings */
		$settings = Setting::find()->where(['type' => Setting::TYPE_ACTION])->all();
		foreach ($settings as $setting) {
			$actions[$setting->code] = [
				'class' => DefaultAction::className(),
			];
		}
		return $actions;
	}

	/**
	 * @return string|Response
	 */
	public function actionIndex() {
		if (Yii::$app->request->isPost) {
			$setting = Yii::$app->request->post('Setting');
			if (isset($_FILES['Setting'])) {
				foreach ($_FILES['Setting']['name'] as $key => $value) {
					$model       = Setting::findOne(['code' => $key]);
					$model->file = UploadedFile::getInstance($model, $key);
					if ($model->file != null && $model->upload()) {
						$model->updateAttributes(['value' => $value]);
					}
				}
			}
			if ($setting != null) {
				foreach ($setting as $key => $value) {
					if ($value !== '' || $value != null) {
						if (is_array($value)) {
							Setting::updateAll(['value' => implode(",", $value)], ['code' => $key]);
						} else {
							Setting::updateAll(['value' => $value], ['code' => $key]);
						}
					}
				}
			}
			Yii::$app->session->setFlash('alert', [
				'body'    => Yii::t('setting', 'Settings has been successfully saved'),
				'options' => ['class' => 'alert-success'],
			]);
		}
		if (Module::hasMultiLanguage()) {
			$title = Translate::setting();
		} else {
			$title = Yii::t('setting', 'Setting');
		}
		return $this->render('index', [
			'title' => $title,
			'code'  => null,
		]);
	}
}

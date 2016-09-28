<?php
namespace phamxuanloc\setting\controllers;

use phamxuanloc\base\Module;
use phamxuanloc\language\Translate;
use phamxuanloc\role\filters\RoleFilter;
use phamxuanloc\setting\models\Setting;
use phamxuanloc\setting\models\SettingSearch;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * ConfigController implements the CRUD actions for Setting model.
 */
class ConfigController extends Controller {

	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		$behaviors = [
			'verbs'  => [
				'class'   => VerbFilter::className(),
				'actions' => [
					'delete' => ['POST'],
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
							'index'  => Translate::index(),
							'create' => Translate::create(),
							'update' => Translate::update(),
							'delete' => Translate::delete(),
						],
					],
				]);
			} else {
				return ArrayHelper::merge($behaviors, [
					'role' => [
						'class'   => RoleFilter::className(),
						'name'    => 'Setting',
						'actions' => [
							'index'  => Yii::t('setting', 'List'),
							'create' => Yii::t('setting', 'Create'),
							'update' => Yii::t('setting', 'Update'),
							'delete' => Yii::t('setting', 'Delete'),
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
	public function beforeAction($action) {
		if (YII_ENV_DEV) {
			return parent::beforeAction($action);
		} else {
			throw new NotFoundHttpException('The requested page does not exist.');
		}
	}

	/**
	 * Lists all Setting models.
	 * @return mixed
	 */
	public function actionIndex() {
		if (Yii::$app->request->post('hasEditable')) {
			$id              = Yii::$app->request->post('editableKey');
			$attribute       = Yii::$app->request->post('editableAttribute');
			$model           = Setting::findOne($id);
			$out             = [
				'output'  => '',
				'message' => '',
			];
			$post            = [];
			$posted          = current($_POST['Setting']);
			$post['Setting'] = $posted;
			if ($model->updateAttributes([$attribute => $posted[$attribute]])) {
				$out = [
					'output'  => $posted[$attribute],
					'message' => '',
				];
			}
			Yii::$app->response->format = 'json';
			return $out;
		}
		$searchModel  = new SettingSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams);
		return $this->render('index', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}

	/**
	 * Creates a new Setting model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * @return mixed
	 */
	public function actionCreate() {
		$model = new Setting();
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->redirect([
				'index',
			]);
		} else {
			return $this->render('create', [
				'model' => $model,
			]);
		}
	}

	/**
	 * Updates an existing Setting model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 *
	 * @param integer $id
	 *
	 * @return mixed
	 */
	public function actionUpdate($id) {
		$model = $this->findModel($id);
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->redirect([
				'index',
			]);
		} else {
			return $this->render('update', [
				'model' => $model,
			]);
		}
	}

	/**
	 * Finds the Setting model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id
	 *
	 * @return Setting the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id) {
		if (($model = Setting::findOne($id)) !== null) {
			return $model;
		} else {
			throw new NotFoundHttpException('The requested page does not exist.');
		}
	}

	/**
	 * Deletes an existing Setting model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 *
	 * @param integer $id
	 *
	 * @return mixed
	 */
	public function actionDelete($id) {
		$model = $this->findModel($id);
		if ($model->parent_id == 0 && $model->type == Setting::TYPE_GROUP) {
			$model->deleteAll(['parent_id' => $model->id]);
		}
		$model->delete();
		return $this->redirect(['index']);
	}
}

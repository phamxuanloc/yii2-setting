<?php
namespace phamxuanloc\setting\models;

use kartik\password\PasswordInput;
use kartik\widgets\ColorInput;
use kartik\widgets\DatePicker;
use kartik\widgets\DateTimePicker;
use kartik\widgets\FileInput;
use kartik\widgets\RangeInput;
use kartik\widgets\Select2;
use kartik\widgets\SwitchInput;
use kartik\widgets\TimePicker;
use phamxuanloc\language\Translate;
use phamxuanloc\roxymce\widgets\RoxyMceWidget;
use phamxuanloc\setting\Module;
use Yii;
use yii\base\ErrorException;
use yii\bootstrap\Html;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * This is the model class for table "setting".
 *
 * @property integer $id
 * @property integer $parent_id
 * @property string  $name
 * @property string  $desc
 * @property string  $code
 * @property string  $type
 * @property string  $store_range
 * @property string  $store_dir
 * @property string  $store_url
 * @property string  $value
 * @property integer $sort_order
 */
class Setting extends ActiveRecord {

	const TYPE_ACTION       = 'action';

	const TYPE_GROUP        = 'group';

	const TYPE_TEXT         = 'text';

	const TYPE_EMAIL        = 'email';

	const TYPE_NUMBER       = 'number';

	const TYPE_TEXTAREA     = 'textarea';

	const TYPE_COLOR        = 'color';

	const TYPE_DATE         = 'date';

	const TYPE_TIME         = 'time';

	const TYPE_DATETIME     = 'datetime';

	const TYPE_PASSWORD     = 'password';

	const TYPE_ROXYMCE      = 'roxymce';

	const TYPE_SELECT       = 'select';

	const TYPE_MULTI_SELECT = 'multi_select';

	const TYPE_FILE_PATH    = 'file_path';

	const TYPE_FILE_URL     = 'file_url';

	const TYPE_PERCENT      = 'percent';

	const TYPE_SWITCH       = 'switch';

	const TYPE_CHECKBOX     = 'checkbox';

	const TYPE_RADIO        = 'radio';

	const TYPE_SEPARATOR    = 'separator';

	const TYPE              = [
		'action'       => 'Action',
		'group'        => 'Group',
		'text'         => 'Text field',
		'email'        => 'Email field',
		'number'       => 'Number field',
		'textarea'     => 'Textarea field',
		'color'        => 'Color picker',
		'date'         => 'Date picker',
		'time'         => 'Time picker',
		'datetime'     => 'Datetime picker',
		'password'     => 'Password field',
		'roxymce'      => 'Roxymce widget',
		'select'       => 'Single dropdown',
		'multi_select' => 'Multi selectable',
		'file_path'    => 'File Path field',
		'file_url'     => 'File Url field',
		'percent'      => 'Percent picker',
		'switch'       => 'Switch',
		'checkbox'     => 'Checkbox',
		'radio'        => 'Radio',
		'separator'    => 'Separator',
	];

	/**
	 * @var UploadedFile
	 */
	public $file;

	/**
	 * @inheritdoc
	 */
	public static function tableName() {
		return '{{%setting}}';
	}

	/**
	 * @param null $code
	 *
	 * @return array
	 */
	public static function getItems($code = null) {
		/**@var $parentSettings self[] */
		$items = [];
		if ($code == null) {
			$parentSettings = Setting::find()->where([
				'parent_id' => 0,
				'type'      => self::TYPE_GROUP,
			])->orderBy(['sort_order' => SORT_ASC])->all();
		} else {
			$currentSetting = self::findOne(['code' => $code]);
			if ($currentSetting !== null) {
				$parentSettings = Setting::find()->where([
					'parent_id' => $currentSetting->id,
					'type'      => self::TYPE_GROUP,
				])->orderBy(['sort_order' => SORT_ASC])->all();
			} else {
				$parentSettings = Setting::find()->where([
					'parent_id' => 0,
					'type'      => self::TYPE_GROUP,
				])->orderBy(['sort_order' => SORT_ASC])->all();
			}
		}
		foreach ($parentSettings as $parentSetting) {
			$content = Html::beginForm('', 'POST', [
				'class'   => 'form-horizontal',
				'id'      => 'nava-setting-form',
				'enctype' => 'multipart/form-data',
			]);
			$content .= $parentSetting->getContent();
			$content .= Html::beginTag('div', ['class' => 'form-group']);
			$content .= Html::beginTag('div', ['class' => 'col-sm-9 col-sm-offset-3']);
			$content .= Html::submitButton('Save', ['class' => 'btn btn-success']);
			$content .= Html::endTag('div');
			$content .= Html::endTag('div');
			$content .= Html::endForm();
			$items[] = [
				'label'   => $parentSetting->getName(),
				'content' => $content,
			];
		}
		return $items;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		/**@var $settings self[] */
		$html     = '';
		$settings = $this->find()->where(['parent_id' => $this->id])->orderBy(['sort_order' => SORT_ASC])->all();
		foreach ($settings as $setting) {
			$html .= Html::beginTag('div', [
				'class' => 'form-group',
				'id'    => $setting->code,
			]);
			if ($setting->type === self::TYPE_SEPARATOR) {
				$html .= '<div class="col-sm-12"><hr></div>';
			} else {
				$html .= Html::label($setting->getName(), $setting->code, ['class' => 'col-sm-3 control-label no-padding-right']);
				$html .= Html::beginTag('div', ['class' => 'col-sm-6']);
				$html .= $setting->getItem();
				$html .= Html::beginTag('span', ['class' => 'help-block']);
				if (YII_DEBUG && YII_ENV == 'dev') {
					$html .= '<strong>Yii::$app->setting->' . $setting->code . '</strong><br/>';
				}
				$html .= $setting->getDesc();
				$html .= Html::endTag('span');
				$html .= Html::endTag('div');
			}
			$html .= Html::endTag('div');
		}
		return $html;
	}

	/**
	 * @return string
	 * @throws ErrorException
	 */
	public function getName() {
		if (Module::hasMultiLanguage()) {
			$code = $this->code;
			return Translate::$code();
		} else {
			try {
				return $this->name;
			} catch (ErrorException $e) {
				throw new ErrorException(Yii::t('setting', 'You should run migrations by command {0}', ['"php yii migrate --migrationPath=@phamxuanloc/setting/migrations"']));
			}
		}
	}

	/**
	 * @param null $options
	 * @param null $pluginOptions
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getItem($options = null, $pluginOptions = null) {
		switch ($this->type) {
			case self::TYPE_TEXT:
				return Html::input('text', 'Setting[' . $this->code . ']', $this->value, $options != null ? $options : [
					'placeholder' => $this->getName(),
					'class'       => 'form-control',
				]);
			case self::TYPE_EMAIL:
				return Html::input('email', 'Setting[' . $this->code . ']', $this->value, $options != null ? $options : [
					'placeholder' => $this->getName(),
					'class'       => 'form-control',
				]);
			case self::TYPE_NUMBER:
				return Html::input('number', 'Setting[' . $this->code . ']', $this->value, $options != null ? $options : [
					'placeholder' => $this->getName(),
					'class'       => 'form-control',
				]);
			case self::TYPE_TEXTAREA:
				return Html::textarea('Setting[' . $this->code . ']', $this->value, $options != null ? $options : [
					'placeholder' => $this->getName(),
					'class'       => 'form-control',
				]);
			case self::TYPE_COLOR:
				return ColorInput::widget([
					'value'   => $this->value,
					'name'    => 'Setting[' . $this->code . ']',
					'options' => $options != null ? $options : [
						'class' => 'form-control',
					],
				]);
			case self::TYPE_DATE:
				return DatePicker::widget([
					'name'          => 'Setting[' . $this->code . ']',
					'value'         => $this->value,
					'options'       => $options != null ? $options : [
						'class' => 'form-control',
					],
					'pluginOptions' => $pluginOptions != null ? $pluginOptions : [
						'format'         => 'yyyy-mm-dd',
						'todayHighlight' => true,
					],
				]);
			case self::TYPE_TIME:
				return TimePicker::widget([
					'name'          => 'Setting[' . $this->code . ']',
					'value'         => $this->value,
					'options'       => $options != null ? $options : [
						'class' => 'form-control',
					],
					'pluginOptions' => $pluginOptions != null ? $pluginOptions : [
						'minuteStep'   => 1,
						'showSeconds'  => true,
						'showMeridian' => false,
					],
				]);
			case self::TYPE_DATETIME:
				return DateTimePicker::widget([
					'name'          => 'Setting[' . $this->code . ']',
					'value'         => $this->value,
					'options'       => $options != null ? $options : [
						'class' => 'form-control',
					],
					'pluginOptions' => [
						'format'         => 'yyyy-mm-dd H:i:s',
						'todayHighlight' => true,
					],
				]);
			case self::TYPE_PASSWORD:
				return PasswordInput::widget([
					'name'          => 'Setting[' . $this->code . ']',
					'value'         => $this->value,
					'options'       => $options != null ? $options : [
						'class' => 'form-control',
					],
					'pluginOptions' => $pluginOptions != null ? $pluginOptions : [
						'showMeter'  => true,
						'toggleMask' => false,
					],
				]);
			case self::TYPE_ROXYMCE:
				return RoxyMceWidget::widget([
					'id'          => 'Setting_' . $this->code,
					'name'        => 'Setting[' . $this->code . ']',
					'value'       => $this->value,
					'action'      => Url::to(['roxymce/default']),
					'options'     => $options != null ? $options : [
						'title' => $this->getName(),
					],
					'htmlOptions' => $pluginOptions != null ? $pluginOptions : [],
				]);
			case self::TYPE_SELECT:
				return Select2::widget([
					'value'         => $this->value,
					'name'          => 'Setting[' . $this->code . ']',
					'data'          => $this->getStoreRange(),
					'options'       => $options != null ? $options : [
						'class' => 'form-control',
					],
					'pluginOptions' => [
						'allowClear' => true,
					],
				]);
			case self::TYPE_MULTI_SELECT:
				$options['multiple'] = true;
				if (!isset($options['class'])) {
					$options['class'] = 'form-control';
				}
				return Select2::widget([
					'name'          => 'Setting[' . $this->code . ']',
					'value'         => explode(",", $this->value),
					'data'          => $this->getStoreRange(),
					'options'       => $options,
					'pluginOptions' => [
						'allowClear' => true,
					],
				]);
			case self::TYPE_FILE_PATH:
				$value = Yii::getAlias($this->store_dir) . DIRECTORY_SEPARATOR . $this->value;
				return FileInput::widget([
					'name'          => 'Setting[' . $this->code . ']',
					'value'         => $value,
					'options'       => $options != null ? $options : [
						'class'    => 'form-control',
						'multiple' => false,
					],
					'pluginOptions' => $pluginOptions != null ? $pluginOptions : [
						'previewFileType' => 'any',
						'showRemove'      => false,
						'showUpload'      => false,
						'initialPreview'  => !$this->isNewRecord ? [
							$this->value,
						] : [],
					],
				]);
			case self::TYPE_FILE_URL:
				$value = Url::to([$this->store_url . '/' . $this->value], true);
				try {
					$is_image = getimagesize($value) ? true : false;
				} catch (ErrorException $e) {
					$is_image = false;
				}
				return FileInput::widget([
					'name'          => 'Setting[' . $this->code . ']',
					'value'         => $value,
					'options'       => $options != null ? $options : [
						'class' => 'form-control',
					],
					'pluginOptions' => $pluginOptions != null ? $pluginOptions : [
						'previewFileType' => 'any',
						'showRemove'      => false,
						'showUpload'      => false,
						'initialPreview'  => !$this->isNewRecord ? [
							$is_image ? Html::img($value) : $this->value,
						] : [],
					],
				]);
			case self::TYPE_PERCENT:
				return RangeInput::widget([
					'name'         => 'Setting[' . $this->code . ']',
					'value'        => $this->value,
					'html5Options' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
					'options'      => $options != null ? $options : [
						'class' => 'form-control',
					],
					'addon'        => ['append' => ['content' => '%']],
				]);
			case self::TYPE_SWITCH:
				$selector = explode(',', $this->store_range);
				if (count($selector) != 2) {
					throw new ErrorException(Yii::t('setting', 'Switch Field should have store with 2 value, and negative is first. Example: no,yes'), 500);
				}
				return Html::hiddenInput('Setting[' . $this->code . ']', $selector[0]) . SwitchInput::widget([
					'name'             => 'Setting[' . $this->code . ']',
					'value'            => $selector[1],
					'containerOptions' => [
						'class' => 'nv-switch-container',
					],
					'options'          => $options != null ? $options : [],
					'pluginOptions'    => $pluginOptions != null ? $pluginOptions : [
						'state'   => $this->value == $selector[1],
						'size'    => 'small',
						'offText' => ucfirst($selector[0]),
						'onText'  => ucfirst($selector[1]),
					],
				]);
			case self::TYPE_CHECKBOX:
				$random = rand(1000, 9999);
				return Html::checkboxList('Setting[' . $this->code . ']', explode(",", $this->value), $this->getStoreRange(), $options != null ? $options : [
					'class' => 'nv-checkbox-list checkbox',
					'item'  => function ($index, $label, $name, $checked, $value) use ($random) {
						$html = Html::beginTag('div');
						$html .= Html::checkbox($name, $checked, [
							'id'    => 'Setting_checkbox_' . $label . '_' . $index . '_' . $random,
							'value' => $value,
						]);
						$html .= Html::label($label, 'Setting_checkbox_' . $label . '_' . $index . '_' . $random);
						$html .= Html::endTag('div');
						return $html;
					},
				]);
			case self::TYPE_RADIO:
				$random = rand(1000, 9999);
				return Html::radioList('Setting[' . $this->code . ']', $this->value, $this->getStoreRange(), $options != null ? $options : [
					'class' => 'nv-checkbox-list radio',
					'item'  => function ($index, $label, $name, $checked, $value) use ($random) {
						$html = Html::beginTag('div');
						$html .= Html::radio($name, $checked, [
							'id'    => 'Setting_radio_' . $label . '_' . $index . '_' . $random,
							'value' => $value,
						]);
						$html .= Html::label($label, 'Setting_radio_' . $label . '_' . $index . '_' . $random);
						$html .= Html::endTag('div');
						return $html;
					},
				]);
			case self::TYPE_SEPARATOR:
				return '<hr>';
			default:
				return Html::input('text', 'Setting[' . $this->code . ']', $this->value, $options != null ? $options : [
					'placeholder' => $this->getName(),
					'class'       => 'form-control',
				]);
		}
	}

	/**
	 * @return array
	 * @throws NotFoundHttpException
	 */
	public function getStoreRange() {
		$response = [];
		if ($this->store_range != '' || $this->store_range != null) {
			$methodVariable = explode('::', $this->store_range);
			if (count($methodVariable) == 2 && isset($methodVariable[1]) && strpos($methodVariable[1], '()') !== false) {
				$methodVariable[1] = str_replace('()', '', $methodVariable[1]);
				if (is_callable($methodVariable)) {
					$response = call_user_func($methodVariable);
				} else {
					throw new NotFoundHttpException(Yii::t('setting', 'Callback function was not found in {0}', [$methodVariable[0]]));
				}
				if (!is_array($response)) {
					throw new NotFoundHttpException(Yii::t('setting', 'Callback function {0} does not returns an array', [$this->store_range]));
				}
			} else {
				if (self::isJson($this->store_range)) {
					$response = Json::decode($this->store_range);
				} else {
					foreach (explode(",", $this->store_range) as $store_range) {
						$response[$store_range] = trim($store_range);
					}
				}
			}
		}
		return $response;
	}

	/**
	 * Will check if is valid json
	 *
	 * @param $string string
	 *
	 * @return bool
	 */
	public static function isJson($string) {
		if (is_array($string) || empty($string)) {
			return false;
		}
		json_decode($string);
		return json_last_error() == JSON_ERROR_NONE;
	}

	/**
	 * @return string
	 */
	public function getDesc() {
		if (Module::hasMultiLanguage()) {
			$code = 'desc_' . $this->code;
			return Translate::$code();
		} else {
			return $this->desc;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[
				[
					'parent_id',
					'sort_order',
				],
				'integer',
			],
			[
				[
					'code',
					'name',
					'type',
				],
				'required',
			],
			[
				[
					'value',
					'type',
				],
				'string',
			],
			[
				['file'],
				'file',
				'skipOnEmpty' => true,
			],
			[
				[
					'store_range',
					'store_dir',
					'store_url',
					'code',
					'name',
					'desc',
				],
				'string',
				'max' => 255,
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels() {
		return [
			'id'          => 'ID',
			'parent_id'   => Yii::t('setting', 'Parent tab'),
			'name'        => Yii::t('setting', 'Name'),
			'code'        => Yii::t('setting', 'Code'),
			'desc'        => Yii::t('setting', 'Description'),
			'type'        => Yii::t('setting', 'Type'),
			'store_range' => Yii::t('setting', 'Store range'),
			'store_dir'   => Yii::t('setting', 'Store dir'),
			'store_url'   => Yii::t('setting', 'Store URL'),
			'value'       => Yii::t('setting', 'Value'),
			'sort_order'  => Yii::t('setting', 'Sort order'),
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function beforeSave($insert) {
		if ($this->parent_id == null) {
			$this->parent_id = 0;
		}
		return parent::beforeSave($insert);
	}

	/**
	 * @return bool
	 */
	public function upload() {
		if ($this->validate()) {
			if (in_array($this->type, [
				self::TYPE_FILE_PATH,
				self::TYPE_FILE_URL,
			])) {
				if ($this->store_dir == '') {
					if (Module::isAdvanced()) {
						/**@var $module Module */
						$module = Yii::$app->getModule('setting');
						if ($module->isBackend()) {
							$this->store_dir = '@backend/web/uploads/setting';
						} else {
							$this->store_dir = '@frontend/web/uploads/setting';
						}
					} else {
						$this->store_dir = '@app/web/uploads/setting';
					}
				}
				if (!file_exists(Yii::getAlias($this->store_dir))) {
					mkdir(Yii::getAlias($this->store_dir), 0777, true);
				}
				$this->updateAttributes(['store_dir' => $this->store_dir]);
			}
			$this->file->saveAs(Yii::getAlias($this->store_dir) . '/' . $this->file->baseName . '.' . $this->file->extension);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return array
	 */
	public static function parentDependent() {
		$response[0] = Yii::t('setting', 'No parent');
		/**@var Setting[] $actionSettings */
		$actionSettings = self::find()
			->where(['type' => self::TYPE_ACTION])
			->orderBy(['sort_order' => SORT_ASC])
			->all();
		if ($actionSettings !== null) {
			foreach ($actionSettings as $actionSetting) {
				$response[$actionSetting->id] = 'Action ' . $actionSetting->name;
				/**@var Setting[] $groupSettings */
				$groupSettings = self::find()->where([
					'type'      => self::TYPE_GROUP,
					'parent_id' => $actionSetting->id,
				])->orderBy(['sort_order' => SORT_ASC])->all();
				foreach ($groupSettings as $groupSetting) {
					$response[$groupSetting->id] = '— Group ' . $groupSetting->name;
				}
			}
		}
		/**@var Setting[] $groupSettings */
		$groupSettings = self::find()->where([
			'type'      => self::TYPE_GROUP,
			'parent_id' => 0,
		])->orderBy(['sort_order' => SORT_ASC])->all();
		foreach ($groupSettings as $groupSetting) {
			$response[$groupSetting->id] = 'Group ' . $groupSetting->name;
		}
		return $response;
	}
}

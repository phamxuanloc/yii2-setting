<?php

namespace phamxuanloc\setting\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use phamxuanloc\setting\models\Setting;

/**
 * SettingSearch represents the model behind the search form about `\phamxuanloc\setting\models\Setting`.
 */
class SettingSearch extends Setting
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'parent_id', 'type', 'sort_order'], 'integer'],
            [['code', 'name', 'desc', 'store_range', 'store_dir', 'value'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Setting::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'type' => $this->type,
            'sort_order' => $this->sort_order,
        ]);

        $query->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'desc', $this->desc])
            ->andFilterWhere(['like', 'store_range', $this->store_range])
            ->andFilterWhere(['like', 'store_dir', $this->store_dir])
            ->andFilterWhere(['like', 'value', $this->value]);

        return $dataProvider;
    }
}

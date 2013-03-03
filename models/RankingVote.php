<?php

/**
 * This is the model class for table "ranking_votes".
 *
 * The followings are the available columns in table 'ranking_votes':
 * @property string $id
 * @property string $user_id
 * @property string $score_ranked
 * @property string $ranking_id
 * @property string $created_on
 * @property string $updated_on
 * @property integer $lock_version
 *
 * The followings are the available model relations:
 * @property User $user
 * @property Ranking $ranking
 */
class RankingVote extends PcBaseArModel {
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return RankingVote the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'ranking_votes';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('score_ranked, ranking_id', 'required'),
			array('user_id', 'length', 'max' => 10),
			array('score_ranked, ranking_id', 'length', 'max' => 11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, score_ranked, ranking_id', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'user' => array(self::BELONGS_TO, 'User', 'user_id'),
			'ranking' => array(self::BELONGS_TO, 'Ranking', 'ranking_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'user_id' => 'User',
			'score_ranked' => 'Score Ranked',
			'ranking_id' => 'Ranking',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search() {
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id, true);
		$criteria->compare('user_id', $this->user_id, true);
		$criteria->compare('score_ranked', $this->score_ranked, true);
		$criteria->compare('ranking_id', $this->ranking_id, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
	/**
	 * @return string
	 */
	public static function getCreatorRelationName() {
		return 'user';
}
	/**
	 * @static
	 * @param int $id primary key of the model
	 * @return int - the model creator user id
	 */
	public static function getCreatorUserId($id) {
		/* @var Article $model */
		$model = self::model()->cache(3600)->with('user_id')->findByPk((int)$id);
		return $model->user_id;
	}
}

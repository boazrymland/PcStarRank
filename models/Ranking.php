<?php

/**
 * This is the model class for table "rankings".
 *
 * The followings are the available columns in table 'rankings':
 * @property string $id
 * @property string $model_name
 * @property string $model_id
 * @property integer $status
 * @property integer $average_score
 * @property string $created_on
 * @property string $updated_on
 * @property integer $lock_version
 *
 * The followings are the available model relations:
 * @property RankingVote[] $rankingVotes
 */
class Ranking extends PcBaseArModel {
	const STATUS_ENABLED = 1;
	const STATUS_DISABLED = 0;

	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Ranking the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'rankings';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('model_name, model_id', 'required'),
			array('status, average_score', 'numerical', 'integerOnly' => true),
			array('model_name', 'length', 'max' => 128),
			array('model_id', 'length', 'max' => 11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, model_name, model_id, status, average_score', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'rankingVotes' => array(self::HAS_MANY, 'RankingVote', 'ranking_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'id' => 'ID',
			'model_name' => 'Model Name',
			'model_id' => 'Model',
			'status' => 'Status',
			'average_score' => 'Average Score',
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
		$criteria->compare('model_name', $this->model_name, true);
		$criteria->compare('model_id', $this->model_id, true);
		$criteria->compare('status', $this->status);
		$criteria->compare('average_score', $this->average_score);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * @return bool telling if 'this' ranking object is in disabled status or not.
	 */
	public function isDisabled() {
		if ($this->status == self::STATUS_DISABLED) {
			return true;
		}
		return false;
	}

	/**
	 * @return string
	 */
	public static function getCreatorRelationName() {
		return '';
	}

	/**
	 * @static
	 * @param int $id primary key of the model
	 * @return int - the model creator user id
	 * @throws CException
	 */
	public static function getCreatorUserId($id) {
		throw new CException("This method should never be called for model of type " . __CLASS__ . " since this class is not related to a user.");
	}
}
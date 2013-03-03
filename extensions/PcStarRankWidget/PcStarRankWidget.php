<?php
/**
 * PcStarRankWidget.php
 *
 */
class PcStarRankWidget extends CWidget {
	const STAR_RANK_AJAX_NOTIFIER = 'StarRankAjaxSubmission';
	const DEFAULT_MIN_RANK = 1;
	const DEFAULT_MAX_RANK = 10;

	/* @var int the model ID this widget is rendered for */
	public $modelId;

	/* @var string the class name of the model this widget 'refers' to - that the voting occurs on/for */
	public $modelClassName;

	/* @var bool */
	private $_isInitialized;

	/* @var Ranking */
	private $_ranking;

	/* @var int */
	private $_minScore;

	/* @var int */
	private $_maxScore;

	/* @var int existing average rank for the specific model, as loaded from its 'Ranking' db record */
	private $_existing_rank;

	/* @var bool whether the str widget for the specific model record should be enabled or disabled. determined by Ranking
	 * record for the specific model */
	private $_readOnly;

	/* @var array */
	private $_starsTitles;

	/* @var int number of votes thus far */
	private $_numOfVotes;

	/**
	 * init method.
	 *
	 * @throws CException
	 */
	public function init() {
		// check that model Id was given
		if (!isset($this->modelId)) {
			Yii::log("Cannot load " . __CLASS__ . " if I don't know what the model Id to refer to. When calling this widget always pass 'modelId' => 123 in its configuration array",
				CLogger::LEVEL_ERROR,
				__METHOD__);
			return;
		}
		// check that model Id was given
		if (!isset($this->modelClassName)) {
			Yii::log("Cannot load " . __CLASS__ . " if I don't know what the model class name is. When calling this widget always pass 'modelClassName' => 'YourClass' in its configuration array",
				CLogger::LEVEL_ERROR,
				__METHOD__);
			return;
		}

		// sanity checks on the given class name and id:
		if (!class_exists($this->modelClassName)) {
			Yii::log("ERROR: was requested to handle star ranking of class name {$this->modelClassName} but class does not exists! Possibly some hacking attempt?",
				CLogger::LEVEL_WARNING,
					"SECURITY " . __METHOD__);
			return;
		}
		if ((is_string($this->modelId) && (!ctype_digit($this->modelId))) && (!is_int($this->modelId))) {
			Yii::log("ERROR: was requested to handle star ranking of class name {$this->modelClassName} with id={$this->modelId} but this id is determined to be fishy! Possibly some hacking attempt?",
				CLogger::LEVEL_WARNING,
					"SECURITY " . __METHOD__);
			return;
		}

		// see if we have a ranking object for this model+id
		$this->_ranking = Ranking::model()->findByAttributes(array('model_id' => $this->modelId, 'model_name' => $this->modelClassName));
		// take existing average rank for this object and also count number of votes for this object, if exists
		if (isset($this->_ranking)) {
			$this->_existing_rank = $this->_ranking->average_score;
			$this->_readOnly = ($this->_ranking->status == Ranking::STATUS_DISABLED) ? true : false;
			$this->_numOfVotes = (int)RankingVote::model()->countByAttributes(array('ranking_id' => $this->_ranking->id));
		}
		else {
			$this->_readOnly = false;
			$this->_numOfVotes = 0;
			$this->_existing_rank = 0;
		}

		/* check if the user has voted already only if the 'readonly' attribute is in FALSE... .
					 *
					 * if the user has already voted for this model record, make this readonly nevertheless. when checking if the
					 *  user voted remember that in order to have a "ranking_votes" record a "ranking" record must exists prior to
					 * it due to PK - FK constraint in the DB level.
					 */
		if (($this->_readOnly === false) && (isset($this->_ranking))) {
			$have_user_voted = (bool)RankingVote::model()->countByAttributes(array('ranking_id' => $this->_ranking->id, 'user_id' => Yii::app()->user->id));
			if ($have_user_voted) {
				$this->_readOnly = true;
			}
		}


		/* the star rank widget supports displaying custom number of stars and custom hover-over title text on the buttons. */
		$reflected = new ReflectionClass($this->modelClassName);
		//$reflected = new Article;
		if ($reflected->hasConstant('STARRANK_MIN_RANK')) {
			$this->_minScore = $reflected->getConstant('STARRANK_MIN_RANK');
		}
		else {
			$this->_minScore = self::DEFAULT_MIN_RANK;
		}
		if ($reflected->hasConstant('STARRANK_MAX_RANK')) {
			$this->_maxScore = $reflected->getConstant('STARRANK_MAX_RANK');
		}
		else {
			$this->_maxScore = self::DEFAULT_MAX_RANK;
		}
		if ($reflected->hasMethod('getStarRankTitles')) {
			$titles = call_user_func(array($this->modelClassName, 'getStarRankTitles'));
			// only accept the class's titles if the number of elements in the returned titles array is the same as 'max stars'.
			if ((is_array($titles)) && (count($titles) == $this->_maxScore)) {
				$this->_starsTitles = $titles;
			}
			else {
				$this->_starsTitles = array();
			}
		}
		// if titles is empty AND max stars is the same as DEFAULT_MAX_RANK initialize titles with our defaults
		if ((empty($this->_starsTitles)) && ($this->_maxScore == self::DEFAULT_MAX_RANK)) {
			$this->_starsTitles = $this->_getDefaultStarsTitles();
		}
		// note that it is possible to get here with an empty $this->starsTitles which will occur if custom DEFAULT_MAX_RANK was set
		// in model class and its value != self::DEFAULT_MAX_RANK)

		$this->_isInitialized = true;
	}

	/**
	 * Run method of this widget
	 */
	public function run() {
		if (!$this->_isInitialized) {
			// probably no modelId given (or similar). Nothing to do in this case as this widget always refers to a certain model.
			Yii::log("No rendering/processing if " . __CLASS__ . " is not initialized. Search for this 'non-initialization' reason in previous log messages... .", CLogger::LEVEL_ERROR, __METHOD__);
			return;
		}

		// check if this is a render or vote request
		$isAjax = Yii::app()->request->isAjaxRequest;
		if ((Yii::app()->request->getParam(self::STAR_RANK_AJAX_NOTIFIER)) && (Yii::app()->request->isAjaxRequest)) {
			/* this is an AJAX request in which the user attempts to submit a star rank. handle it. */

			// is ranking enabled for this model record?
			if (!$this->_isStarRankEnabledForModel()) {
				// Star Ranking is disabled for this specific model.
				Yii::log("User (user id = " . Yii::app()->user->id . ") tried to star-rank model class = " . $this->modelClassName . " with id = " . $this->modelId
						. " but star rank is disabled for this model object", CLogger::LEVEL_TRACE, __METHOD__);
				// do nothing other than the log above...
				return;
			}

			/* check if user is allowed to rank	*/
			if (!$this->_checkAccess("star rank content")) {
				Yii::log("User tried to star rank but he's not allowed to. User id = " . Yii::app()->user->id, CLogger::LEVEL_INFO, __METHOD__);
				// return some anwswer that might be used on client side to do something
				echo CJSON::encode(array(
					'status' => 'register first',
					'message' => Yii::t("PcStarRankModule.general", "Sorry - only registered, non-blocked users are allowed to rank content. Please {register} first.",
						array('{register}' => CHtml::link(Yii::t("PcStarRankModule.general", "register"), array(Yii::app()->params['registrationRoute'])))),
				));
				return;
			}

			/* check if user ranked this model already */
			if ($this->_wasModelRankedByUser(Yii::app()->user)) {
				// user ranked already. ignore his ranking, log this incident and show results view
				Yii::log("User id = " . Yii::app()->user->id . " attempted to star-rank again model name = '" . $this->modelClassName . "', id = " . $this->modelId . ". I smell a fraud attempt!", CLogger::LEVEL_WARNING, "SECURITY " . __METHOD__);
				echo CJSON::encode(array(
					'status' => 'fraud attempting?... . It has been noticed!',
					'message' => Yii::t("PcStarRankModule.general", "Error occurred"),
				));
				return;
			}

			/* Ok, all checks passed - now handle actual ranking. */
			/* @TODO enclose in try-catch block. upon failures just log the problem and return with a general error message from the catch block (no need for separate method for this
			 * as this is only handled here. */
			try {
				$average = $this->_rankModel($this->modelClassName, $this->modelId, Yii::app()->request->getParam('rank'), Yii::app()->user->id);
				if (!$average) {
					// means there was some problem in ranking. return an error message
					echo CJSON::encode(array(
						'status' => 'error',
						'message' => Yii::t("PcStarRankModule.general", "Error occurred"),
					));
					return;
				}
				else {
					echo CJSON::encode(array(
						'status' => 'success',
						'message' => Yii::t("PcStarRankModule.general", "Vote received!"),
						'average' => $average,
					));
					return;
				}
			}
			catch (CException $e) {
				// @TODO: log, echo json encode error occurred
			}
		}
		else if (Yii::app()->request->isAjaxRequest || Yii::app()->request->isPostRequest) {
			// some other ajax request. do nothing...
			return;
		}
		else {
			// render the star rank widget for display
			$this->controller->renderPartial("application.modules.PcStarRank.extensions.PcStarRankWidget.views.star_rank_widget", array(
				// we need the model name and id somehow conveyed into client side and sent back to us upon submission to be
				// able to tell to which model (name + id) the submission refers to since there might be several widgets on the same page.
				'model_name' => $this->modelClassName,
				'model_id' => $this->modelId,
				'existing_rank' => $this->_existing_rank,
				'titles' => $this->_starsTitles,
				'min' => $this->_minScore,
				'max' => $this->_maxScore,
				'readonly' => $this->_readOnly,
				'ratingStepSize' => "0.5",
				'numOfVotes' => $this->_numOfVotes,
			));
		}
	}

	/**
	 *
	 * Handles the actual rank recording
	 *
	 * @param string $model_name class name
	 * @param int $model_id id of the record for which this vote goes to.
	 * @param int $rank (should be in the limits of   $this->_minScore < $rank < $this->_maxScore
	 * @param int $user_id voting user id
	 *
	 * @return mixed - false for failures and int noting average score when succeeded (note error message logged with explanations).
	 *
	 * @throws CException
	 */
	private function _rankModel($model_name, $model_id, $rank, $user_id) {
		/* a little sanity checks: */
		if (($rank < $this->_minScore) || ($rank > $this->_maxScore)) {
			Yii::log("ERROR: passed rank ($rank) out of allowed range", CLogger::LEVEL_WARNING, "SECURITY " . __METHOD__);
			return false;
		}

		/*
		 * now record the voting.
		 * we need to:
		 * - create RankingVote record and save it. Since there's a slim, but existing, chance for race conditions we do this in a try-catch block
		 * - if no $this->_ranking exists for this model name + id, create one and save it.
		 * - then calculate updated statistics for $this->_ranking and update the record.
		 *
		 * Where ever possible and relevant, we use try catch to catch possible exceptions thrown due to reasons detailed for each case.
		 *
		 */

		// we need to first create the Ranking object since we need its PK as an FK for the RankingVote record
		if (isset($this->_ranking)) {
			$ranking_fk = $this->_ranking->id;
		}
		else {
			// create the fresh new Ranking object, save it and get its id:
			$ranking = new Ranking();
			$ranking->model_name = $this->modelClassName;
			$ranking->model_id = $this->modelId;
			try {
			// no need for validation - model name and id already validated when $this is initialized.
				// but, race condition can occur that will fail this saving. not a big deal though.
			$ranking->save(false);
			}
			catch (CException $e) {
				Yii::log("Error: tried to save new Ranking record for model class name={$ranking->model_name}, model id={$ranking->model_id} but exception was thrown. Exception's message: " . $e->getMessage(), CLogger::LEVEL_WARNING, __METHOD__);
				return false;
			}
			$this->_ranking = $ranking;
			$ranking_fk = $ranking->id;
		}
		/* in rare race conditions a double vote on the same user can occur simultaneously. In such a case an exception will
		 * be thrown due to db constraint. this is not catched here and should be handled by client of this method.
		 * if you check, you'll see that there's a single client to this method and putting a nice try-catch block there
		 * is simpler and simplifying the code.
		 * also, there's no need to catch this exception here. at worst, we're left with a fresh ranking record (and failure
		 * with the vote record), with average_score=0 and in enabled status - nothing harmful. */

		/*
		 * 1. Save the vote itself
		 * 2. update average score for $this->_ranking
		 * 		for all rank vote records for model name + id, sum up their score, divide in the number of those votes and
		 * 		round to an int.
		 * 		We do this in an atomic fashion since we wish to calculate and update the average based on fixed data that will
		 * 		not change under our feet. We'll also use the optimistic locking provided by PcBaseArModel class for the update.
		 */
		try {
			$trans = Yii::app()->db->beginTransaction();

			/*
			 *  save the rank vote.
			 */
			$rank_vote = new RankingVote();
			$rank_vote->ranking_id = $ranking_fk;
			$rank_vote->user_id = $user_id;
			$rank_vote->score_ranked = $rank;
			$rank_vote->save();

			/*
			 * Calculate average and save in the Ranking record
			 */
			// sum the score ranks total and num of votes
			$stats = Yii::app()->db->createCommand()
					->select('sum(score_ranked) as total_score, count(id) as votes_count')
					->from($rank_vote->tableName())
					->where('ranking_id=:id', array(':id' => $ranking_fk))
					->queryRow();
			$average = $this->_round_to_half($stats['total_score'] / $stats['votes_count']);
			// update the rankings record with the update average
			$this->_ranking->safelyUpdateByPk($this->_ranking->id, array('average_score' => $average));
			// just for the sake of completeness - god knows where tomorrow in this class we might refer to the average
			// after it has been saved, assuming its updated in the ranking object as well.
			$this->_ranking->average_score = $average;
			/*
			 * Update the model record itself with the average. This is useful if you need to sort records (of type "class name")
			 * by rating, for example. This would have been much more cumbersome to do if the average rating was saved only
			 * in the Ranking record for this model name+id.
			 * we do this using behavior we have in this module
			 */
			// load the model object
			$model = call_user_func(array($model_name, 'model'));
			/* @var CActiveRecord $record */
			$record = $model->findByPk((int)$model_id);
			if (! $record) {
				// really really strange... :) should never happen actually
				Yii::log("Trying to load $model_name with PK=$model_id and failed! Security issue?", CLogger::LEVEL_ERROR, "SECURITY " . __METHOD__);
				throw new CException("Trying to load $model_name with PK=$model_id and failed! That's really strange and I advise to have a look at this ASAP... .");
			}
			// attach our behavior to it
			$record->attachBehavior('avgRankUpdater', new PcAvgRankUpdaterBehavior);
			$record->updateAverage($average);

			// all passed - commit the change
			$trans->commit();
			return $average;
		}
		catch (CException $e) {
			Yii::log("Bummer, tried updating average score for ranking id {$this->_ranking->id} but I failed. Reported failure reason: " . $e->getMessage(), CLogger::LEVEL_WARNING, __METHOD__);
			$trans->rollback();
			return false;
		}
	}

	/**
	 * @return bool telling if $this->_ranking is in disabled status or not
	 */
	private function _isStarRankEnabledForModel() {
		if (!isset($this->_ranking)) {
			// no ranking object yet which is very reasonable and it is considered enabled.
			return true;
		}
		if ($this->_ranking->isDisabled()) {
			return false;
		}
		return true;
	}

	/**
	 * Tells whether $user already ranked the model 'this' is currently running in context.
	 *
	 * @param CWebUser $user
	 * @return bool noting whether user voted or not
	 */
	private function _wasModelRankedByUser(CWebUser $user) {
		if (!isset($this->_ranking)) {
			// no ranking object yet means no vote for sure by this user
			return false;
		}
		$ranking_vote = RankingVote::model()->findByAttributes(array('ranking_id' => $this->_ranking->id, 'user_id' => $user->id));
		if ($ranking_vote) {
			// yep, we did find a RankingVote for 'this' user for this ranking model (meaning model name + id).
			// this means the user have ranked the model record before.
			return true;
		}
		// didn't rank
		return false;
	}

	private function _getDefaultStarsTitles() {
		return array(
			'1' => Yii::t("PcStarRankModule.general", '0.5'),
			'2' => Yii::t("PcStarRankModule.general", '1'),
			'3' => Yii::t("PcStarRankModule.general", '1.5'),
			'4' => Yii::t("PcStarRankModule.general", '2'),
			'5' => Yii::t("PcStarRankModule.general", '2.5'),
			'6' => Yii::t("PcStarRankModule.general", '3'),
			'7' => Yii::t("PcStarRankModule.general", '3.5'),
			'8' => Yii::t("PcStarRankModule.general", '4'),
			'9' => Yii::t("PcStarRankModule.general", '4.5'),
			'10' => Yii::t("PcStarRankModule.general", '5'),

		);
	}

	/**
	 * Class internal access control check to determine if the user has permission to do $permission.
	 * We use this method to create a single method to update depending on the access control used by your system.
	 *
	 * By default, this method uses Yii's RBAC system and will check permission to 'star rank content'.
	 *
	 * Update this method upon a need basis.
	 *
	 * @param string $permission the permission label to check
	 * @return bool
	 *
	 */
	private function _checkAccess($permission) {
		if (Yii::app()->user->checkAccess("star rank content")) {
			return true;
		}
		return false;
	}

	/**
	 * Auxilary mathematical function that helps calculating average ranking by rounding to nearest half integer
	 *
	 * @param $num
	 * @return float
	 */
	private function _round_to_half($num) {
		if ($num >= ($half = ($ceil = ceil($num)) - 0.5) + 0.25) return $ceil;
		else if ($num < $half - 0.25) return floor($num);
		else return $half;
	}
}

<?php
/**
 * PcAvgRankUpdaterBehavior.php
 * Created on 19 06 2012 (10:59 AM)
 *
 */

class PcAvgRankUpdaterBehavior extends CActiveRecordBehavior {
	// DB column name for storing average rank
	const AVERAGE_ATTRIBUTE = 'average_rank';

	/**
	 *
	 * Updates the average rank, as provided to method, in the record's DB.
	 * Note that no optimistic locking should apply (@see http://www.yiiframework.com/extension/pcbasearmodel/) since
	 * we prefer small chance of small, temporal inaccuracies here rather than 'stale object' exceptions thrown in other
	 * parts of the application since the same record might be already handled elsewhere in the code in the same request.
	 *
	 * @param float $avg should in the in format of X.Y. E.g., 3.5. In the DB level, for example for MySQL, it should
	 *             be in the format of DECIMAL(2,1)
	 * @return bool
	 *
	 * @throws CException
	 */
	public function updateAverage($avg) {
		// first, a little sanity to check that the kind developer has made sure that the needed attribute is defined
		// for this model type
		if (!$this->owner->hasAttribute(self::AVERAGE_ATTRIBUTE)) {
			throw new CException("Oops - attempting to update average rank value for object of class "
					. get_class($this->owner)
					. " with id = " . $this->owner->getPrimaryKey()
					. " but this class has no attribute named " . self::AVERAGE_ATTRIBUTE
					. " which I need in order to save the average in! Please check DB table and update AR class accordingly.");
		}

		// do the update and return accordingly
		return $this->owner->updateByPk($this->owner->getPrimaryKey(), array(self::AVERAGE_ATTRIBUTE => $avg));
	}

}

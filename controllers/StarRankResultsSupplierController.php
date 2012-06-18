<?php
/**
 * StarRankResultsSupplierController.php
 */

class StarRankResultsSupplierController extends Controller {
	/**
	 * Aux method that's helping returning a clean 'partial' response, which is suitable for AJAX processing on client side.
	 * Why do we need this?
	 * @see http://rymland.org/post/28?title=Using+a+widget+located+in+a+module+as+an+AJAX+action+provider
	 *
	 */
	public function actionProcessVote() {
		if (Yii::app()->request->isAjaxRequest) {
			// instantiate the widget with $captureOutput = 1.
			// echo the content received... . It will do the rest! :)
			$results = $this->widget('PcStarRankWidget', array(
					'modelClassName' => Yii::app()->request->getParam('name'),
					'modelId' => Yii::app()->request->getParam('id')),
				true);
			echo $results;
		}

	}
}

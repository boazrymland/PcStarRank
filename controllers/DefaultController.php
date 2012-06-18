<?php

/**
 * There's no real need for the 'default controller' for this module
 */
class DefaultController extends Controller {
	public function actionIndex() {
		throw new CHttpException(404, Yii::t("PcStarRankingModule.general", "The requested page does not exist."));
	}
}
<?php
/**
 * star_rank_widget.php
 * Created on 13 06 2012 (9:48 PM)
 *
 *
 */
$votes_plus_one = $numOfVotes + 1;
$this->widget('CStarRating', array(
	'name' => strtolower($model_name) . "-" . $model_id, // an unique name
	//'starCount' => $max,
	//'maxRating' => $max,
	//'minRating' => $min,
	'readOnly' => $readonly,
	/* ok, so you might wonder why am I hard coding below. Actually, you should :)
		Thing is, I failed to find out why the CStarRating doesn't behave right, as expected, with regard to stars rating.
		It just doesn't work as expected. See more in threads like this:
		http://www.yiiframework.com/forum/index.php/topic/24789-cstarrating-buggy/
		The hard coded values below are taken from a post on that thread and it suits my needs on a specific project.
		They will get you going with 5 stars rating, with a resolution of 1/2 star when voting and displaying current vote
	*/
	//'minRating' => 0.5,
	//'maxRating' => 5,
	//'ratingStepSize' => 0.5,
	//'starCount' => 5,
	// I also wasn't able to get titles show up (no matter what I tried in PcStarRankWidget._getDefaultStarsTitles(), for half-stars configuration), hence the comment out below... .
	//'titles' => $titles,
	//'ratingStepSize' => "$ratingStepSize",
	// remove that reset button...
	'allowEmpty' => false,
	'value' => (int)$existing_rank,
	// updates the div with the new rating info, displays a message for 5 seconds and makes the //widget readonly
	'callback' => '
        function(){
                url = "' . Yii::app()->createUrl('starRank/StarRankResultsSupplier/processVote', array("StarRankAjaxSubmission" => true)) . '";
                jQuery.getJSON(url, {name: "' . $model_name . '", id: ' . $model_id . ', rank: $(this).val()}, function(data) {
                	if (data.status == "register first"){
                	                		$("#vcounter-' . strtolower($model_name) . '-' . $model_id . '").html("(' . $numOfVotes . " " . Yii::t("PcStarRankModule.general", "votes") . ') " + data.message);
                	                    }
                	else if (data.status == "success"){
                		$("#vcounter-' . strtolower($model_name) . '-' . $model_id . '").html("(' . $votes_plus_one . " " . Yii::t("PcStarRankModule.general", "votes") . ') " + data.message);
                    }
                    else {
                    	$("#vcounter-' . strtolower($model_name) . '-' . $model_id . '").html("(' . $numOfVotes . " " . Yii::t("PcStarRankModule.general", "votes") . ') " + data.message);
                    }
                    $("input[name*=' . strtolower($model_name) . '-' . $model_id . ']").rating("readOnly",true);
                    return false;
                });}'
));
echo "<span id='vcounter-" . strtolower($model_name) . "-" . $model_id . "'>($numOfVotes " . Yii::t("PcStarRankModule.general", "votes") . ")</span>";
?>


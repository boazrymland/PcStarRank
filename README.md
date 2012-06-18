PcStarRank
==========

Yii extension (module) for 'star ranking' content on a Yii based webapp/site

# Introduction & Features

This module provides a plug & play style component that is 'attachable' to probably any content on your Yii site that has a separate DB table for it (or multiple tables). Using it, your content can be ranked by users who have permission to rank content.

This module uses internally Yii's built in CStarRating to render the actual rating UI but this UI can be replaced with whatever you prefer.

# Requirements

* I've built and tested this extension on the latest version of Yii at the time of writing, v1.1.10
* This extension uses the base active record class provided by [PcBaseArModel extension](http://www.yiiframework.com/extension/pcbasearmodel/) and thus depends on it. Please refer to the link given for documentation on that extension.

# Installation

* Unpack the contents of this extension and place it under */protected/modules/PcStarRank/* directory
* In main.php config file:

```php
// autoloading model and component classes
  'import' => array(
    //...
    // Star rank module
    'application.modules.PcStarRank.*',
    'application.modules.PcStarRank.models.*',
    'application.modules.PcStarRank.controllers.*',
    'application.modules.PcStarRank.extensions.PcStarRankWidget.*',
    //...
    ),
  //...
  'modules' => array(
    //...
    'starRank' => array(
      'class' => 'application.modules.PcStarRank.PcStarRankModule'
    ),
    //...
  ),
```

# Usage

# Developer notes

The following notes are meant for developers:
* DB schema is ignorant to actual rating mechanism used. There are scores, min, max but there's no 'stars' mentioned anywhere. Feel free to change actual rating mechanism (typically some JS trickery). I don't have time at the moment to document the interface between the frontend and the backend parts of the widget/module.
* 


# Dependencies/Decapsulation considerations

* This extension expects 'users' table with 'id' as its primary key. It is used to record votes in ranking_votes table with the voting user's id.
* This extension was **not** designed for voting by guest user.
* Ranking and RankingVote classes both extend *PcBaseArModel* and not CActiveRecord (since we use its optimistic locking feature). Therefore, this extension depends on the [PcBaseArModel](http://www.yiiframework.com/extension/pcbasearmodel/) extension.
* The included widget supports min and max number of stars per model class. A default of 1 and 5, respectively, is defined in this widget. To define other values be sure to have two constant in the model class named STARRANK_MIN_RANK and STARRANK_MAX_RANK. Both should be defined or none.
* The included widget supports descriptive text on each star in the widget, to be supplied by the model class. If this is desired, define a static method in the model class named *getStarRankTitles()*. This method should return an array with numerical keys and values that should be the titles for the stars. Make sure that the number of elements returned by this method is the same as your model class constant STARRANK_MAX_STARS... .
* The widget implements an internal method for access control for determination if a certain user can star-rank or not. By default, it is set to check access using Yii's RBAC system, checking for permission to "star rank content". Update this method if you use some other access control method, like the simpler 'access control filter' method (see official documentation [here](http://www.yiiframework.com/doc/guide/1.1/en/topics.auth#access-control-filter))
                          





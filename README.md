PcStarRank
==========

Yii extension (module) for 'star ranking' content on a Yii based webapp/site

# Introduction & Features

* This module provides a plug & play style component that is 'attachable' to probably any content on your Yii site that has a separate DB table for it (or multiple tables). Using it, your content can be ranked by users who have permission to rank content. 
* This module should support multiple rendered copies of it on the same page, for several rank-able content items (I didn't test it so far but designed it for this use case and highly likely that this works).

This module uses internally Yii's built in CStarRating to render the actual rating UI but this UI can be replaced with whatever you prefer.

## Requirements

* I've built and tested this extension on the latest version of Yii at the time of writing, v1.1.10
* This extension uses the base active record class provided by [PcBaseArModel extension](http://www.yiiframework.com/extension/pcbasearmodel/) and thus depends on it. Please refer to the link given for documentation on that extension.
* There are several more dependancies that are detailed in the section titled "Dependencies/Decapsulation considerations" below. Among which are (in a nutshell):
  * This module depends on a table for users named *users* with a primary key column named *id*.
  * By default, the included widget **depends** on Yii's RBAC system to be working and expects a permission named "star rank content". This can be overriden however. It is covered in greater depth below... .

### Dependencies/Decapsulation considerations

* This extension expects *users* table with *id* as its primary key. It is used to impose some constraints in the DB level and to link PK-FK between record votes in *ranking_votes* table to the actual users in the *users* table
* Ranking and RankingVote classes both extend *PcBaseArModel* and not CActiveRecord (since we use its optimistic locking feature). Therefore, this extension depends on the [PcBaseArModel](http://www.yiiframework.com/extension/pcbasearmodel/) extension.
* The included widget supports min and max number of stars per model class. A default of 1 and 5, respectively, is defined in this widget. To define other values be sure to have two constant in the model class named STARRANK_MIN_RANK and STARRANK_MAX_RANK. Both should be defined or none.
* The included widget supports descriptive text on each star in the widget, to be supplied by the model class. If this is desired, define a static method in the model class named *getStarRankTitles()*. This method should return an array with numerical keys and values that should be the titles for the stars. Make sure that the number of elements returned by this method is the same as your model class constant STARRANK_MAX_STARS... .
* The widget implements an internal method for access control for determination if a certain user can star-rank or not. By default, it is set to check access using Yii's RBAC system, checking for permission to "star rank content". Update this method if you use some other access control method, like the simpler 'access control filter' method (see official documentation [here](http://www.yiiframework.com/doc/guide/1.1/en/topics.auth#access-control-filter))
 

## Limitations

* This extension was **not** designed for voting by the guest user.
* Despite my best intentions I couldn't get to control CStarRating UI options as I wanted/needed. I simply wasn't able to figure out (==overcome bugs, I guess) how the heck I can have my own number of stars, with certain 'ratingStepSize', custom 'titles' etc. I resorted to using CStarRating defaults, which is almost what I needed anyway in the project for which this extension was developed for. See more about such problems reported in [this forum thread](http://www.yiiframework.com/forum/index.php/topic/24789-cstarrating-buggy/).

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

* Configure Yii's main.php as noted above
* Make sure the needed requirements have been met.
* In the page where you need to have the the content to be rated, put the following code in the relevant view file that will render the needed UI widget:

```php
$this->widget('PcStarRankWidget', array('modelId' => $model->id, 'modelClassName' => get_class($model)));
```

# Developer notes

The following notes are meant for developers:
* DB schema is ignorant to actual rating mechanism used. There are scores, min, max but there's no 'stars' mentioned anywhere. Feel free to change actual rating mechanism (typically some JS trickery). I don't have time at the moment to document the interface between the frontend and the backend parts of the widget/module.
* The code itself is full of comments that document my decisions/contemplations/etc. I use them to track my thoughts/decisions later on. You're invited to read the code to learn more and see more. In short: *"Use the force! - read the source!"* :-)

## Room for development

Further room for development, and its only notes I've quickly pulled out. Feel free to submit more...:

* Damn, did I really left cache un-implemented? It appears so :-( . Time, I lack time... . Caching here is natural (but where isnt it?...).
* Develop the cronjob mentioned in the 'TODOs' section.
* Check even further possible security issues. For a start:
  * Not a big deal (IMHO): when the widget is rendered the content class name is given in the html so client side can know class names of models in our system.
  * Continuing last bullet, rogue users can try and mess with the system by submitting ranks for models that the developer didn't intend from the start to be rank-able, like "User" class. This is pretty harmful as indeed ranking_votes and rankings records will be created but still. Consider having some configuration parameter to the module (main.php) that lists all 'rankable' class names in an array. This requires manitaining but solves the problem just described.
                         
# TODOs

* Have some cronjob to clean dangling rankings. We cannot do this (easily at least) via a DB constraint since this table references multiple other, unknown and dynamic tables (via model_class column which is a string). Don't worry about ranking_votes - that one DOES have a constraint to delete records that are being deleted on rankings (and rankings to ranking_votes is one to many. There will always be a rankings record for a ranking_votes record).
                                                                                                                                                                                                                                                          
# Resources

* I've used [this forum thread](http://www.yiiframework.com/forum/index.php/topic/29851-complete-guide-for-multiple-cstarratings-on-same-page/) for information/tips during development.




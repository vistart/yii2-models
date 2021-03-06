<?php

/**
 *  _   __ __ _____ _____ ___  ____  _____
 * | | / // // ___//_  _//   ||  __||_   _|
 * | |/ // /(__  )  / / / /| || |     | |
 * |___//_//____/  /_/ /_/ |_||_|     |_|
 * @link http://vistart.name/
 * @copyright Copyright (c) 2016 vistart
 * @license http://vistart.name/license/
 */

namespace vistart\Models\tests\data\ar;

use vistart\Models\models\BaseUserRelationModel;

/**
 * Description of UserRelation
 *
 * @author vistart <i@vistart.name>
 */
class UserRelation extends BaseUserRelationModel
{

    public $multiBlamesAttribute = 'groups';

    public function init()
    {
        $this->multiBlamesClass = UserRelationGroup::className();
        parent::init();
    }

    public static function tableName()
    {
        return '{{%user_relation}}';
    }

    /**
     * Friendly to IDE.
     * @return \vistart\Models\queries\BaseUserRelationQuery
     */
    public static function find()
    {
        return parent::find();
    }
}

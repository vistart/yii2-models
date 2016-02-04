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

namespace vistart\Models\models;

use vistart\Models\traits\UserRelationGroupTrait;

/**
 * This abstract class is used for building user relation group.
 *
 * $contentAttribute name of user relation group.
 * $contentTypeAttribute type of user relation group.
 * 
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
abstract class BaseUserRelationGroupModel extends BaseBlameableModel
{
    use UserRelationGroupTrait;

    public $confirmationAttribute = false;
    public $enableIP = false;
    public $idAttribute = false;
    public $updatedAtAttribute = false;
    public $updatedByAttribute = false;

    public function init()
    {
        if (!is_string($this->queryClass)) {
            $this->queryClass = \vistart\Models\queries\BaseBlameableQuery::className();
        }
        if ($this->skipInit) {
            return;
        }
        $this->initUserRelationGroupEvents();
        parent::init();
    }
}

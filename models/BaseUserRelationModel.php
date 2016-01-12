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

use vistart\Models\traits\UserRelationTrait;

/**
 * Description of BaseFriendModel
 *
 * @author vistart <i@vistart.name>
 */
abstract class BaseUserRelationModel extends BaseMongoBlameableModel {

    use UserRelationTrait;

    public $confirmationAttribute = false;
    public $contentAttribute = false;
    public $idAttribute = false;
    public $updatedByAttribute = false;

    public function rules() {
        return array_merge(parent::rules(), $this->getOtherGuidRules());
    }

}
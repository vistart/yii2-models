<?php

/**
 *  _   __ __ _____ _____ ___  ____  _____
 * | | / // // ___//_  _//   ||  __||_   _|
 * | |/ // /(__  )  / / / /| || |     | |
 * |___//_//____/  /_/ /_/ |_||_|     |_|
 * @link https://vistart.name/
 * @copyright Copyright (c) 2016 vistart
 * @license https://vistart.name/license/
 */

namespace vistart\Models\tests\data\ar;

/**
 * Description of MongoBlameable
 *
 * @author vistart <i@vistart.name>
 */
class MongoBlameable extends \vistart\Models\models\BaseMongoBlameableModel
{

    public static function collectionName()
    {
        return ['yii2-models', 'blameable'];
    }
}

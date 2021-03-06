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

namespace vistart\Models\queries;

use vistart\Models\models\BaseUserRelationModel;
use vistart\Models\traits\MutualQueryTrait;

/**
 * Description of BaseUserRelationQuery
 *
 * Note: You must specify $modelClass property, and the class must be the subclass
 * of `\vistart\Models\models\BaseUserRelationModel`.
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
class BaseUserRelationQuery extends BaseBlameableQuery
{
    use MutualQueryTrait;

    /**
     * Specify groups.
     * This method will be skipped if not enable the group features (`$multiBlamesAttribute = false`).
     * @param string|array $groups the guid of group If string, or guid array of
     * groups if array. If you want to get ungrouped relation(s), please assign
     * empty array, or if you do not want to delimit group(s), please do not
     * access this method, or assign null.
     * @return static $this
     */
    public function groups($groups = [])
    {
        if ($groups === null) {
            return $this;
        }
        $model = $this->noInitModel;
        if (!is_string($model->multiBlamesAttribute)) {
            return $this;
        }
        if (empty($groups)) {
            return $this->andWhere([$model->multiBlamesAttribute => BaseUserRelationModel::getEmptyGroupJson()]);
        }
        return $this->andWhere(['or like', $model->multiBlamesAttribute, $groups]);
    }
}

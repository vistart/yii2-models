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

namespace vistart\Models\traits;

use yii\db\ActiveQuery;

/**
 * This trait attach two base condition.
 *
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
trait QueryTrait
{

    /**
     * Attach like condition.
     * @param mixed $value
     * @param string $attribute
     * @param string|false $like
     * @return $this
     */
    protected function likeCondition($value, $attribute, $like = false)
    {
        if (!is_string($attribute)) {
            return $this;
        }
        if ($like) {
            return $this->andWhere([$like, $attribute, $value]);
        }
        return $this->andWhere([$attribute => $value]);
    }

    /**
     * Specify range wilth $attribute to $query.
     * @param ActiveQuery $query
     * @param string $attribute
     * @param string $start
     * @param string $end
     * @return $query
     */
    protected static function range($query, $attribute, $start = null, $end = null)
    {
        if (!empty($start)) {
            $query = $query->andWhere(['>=', $attribute, $start]);
        }
        if (!empty($end)) {
            $query = $query->andWhere(['<', $attribute, $end]);
        }
        return $query;
    }
}

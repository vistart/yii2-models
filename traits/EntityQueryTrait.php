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

/**
 * This trait is used for building entity query class for entity model.
 *
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
trait EntityQueryTrait
{
    use QueryTrait;

    public $noInitModel;

    /**
     * Build model without any initializations.
     */
    public function buildNoInitModel()
    {
        if (empty($this->noInitModel) && is_string($this->modelClass)) {
            $modelClass = $this->modelClass;
            $this->noInitModel = $modelClass::buildNoInitModel();
        }
    }

    /**
     * Specify guid attribute.
     * @param string|array $guid
     * @param false|string $like false, 'like', 'or like', 'not like', 'or not like'.
     * @return $this
     */
    public function guid($guid, $like = false)
    {
        $model = $this->noInitModel;
        return $this->likeCondition($guid, $model->guidAttribute, $like);
    }

    /**
     * Specify id attribute.
     * @param string|integer|array $id
     * @param false|string $like false, 'like', 'or like', 'not like', 'or not like'.
     * @return $this
     */
    public function id($id, $like = false)
    {
        $model = $this->noInitModel;
        return $this->likeCondition($id, $model->idAttribute, $like);
    }

    /**
     * Specify create time range.
     * @param string $start
     * @param string $end
     * @return $this
     */
    public function createdAt($start = null, $end = null)
    {
        $model = $this->noInitModel;
        if (!is_string($model->createdAtAttribute)) {
            return $this;
        }
        return static::range($this, $model->createdAtAttribute, $start, $end);
    }

    /**
     * Specify update time range.
     * @param string $start 
     * @param string $end
     * @return $this
     */
    public function updatedAt($start = null, $end = null)
    {
        $model = $this->noInitModel;
        if (!is_string($model->updatedAtAttribute)) {
            return $this;
        }
        return static::range($this, $model->updatedAtAttribute, $start, $end);
    }
}

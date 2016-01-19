<?php

/*
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
 * Description of BlameableQueryTrait
 *
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
trait BlameableQueryTrait
{

    public function confirmed($isConfirmed = true)
    {
        $model = $this->noInitModel;
        if (!is_string($model->confirmationAttribute)) {
            return $this;
        }
        return $this->andWhere([$model->confirmationAttribute => $isConfirmed]);
    }

    public function content($content, $like = false)
    {
        $model = $this->noInitModel;
        if (!is_string($model->contentAttribute)) {
            return $this;
        }
        if (!$like) {
            return $this->andWhere([$like, $model->contentAttribute, $content]);
        }
        return $this->andWhere([$model->contentAttribute => $content]);
    }
}

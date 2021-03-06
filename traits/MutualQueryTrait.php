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

namespace vistart\Models\traits;

/**
 * This trait is used for building query class which contains mutual relation operations.
 *
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
trait MutualQueryTrait
{

    /**
     * Get the opposite relation.
     * @param BaseUserModel|string $user initiator
     * @param BaseUserModel|string $other recipient.
     * @param Connection $database
     * @return 
     */
    public function opposite($user, $other, $database = null)
    {
        $model = $this->noInitModel;
        return $this->andWhere([$model->createdByAttribute => $other, $model->otherGuidAttribute => $user])->one($database);
    }

    /**
     * Get all the opposites.
     * @param string $user initator.
     * @param array $others all recipients.
     * @param Connection $database
     * @return array instances.
     */
    public function opposites($user, $others = [], $database = null)
    {
        $model = $this->noInitModel;
        $query = $this->andWhere([$model->otherGuidAttribute => $user]);
        if (!empty($others)) {
            $query = $query->andWhere([$model->createdByAttribute => array_values($others)]);
        }
        return $query->all($database);
    }

    /**
     * Specify initiators.
     * @param string|array $users the guid of initiator if string, or guid array
     * of initiators if array.
     * @return \static $this
     */
    public function initiators($users = [])
    {
        if (empty($users)) {
            return $this;
        }
        $model = $this->noInitModel;
        return $this->andWhere([$model->createdByAttribute => $users]);
    }

    /**
     * Specify recipients.
     * @param string|array $users the guid of recipient if string, or guid array
     * of recipients if array.
     * @return \static $this
     */
    public function recipients($users = [])
    {
        if (empty($users)) {
            return $this;
        }
        $model = $this->noInitModel;
        return $this->andWhere([$model->otherGuidAttribute => $users]);
    }
}

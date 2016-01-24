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
 * Description of SelfBlameableTrait
 *
 * This trait require GUIDTrait enabled.
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
trait SelfBlameableTrait
{

    public $parentAttribute = false;
    public $parentTypeAttribute;
    public static $parentNone = 0;
    public static $parentParent = 1;
    public static $parentTypes = [
        0 => 'root',
        1 => 'parent',
    ];
    public static $onUpdateNoAction = 0;
    public static $onUpdateRestrict = 1;
    public static $onUpdateCascade = 2;
    public static $onUpdateSetNull = 3;
    public static $onUpdateTypes = [
        0 => 'on action',
        1 => 'restrict',
        2 => 'cascade',
        3 => 'set null',
    ];
    public $onDeleteType = 2;
    public $onUpdateType = 2;

    public function getSelfBlameableRules()
    {
        if (!is_string($this->parentAttribute)) {
            return [];
        }
        return [
            [[$this->parentAttribute], 'string', 'max' => 36],
            [[$this->parentTypeAttribute], 'in', 'range' => array_keys(static::$parentTypes)],
            [[$this->parentTypeAttribute], 'default', 'value' => 0],
            [[$this->parentTypeAttribute], 'required'],
        ];
    }

    /**
     * Bear a child.
     * @return static
     */
    public function bear()
    {
        return new static([$this->parentAttribute => $this->guid, $this->parentTypeAttribute => static::$parentParent]);
    }

    /**
     * 
     * @param \yii\base\Event $event
     * @return boolean
     */
    public function onDeleteChildren($event)
    {
        $sender = $event->sender;
        if (!is_string($sender->parentAttribute)) {
            return true;
        }
        switch ($sender->onDeleteType) {
            case static::$onUpdateNoAction:
                $event->isValid = true;
                break;
            case static::$onUpdateRestrict:
                $event->isValid = $sender->getChildren() === null;
                break;
            case static::$onUpdateCascade:
                $event->isValid = $sender->deleteChildren();
                break;
            case static::$onUpdateSetNull:
                $event->isValid = $sender->updateChildren(null);
                break;
        }
    }

    /**
     * 
     * @param \yii\base\Event $event
     * @return boolean
     */
    public function onUpdateChildren($event)
    {
        $sender = $event->sender;
        if (!is_string($sender->parentAttribute)) {
            return true;
        }
        switch ($sender->onUpdateType) {
            case static::$onUpdateNoAction:
                $event->isValid = true;
                break;
            case static::$onUpdateRestrict:
                $event->isValid = $sender->getChildren() === null;
                break;
            case static::onUpdateCascade:
                $event->isValid = $sender->updateChildren();
                break;
            case static::onUpdateSetNull:
                $event->isValid = $sender->updateChildren(null);
                break;
        }
    }

    /**
     * Get children, not grandchildren.
     * @return static[]
     */
    public function getChildren()
    {
        return static::find()->where([$this->parentAttribute => $this->guid, $this->parentTypeAttribute => static::$parentParent])->all();
    }

    /**
     * 
     * @param mixed $value
     * @return \yii\db\IntegrityException|boolean
     * @throws \yii\db\IntegrityException
     */
    public function updateChildren($value = false)
    {
        $children = $this->getChildren();
        if (empty($children)) {
            return true;
        }
        $parentAttribute = $this->parentAttribute;
        $transaction = $this->getDb()->beginTransaction();
        try {
            foreach ($children as $child) {
                if ($value === false) {
                    $child->$parentAttribute = $this->guid;
                } elseif (empty($value)) {
                    $child->$parentAttribute = '';
                } else {
                    $child->$parentAttribute = $value;
                }
                if (!$child->save()) {
                    throw new \yii\db\IntegrityException('Update failed:' . $child->errors);
                }
            }
            $transaction->commit();
        } catch (\yii\db\IntegrityException $ex) {
            $transaction->rollBack();
            if (YII_DEBUG || YII_ENV !== YII_ENV_PROD) {
                Yii::error($ex->errorInfo, static::className() . '\update');
                return $ex;
            }
            Yii::warning($ex->errorInfo, static::className() . '\update');
            return false;
        }
        return true;
    }

    /**
     * 
     * @return \yii\db\IntegrityException|boolean
     * @throws \yii\db\IntegrityException
     */
    public function deleteChildren()
    {
        $children = $this->getChildren();
        if (empty($children)) {
            return true;
        }
        $transaction = $this->getDb()->beginTransaction();
        try {
            foreach ($children as $child) {
                if (!$child->delete()) {
                    throw new \yii\db\IntegrityException('Delete failed:' . $child->errors);
                }
            }
            $transaction->commit();
        } catch (\yii\db\IntegrityException $ex) {
            $transaction->rollBack();
            if (YII_DEBUG || YII_ENV !== YII_ENV_PROD) {
                Yii::error($ex->errorInfo, static::className() . '\delete');
                return $ex;
            }
            Yii::warning($ex->errorInfo, static::className() . '\delete');
            return false;
        }
        return true;
    }

    /**
     * 
     * @param \yii\base\Event $event
     * @return boolean
     */
    public function onParentGuidChanged($event)
    {
        $sender = $event->sender;
        if ($sender->isAttributeChanged($sender->guidAttribute)) {
            return $sender->onUpdateChildren($event);
        }
    }

    protected function initSelfBlameableEvents()
    {
        $this->on(static::EVENT_BEFORE_UPDATE, [$this, 'onParentGuidChanged']);
        $this->on(static::EVENT_BEFORE_DELETE, [$this, 'onDeleteChildren']);
    }
}
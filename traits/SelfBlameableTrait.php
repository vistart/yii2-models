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

use yii\base\ModelEvent;
use yii\db\ActiveQuery;
use yii\db\IntegrityException;

/**
 * This trait is designed for the model who contains parent.
 * The BlameableTrait use this trait by default. If you want to use this trait
 * into seperate model, please call the `initSelfBlameableEvents()` method in
 * `init()` method, like following:
 * ```php
 * public function init()
 * {
 *     $this->initSelfBlameableEvents();  // put it before parent call.
 *     parent::init();
 * }
 * ```
 * 
 * @property static $parent
 * @property-read static[] $ancestors
 * @property-read string[] $ancestorChain
 * @property-read static $commonAncestor
 * @property-read static[] $children
 * @property-read static[] $oldChildren
 * @property array $selfBlameableRules
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
trait SelfBlameableTrait
{

    /**
     * @var false|string attribute name of which store the parent's guid.
     */
    public $parentAttribute = false;

    /**
     * @var string|array rule name and parameters of parent attribute, as well
     * as self referenced ID attribute.
     */
    public $parentAttributeRule = ['string', 'max' => 36];

    /**
     * @var string self referenced ID attribute.
     */
    public $refIdAttribute = 'guid';
    public static $parentNone = 0;
    public static $parentParent = 1;
    public static $parentTypes = [
        0 => 'none',
        1 => 'parent',
    ];
    public static $onNoAction = 0;
    public static $onRestrict = 1;
    public static $onCascade = 2;
    public static $onSetNull = 3;
    public static $onUpdateTypes = [
        0 => 'on action',
        1 => 'restrict',
        2 => 'cascade',
        3 => 'set null',
    ];

    /**
     * @var integer indicates the on delete type. default to cascade.
     */
    public $onDeleteType = 2;

    /**
     * @var integer indicates the on update type. default to cascade.
     */
    public $onUpdateType = 2;

    /**
     * @var boolean indicates whether throw exception or not when restriction occured on updating or deleting operation.
     */
    public $throwRestrictException = false;
    private $localSelfBlameableRules = [];
    public static $eventParentChanged = 'parentChanged';
    public static $eventChildAdded = 'childAdded';

    /**
     * @var false|integer Set the limit of ancestor level. False is no limit.
     */
    public $ancestorLimit = false;

    /**
     * @var false|integer Set the limit of children. False is no limit.
     */
    public $childrenLimit = false;

    /**
     * Get rules associated with self blameable attribute.
     * @return array rules.
     */
    public function getSelfBlameableRules()
    {
        if (!is_string($this->parentAttribute)) {
            return [];
        }
        if (!empty($this->localSelfBlameableRules) && is_array($this->localSelfBlameableRules)) {
            return $this->localSelfBlameableRules;
        }
        if (is_string($this->parentAttributeRule)) {
            $this->parentAttributeRule = [$this->parentAttributeRule];
        }
        $this->localSelfBlameableRules = [
            array_merge([$this->parentAttribute], $this->parentAttributeRule),
        ];
        return $this->localSelfBlameableRules;
    }

    /**
     * Set rules associated with self blameable attribute.
     * @param array $rules rules.
     */
    public function setSelfBlameableRules($rules = [])
    {
        $this->localSelfBlameableRules = $rules;
    }

    /**
     * Check whether this model has reached the ancestor limit.
     * If $ancestorLimit is false, it will be regared as no limit(return false).
     * If $ancestorLimit is not false and not a number, 256 will be taken.
     * @return boolean
     */
    public function hasReachedAncestorLimit()
    {
        if ($this->ancestorLimit === false) {
            return false;
        }
        if (!is_numeric($this->ancestorLimit)) {
            $this->ancestorLimit = 256;
        }
        return count($this->getAncestorChain()) >= $this->ancestorLimit;
    }

    /**
     * Check whether this model has reached the children limit.
     * If $childrenLimit is false, it will be regarded as no limit(return false).
     * If $childrenLimist is not false and not a number, 256 will be taken.
     * @return boolean
     */
    public function hasReachedChildrenLimit()
    {
        if ($this->childrenLimit === false) {
            return false;
        }
        if (!is_numeric($this->childrenLimit)) {
            $this->childrenLimit = 256;
        }
        return ((int) $this->getChildren()->count()) >= $this->childrenLimit;
    }

    /**
     * Bear a child.
     * @param array $config
     * @return static|null Null if reached the ancestor limit or children limit.
     */
    public function bear($config = [])
    {
        if ($this->hasReachedAncestorLimit() || $this->hasReachedChildrenLimit()) {
            return null;
        }
        if (isset($config['class'])) {
            unset($config['class']);
        }
        $refIdAttribute = $this->refIdAttribute;
        $config[$this->parentAttribute] = $this->$refIdAttribute;
        return new static($config);
    }

    /**
     * Add a child.
     * @param static $child
     * @return boolean
     */
    public function addChild($child)
    {
        return $this->hasReachedChildrenLimit() ? $child->setParent($this) : false;
    }

    /**
     * Event triggered before deleting itself.
     * @param ModelEvent $event
     * @return boolean true if parentAttribute not specified.
     * @throws IntegrityException throw if $throwRestrictException is true when $onDeleteType is on restrict.
     */
    public function onDeleteChildren($event)
    {
        $sender = $event->sender;
        if (!is_string($sender->parentAttribute)) {
            return true;
        }
        switch ($sender->onDeleteType) {
            case static::$onRestrict:
                $event->isValid = $sender->children === null;
                if ($this->throwRestrictException) {
                    throw new IntegrityException('Delete restricted.');
                }
                break;
            case static::$onCascade:
                $event->isValid = $sender->deleteChildren();
                break;
            case static::$onSetNull:
                $event->isValid = $sender->updateChildren(null);
                break;
            case static::$onNoAction:
            default:
                $event->isValid = true;
                break;
        }
    }

    /**
     * Event triggered before updating itself.
     * @param ModelEvent $event
     * @return boolean true if parentAttribute not specified.
     * @throws IntegrityException throw if $throwRestrictException is true when $onUpdateType is on restrict.
     */
    public function onUpdateChildren($event)
    {
        $sender = $event->sender;
        if (!is_string($sender->parentAttribute)) {
            return true;
        }
        switch ($sender->onUpdateType) {
            case static::$onRestrict:
                $event->isValid = $sender->getOldChildren() === null;
                if ($this->throwRestrictException) {
                    throw new IntegrityException('Update restricted.');
                }
                break;
            case static::$onCascade:
                $event->isValid = $sender->updateChildren();
                break;
            case static::$onSetNull:
                $event->isValid = $sender->updateChildren(null);
                break;
            case static::$onNoAction:
            default:
                $event->isValid = true;
                break;
        }
    }

    /**
     * Get parent query.
     * Or get parent instance if access by magic property.
     * @return ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(static::className(), [$this->refIdAttribute => $this->parentAttribute]);
    }

    /**
     * Set parent.
     * Don't forget save model after setting it.
     * @param static $parent
     * @return false|string
     */
    public function setParent($parent)
    {
        if (empty($parent) || $this->guid == $parent->guid || $parent->hasAncestor($this) || $parent->hasReachedAncestorLimit()) {
            return false;
        }
        unset($this->parent);
        unset($parent->children);
        $this->trigger(static::$eventParentChanged);
        $parent->trigger(static::$eventChildAdded);
        return $this->{$this->parentAttribute} = $parent->guid;
    }

    /**
     * Check whether this model has parent.
     * @return boolean
     */
    public function hasParent()
    {
        return $this->parent !== null;
    }

    /**
     * Check whether if $ancestor is the ancestor of myself.
     * Note, Itself will not be regarded as the its ancestor.
     * @param static $ancestor
     * @return boolean
     */
    public function hasAncestor($ancestor)
    {
        if (!$this->hasParent()) {
            return false;
        }
        if ($this->parent->guid == $ancestor->guid) {
            return true;
        }
        return $this->parent->hasAncestor($ancestor);
    }

    /**
     * Get ancestor chain. (Ancestor GUID Only!)
     * If this model has ancestor, the return array consists all the ancestor in order.
     * The first element is parent, and the last element is root, otherwise return empty array.
     * If you want to get ancestor model, you can simplify instance a query and specify the 
     * condition with the return value. But it will not return models under the order of ancestor chain.
     * @param string[] $ancestor
     * @return string[]
     */
    public function getAncestorChain($ancestor = [])
    {
        if (!is_string($this->parentAttribute)) {
            return [];
        }
        if (!$this->hasParent()) {
            return $ancestor;
        }
        $ancestor[] = $this->parent->guid;
        return $this->parent->getAncestorChain($ancestor);
    }

    /**
     * Get ancestors with specified ancestor chain.
     * @param string[] $ancestor Ancestor chain.
     * @return static[]|null
     */
    public static function getAncestorModels($ancestor)
    {
        if (empty($ancestor) || !is_array($ancestor)) {
            return null;
        }
        $models = [];
        foreach ($ancestor as $self) {
            $models[] = static::findOne($self);
        }
        return $models;
    }

    /**
     * Get ancestors.
     * @return static[]|null
     */
    public function getAncestors()
    {
        return is_string($this->parentAttribute) ? $this->getAncestorModels($this->getAncestorChain()) : null;
    }

    /**
     * Check whether if this model has common ancestor with $model.
     * @param static $model
     * @return boolean
     */
    public function hasCommonAncestor($model)
    {
        return is_string($this->parentAttribute) ? $this->getCommonAncestor($model) !== null : false;
    }

    /**
     * Get common ancestor. If there isn't common ancestor, null will be given.
     * @param static $model
     * @return static
     */
    public function getCommonAncestor($model)
    {
        if (!is_string($this->parentAttribute) || empty($model) || !$model->hasParent()) {
            return null;
        }
        $ancestor = $this->getAncestorChain();
        if (in_array($model->parent->guid, $ancestor)) {
            return $model->parent;
        }
        return $this->getCommonAncestor($model->parent);
    }

    /**
     * Get children query.
     * Or get children instances if access magic property.
     * @return ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(static::className(), [$this->parentAttribute => $this->refIdAttribute])->inverseOf('parent');
    }

    /**
     * Get children which parent attribute point to the my old guid.
     * @return static[]
     */
    public function getOldChildren()
    {
        return static::find()->where([$this->parentAttribute => $this->getOldAttribute($this->refIdAttribute)])->all();
    }

    /**
     * Update all children, not grandchildren.
     * If onUpdateType is on cascade, the children will be updated automatically.
     * @param mixed $value set guid if false, set empty string if empty() return
     * true, otherwise set it to $parentAttribute.
     * @return IntegrityException|boolean true if all update operations
     * succeeded to execute, or false if anyone of them failed. If not production
     * environment or enable debug mode, it will return exception.
     * @throws IntegrityException throw if anyone update failed.
     */
    public function updateChildren($value = false)
    {
        $children = $this->getOldChildren();
        if (empty($children)) {
            return true;
        }
        $parentAttribute = $this->parentAttribute;
        $transaction = $this->getDb()->beginTransaction();
        try {
            foreach ($children as $child) {
                if ($value === false) {
                    $refIdAttribute = $this->refIdAttribute;
                    $child->$parentAttribute = $this->$refIdAttribute;
                } elseif (empty($value)) {
                    $child->$parentAttribute = '';
                } else {
                    $child->$parentAttribute = $value;
                }
                if (!$child->save()) {
                    throw new IntegrityException('Update failed:' . $child->errors);
                }
            }
            $transaction->commit();
        } catch (IntegrityException $ex) {
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
     * Delete all children, not grandchildren.
     * If onDeleteType is on cascade, the children will be deleted automatically.
     * If onDeleteType is on restrict and contains children, the deletion will
     * be restricted.
     * @return IntegrityException|boolean true if all delete operations
     * succeeded to execute, or false if anyone of them failed. If not production
     * environment or enable debug mode, it will return exception.
     * @throws IntegrityException throw if anyone delete failed.
     */
    public function deleteChildren()
    {
        $children = $this->children;
        if (empty($children)) {
            return true;
        }
        $transaction = $this->getDb()->beginTransaction();
        try {
            foreach ($children as $child) {
                if (!$child->delete()) {
                    throw new IntegrityException('Delete failed:' . $child->errors);
                }
            }
            $transaction->commit();
        } catch (IntegrityException $ex) {
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
     * Update children's parent attribute.
     * Event triggered before updating.
     * @param ModelEvent $event
     * @return boolean
     */
    public function onParentRefIdChanged($event)
    {
        $sender = $event->sender;
        if ($sender->isAttributeChanged($sender->refIdAttribute)) {
            return $sender->onUpdateChildren($event);
        }
    }

    /**
     * Attach events associated with self blameable attribute.
     */
    protected function initSelfBlameableEvents()
    {
        $this->on(static::EVENT_BEFORE_UPDATE, [$this, 'onParentRefIdChanged']);
        $this->on(static::EVENT_BEFORE_DELETE, [$this, 'onDeleteChildren']);
    }
}

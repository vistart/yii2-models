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

use vistart\Models\models\BaseUserModel;
use vistart\Models\traits\MultipleBlameableTrait as mb;

/**
 * Relation features.
 * Note: Several methods associated with "inserting", "updating" and "removing" may
 * involve more DB operations, I strongly recommend those methods to be placed in
 * transaction execution, in order to ensure data consistency.
 * If you want to use group feature, the class used [[UserRelationGroupTrait]]
 * must be used coordinately.
 * @property array $groupGuids the guid array of all groups which owned by current relation.
 * @property-read array $allGroups
 * @property-read array $nonGroupMembers
 * @property-read integer $groupsCount
 * @property-read array $groupsRules
 * @property boolean $isFavorite
 * @property-read \vistart\Models\models\BaseUserRelationModel $opposite
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
trait UserRelationTrait
{
    use mb {
        mb::addBlame as addGroup;
        mb::createBlame as createGroup;
        mb::addOrCreateBlame as addOrCreateGroup;
        mb::removeBlame as removeGroup;
        mb::removeAllBlames as removeAllGroups;
        mb::getBlame as getGroup;
        mb::getOrCreateBlame as getOrCreateGroup;
        mb::getBlameds as getGroupMembers;
        mb::getBlameGuids as getGroupGuids;
        mb::setBlameGuids as setGroupGuids;
        mb::getAllBlames as getAllGroups;
        mb::getNonBlameds as getNonGroupMembers;
        mb::getBlamesCount as getGroupsCount;
        mb::getMultipleBlameableAttributeRules as getGroupsRules;
        mb::getEmptyBlamesJson as getEmptyGroupJson;
    }

    /**
     * @var string the another party of the relation.
     */
    public $otherGuidAttribute = 'other_guid';

    /**
     * @var string
     */
    public $remarkAttribute = 'remark';
    public static $relationSingle = 0;
    public static $relationMutual = 1;
    public $relationType = 1;
    public $relationTypes = [
        0 => 'Single',
        1 => 'Mutual',
    ];

    /**
     * @var string the attribute name of which determines the relation type.
     */
    public $mutualTypeAttribute = 'type';
    public static $mutualTypeNormal = 0x00;
    public static $mutualTypeSuspend = 0x01;

    /**
     * @var array Mutual types.
     */
    public static $mutualTypes = [
        0x00 => 'Normal',
        0x01 => 'Suspend',
    ];

    /**
     * @var string the attribute name of which determines the `favorite` field.
     */
    public $favoriteAttribute = 'favorite';

    /**
     * Get whether this relation is favorite or not.
     * @return boolean
     */
    public function getIsFavorite()
    {
        $favoriteAttribute = $this->favoriteAttribute;
        return is_string($favoriteAttribute) ? (int) $this->$favoriteAttribute > 0 : null;
    }

    /**
     * Set favorite.
     * @param boolean $fav
     */
    public function setIsFavorite($fav)
    {
        $favoriteAttribute = $this->favoriteAttribute;
        return is_string($favoriteAttribute) ? $this->$favoriteAttribute = ($fav ? 1 : 0) : null;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), $this->getUserRelationRules());
    }

    /**
     * Validation rules associated with user relation.
     * @return array rules.
     */
    public function getUserRelationRules()
    {
        $rules = [];
        if ($this->relationType == static::$relationMutual) {
            $rules = [
                [[$this->mutualTypeAttribute], 'in', 'range' => array_keys(static::$mutualTypes)],
                [[$this->mutualTypeAttribute], 'default', 'value' => static::$mutualTypeNormal],
            ];
        }
        return array_merge($rules, $this->getRemarkRules(), $this->getFavoriteRules(), $this->getGroupsRules(), $this->getOtherGuidRules());
    }

    /**
     * Get remark.
     * @return string remark.
     */
    public function getRemark()
    {
        $remarkAttribute = $this->remarkAttribute;
        return is_string($remarkAttribute) ? $this->$remarkAttribute : null;
    }

    /**
     * Set remark.
     * @param string $remark
     * @return string remark.
     */
    public function setRemark($remark)
    {
        $remarkAttribute = $this->remarkAttribute;
        return is_string($remarkAttribute) ? $this->$remarkAttribute = $remark : null;
    }

    /**
     * Validation rules associated with remark attribute.
     * @return array rules.
     */
    public function getRemarkRules()
    {
        return is_string($this->remarkAttribute) ? [
            [[$this->remarkAttribute], 'string'],
            [[$this->remarkAttribute], 'default', 'value' => ''],
            ] : [];
    }

    /**
     * Validation rules associated with favorites attribute.
     * @return array rules.
     */
    public function getFavoriteRules()
    {
        return is_string($this->favoriteAttribute) ? [
            [[$this->favoriteAttribute], 'boolean'],
            [[$this->favoriteAttribute], 'default', 'value' => 0],
            ] : [];
    }

    /**
     * Validation rules associated with other guid attribute.
     * @return array rules.
     */
    public function getOtherGuidRules()
    {
        $rules = [
            [[$this->otherGuidAttribute], 'required'],
            [[$this->otherGuidAttribute], 'string', 'max' => 36],
            [[$this->otherGuidAttribute, $this->createdByAttribute], 'unique', 'targetAttribute' => [$this->otherGuidAttribute, $this->createdByAttribute]],
        ];
        return $rules;
    }

    /**
     * Attach events associated with user relation.
     */
    public function initUserRelationEvents()
    {
        $this->on(static::EVENT_INIT, [$this, 'onInitBlamesLimit']);
        $this->on(static::$eventNewRecordCreated, [$this, 'onInitGroups']);
        $this->on(static::$eventNewRecordCreated, [$this, 'onInitRemark']);
        $this->on(static::$eventMultipleBlamesChanged, [$this, 'onBlamesChanged']);
        $this->on(static::EVENT_AFTER_INSERT, [$this, 'onInsertRelation']);
        $this->on(static::EVENT_AFTER_UPDATE, [$this, 'onUpdateRelation']);
        $this->on(static::EVENT_AFTER_DELETE, [$this, 'onDeleteRelation']);
    }

    /**
     * Get opposite relation to self.
     * @return \vistart\Models\models\BaseUserRelationModel
     */
    public function getOpposite()
    {
        if ($this->isNewRecord) {
            return null;
        }
        $createdByAttribute = $this->createdByAttribute;
        $otherGuidAttribute = $this->otherGuidAttribute;
        return static::find()->opposite($this->$createdByAttribute, $this->$otherGuidAttribute);
    }

    /**
     * Build a suspend mutual relation, not support single relation.
     * @param BaseUserModel|string $user Initiator or its GUID.
     * @param BaseUserModel|string $other Recipient or its GUID.
     * @return \vistart\Models\models\BaseUserRelationModel The relation will be
     * given if exists, or return a new relation.
     */
    public static function buildSuspendRelation($user, $other)
    {
        $relation = static::buildRelation($user, $other);
        $btAttribute = $relation->mutualTypeAttribute;
        $relation->$btAttribute = static::$mutualTypeSuspend;
        return $relation;
    }

    /**
     * Build a normal relation.
     * @param BaseUserModel|string $user Initiator or its GUID.
     * @param BaseUserModel|string $other Recipient or its GUID.
     * @return \vistart\Models\models\BaseUserRelationModel The relation will be
     * given if exists, or return a new relation.
     */
    public static function buildNormalRelation($user, $other)
    {
        $relation = static::buildRelation($user, $other);
        if ($relation->relationType == static::$relationMutual) {
            $btAttribute = $relation->mutualTypeAttribute;
            $relation->$btAttribute = static::$mutualTypeNormal;
        }
        return $relation;
    }

    /**
     * Build relation between initiator and recipient.
     * @param BaseUserModel|string $user Initiator or its GUID.
     * @param BaseUserModel|string $other Recipient or its GUID.
     * @return \vistart\Models\models\BaseUserRelationModel The relation will be
     * given if exists, or return a new relation.
     */
    protected static function buildRelation($user, $other)
    {
        $relation = static::find()->initiators($user)->recipients($other)->one();
        if (!$relation) {
            $rni = static::buildNoInitModel();
            $createdByAttribute = $rni->createdByAttribute;
            $otherGuidAttribute = $rni->otherGuidAttribute;
            $userClass = $rni->userClass;
            if ($user instanceof BaseUserModel) {
                $userClass = $userClass ? : $user->className();
                $user = $user->guid;
            }
            if ($other instanceof BaseUserModel) {
                $other = $other->guid;
            }
            $relation = new static([$createdByAttribute => $user, $otherGuidAttribute => $other, 'userClass' => $userClass]);
        }
        return $relation;
    }

    /**
     * Build opposite mutual relation throughout the current relation, not support
     * single relation. The opposite relation will be given if existed.
     * @param \vistart\Models\models\BaseUserRelationModel $relation
     * @return \vistart\Models\models\BaseUserRelationModel
     */
    protected static function buildOppositeRelation($relation)
    {
        if ($relation->relationType == static::$relationSingle) {
            return null;
        }
        $createdByAttribute = $relation->createdByAttribute;
        $otherGuidAttribute = $relation->otherGuidAttribute;
        $mutualTypeAttribute = $relation->mutualTypeAttribute;
        $opposite = static::buildRelation($relation->$otherGuidAttribute, $relation->$createdByAttribute);
        $opposite->$mutualTypeAttribute = $relation->$mutualTypeAttribute;
        return $opposite;
    }

    /**
     * Remove myself.
     * @return integer|false The number of relations removed, or false if the remove
     * is unsuccessful for some reason. Note that it is possible the number of relations
     * removed is 0, even though the remove execution is successful.
     */
    public function remove()
    {
        return $this->delete();
    }

    /**
     * Remove first relation between initiator(s) and recipient(s).
     * @param BaseUserModel|string|array $user Initiator or its guid, or array of them.
     * @param BaseUserModel|string|array $other Recipient or its guid, or array of them.
     * @return integer|false The number of relations removed.
     */
    public static function removeOneRelation($user, $other)
    {
        return static::find()->initiators($user)->recipients($other)->one()->delete();
    }

    /**
     * Remove all relations between initiator(s) and recipient(s).
     * @param BaseUserModel|string|array $user Initiator or its guid, or array of them.
     * @param BaseUserModel|string|array $other Recipient or its guid, or array of them.
     * @return integer The number of relations removed.
     */
    public static function removeAllRelations($user, $other)
    {
        $rni = static::buildNoInitModel();
        $createdByAttribute = $rni->createdByAttribute;
        $otherGuidAttribute = $rni->otherGuidAttribute;
        return static::deleteAll([$createdByAttribute => $user, $otherGuidAttribute => $other]);
    }

    /**
     * Get first relation between initiator(s) and recipient(s).
     * @param BaseUserModel|string|array $user Initiator or its guid, or array of them.
     * @param BaseUserModel|string|array $other Recipient or its guid, or array of them.
     * @return \vistart\Models\models\BaseUserRelationModel
     */
    public static function findOneRelation($user, $other)
    {
        return static::find()->initiators($user)->recipients($other)->one();
    }

    /**
     * Get first opposite relation between initiator(s) and recipient(s).
     * @param BaseUserModel|string $user Initiator or its guid, or array of them.
     * @param BaseUserModel|string $other Recipient or its guid, or array of them.
     * @return \vistart\Models\models\BaseUserRelationModel
     */
    public static function findOneOppositeRelation($user, $other)
    {
        return static::find()->initiators($other)->recipients($user)->one();
    }

    /**
     * Get user's or users' all relations, or by specified groups.
     * @param BaseUserModel|string|array $user Initiator or its GUID, or Initiators or their GUIDs.
     * @param BaseUserRelationGroupModel|string|array|null $groups UserRelationGroup or its guid, or array of them. If you do not want to delimit the groups, please assign null.
     * @return array all eligible relations
     */
    public static function findOnesAllRelations($user, $groups = null)
    {
        return static::find()->initiators($user)->groups($groups)->all();
    }

    /**
     * Initialize groups attribute.
     * @param \yii\base\Event $event
     */
    public function onInitGroups($event)
    {
        $sender = $event->sender;
        $sender->removeAllGroups();
    }

    /**
     * Initialize remark attribute.
     * @param \yii\base\Event $event
     */
    public function onInitRemark($event)
    {
        $sender = $event->sender;
        $remarkAttribute = $sender->remarkAttribute;
        is_string($remarkAttribute) ? $sender->$remarkAttribute = '' : null;
    }

    /**
     * The event triggered after insert new relation.
     * The opposite relation should be inserted without triggering events
     * simultaneously after new relation inserted,
     * @param \yii\base\Event $event
     */
    public function onInsertRelation($event)
    {
        $sender = $event->sender;
        if ($sender->relationType == static::$relationMutual) {
            $opposite = static::buildOppositeRelation($sender);
            $opposite->off(static::EVENT_AFTER_INSERT, [$opposite, 'onInsertRelation']);
            if (!$opposite->save()) {
                $opposite->recordWarnings();
            }
            $opposite->on(static::EVENT_AFTER_INSERT, [$opposite, 'onInsertRelation']);
        }
    }

    /**
     * The event triggered after update relation.
     * The opposite relation should be updated without triggering events
     * simultaneously after existed relation removed.
     * @param \yii\base\Event $event
     */
    public function onUpdateRelation($event)
    {
        $sender = $event->sender;
        if ($sender->relationType == static::$relationMutual) {
            $opposite = static::buildOppositeRelation($sender);
            $opposite->off(static::EVENT_AFTER_UPDATE, [$opposite, 'onUpdateRelation']);
            if (!$opposite->save()) {
                $opposite->recordWarnings();
            }
            $opposite->on(static::EVENT_AFTER_UPDATE, [$opposite, 'onUpdateRelation']);
        }
    }

    /**
     * The event triggered after delete relation.
     * The opposite relation should be deleted without triggering events
     * simultaneously after existed relation removed.
     * @param \yii\base\Event $event
     */
    public function onDeleteRelation($event)
    {
        $sender = $event->sender;
        if ($sender->relationType == static::$relationMutual) {
            $createdByAttribute = $sender->createdByAttribute;
            $otherGuidAttribute = $sender->otherGuidAttribute;
            $sender->off(static::EVENT_AFTER_DELETE, [$sender, 'onDeleteRelation']);
            static::removeAllRelations($sender->$otherGuidAttribute, $sender->$createdByAttribute);
            $sender->on(static::EVENT_AFTER_DELETE, [$sender, 'onDeleteRelation']);
        }
    }
}

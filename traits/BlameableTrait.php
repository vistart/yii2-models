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

use vistart\Models\queries\BaseUserQuery;
use yii\base\InvalidParamException;
use yii\base\ModelEvent;
use yii\base\NotSupportedException;
use yii\behaviors\BlameableBehavior;
use yii\caching\TagDependency;
use yii\data\Pagination;

/**
 * This trait is used for building blameable model. It contains following features：
 * 1.单列内容；多列内容待定；
 * 2.内容类型；具体类型应当自定义；
 * 3.内容规则；自动生成；
 * 4.归属用户 GUID；
 * 5.创建用户 GUID；
 * 6.上次更新用户 GUID；
 * 7.Confirmation features, provided by [[ConfirmationTrait]];
 * @property-read array $blameableAttributeRules Get all rules associated with
 * blameable.
 * @property array $blameableRules Get or set all the rules associated with
 * creator, updater, content and its ID, as well as all the inherited rules.
 * @property array $blameableBehaviors Get or set all the behaviors assoriated
 * with creator and updater, as well as all the inherited behaviors.
 * @property-read array $descriptionRules Get description property rules.
 * @property-read mixed $content Content.
 * @property-read boolean $contentCanBeEdited Whether this content could be edited.
 * @property-read array $contentRules Get content rules.
 * @property-read BaseUserModel $user
 * @property-read BaseUserModel $updater
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
trait BlameableTrait
{
    use ConfirmationTrait,
        SelfBlameableTrait;

    private $blameableLocalRules = [];
    private $blameableLocalBehaviors = [];

    /**
     * @var boolean|string|array Specify the attribute(s) name of content(s). If
     * there is only one content attribute, you can assign its name. Or there
     * is multiple attributes associated with contents, you can assign their
     * names in array. If you don't want to use this feature, please assign
     * false.
     * For example:
     * ```php
     * public $contentAttribute = 'comment'; // only one field named as 'comment'.
     * ```
     * or
     * ```php
     * public $contentAttribute = ['year', 'month', 'day']; // multiple fields.
     * ```
     * or
     * ```php
     * public $contentAttribute = false; // no need of this feature.
     * ```
     * If you don't need this feature, you should add rules corresponding with
     * `content` in `rules()` method of your user model by yourself.
     */
    public $contentAttribute = 'content';

    /**
     * @var array built-in validator name or validatation method name and
     * additional parameters.
     */
    public $contentAttributeRule = ['string', 'max' => 255];

    /**
     * @var boolean|string Specify the field which stores the type of content.
     */
    public $contentTypeAttribute = false;

    /**
     * @var boolean|array Specify the logic type of content, not data type. If
     * your content doesn't need this feature. please specify false. If the
     * $contentAttribute is specified to false, this attribute will be skipped.
     * ```php
     * public $contentTypes = [
     *     'public',
     *     'private',
     *     'friend',
     * ];
     * ```
     */
    public $contentTypes = false;

    /**
     * @var boolean|string This attribute speicfy the name of description
     * attribute. If this attribute is assigned to false, this feature will be
     * skipped.
     */
    public $descriptionAttribute = false;

    /**
     * @var string
     */
    public $initDescription = '';

    /**
     * @var string the attribute that will receive current user ID value. This
     * attribute must be assigned.
     */
    public $createdByAttribute = "user_guid";

    /**
     * @var string the attribute that will receive current user ID value.
     * Set this property to false if you do not want to record the updater ID.
     */
    public $updatedByAttribute = "user_guid";

    /**
     * @var boolean Add combinated unique rule if assigned to true.
     */
    public $idCreatorCombinatedUnique = true;

    /**
     * @var boolean|string The name of user class which own the current entity.
     * If this attribute is assigned to false, this feature will be skipped, and
     * when you use create() method of UserTrait, it will be assigned with
     * current user class.
     */
    public $userClass;
    public static $cacheKeyBlameableRules = 'blameable_rules';
    public static $cacheTagBlameableRules = 'tag_blameable_rules';
    public static $cacheKeyBlameableBehaviors = 'blameable_behaviors';
    public static $cacheTagBlameableBehaviors = 'tag_blameable_behaviors';

    /**
     * @inheritdoc
     * ------------
     * The classical rules is like following:
     * [
     *     ['guid', 'required'],
     *     ['guid', 'unique'],
     *     ['guid', 'string', 'max' => 36],
     * 
     *     ['id', 'required'],
     *     ['id', 'unique'],
     *     ['id', 'string', 'max' => 4],
     * 
     *     ['create_time', 'safe'],
     *     ['update_time', 'safe'],
     * 
     *     ['ip_type', 'in', 'range' => [4, 6]],
     *     ['ip_1', 'number', 'integerOnly' => true, 'min' => 0],
     *     ['ip_2', 'number', 'integerOnly' => true, 'min' => 0],
     *     ['ip_3', 'number', 'integerOnly' => true, 'min' => 0],
     *     ['ip_4', 'number', 'integerOnly' => true, 'min' => 0],
     * 
     * 
     * ]
     * @return array
     */
    public function rules()
    {
        return $this->getBlameableRules();
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return $this->getBlameableBehaviors();
    }

    /**
     * Get total of contents which owned by their owner.
     * @return integer
     */
    public function countOfOwner()
    {
        $createdByAttribute = $this->createdByAttribute;
        return static::find()->where([$createdByAttribute => $this->$createdByAttribute])->count();
    }

    /**
     * Get content.
     * @return mixed
     */
    public function getContent()
    {
        $contentAttribute = $this->contentAttribute;
        if ($contentAttribute === false) {
            return null;
        }
        if (is_array($contentAttribute)) {
            $content = [];
            foreach ($contentAttribute as $key => $value) {
                $content[$key] = $this->$value;
            }
            return $content;
        }
        return $this->$contentAttribute;
    }

    /**
     * Set content.
     * @param mixed $content
     */
    public function setContent($content)
    {
        $contentAttribute = $this->contentAttribute;
        if ($contentAttribute === false) {
            return;
        }
        if (is_array($contentAttribute)) {
            foreach ($contentAttribute as $key => $value) {
                $this->$value = $content[$key];
            }
            return;
        }
        $this->$contentAttribute = $content;
    }

    /**
     * Determines whether content could be edited. Your should implement this
     * method by yourself.
     * @return boolean
     * @throws NotSupportedException
     */
    public function getContentCanBeEdited()
    {
        if ($this->contentAttribute === false) {
            return false;
        }
        throw new NotSupportedException("This method is not implemented.");
    }

    /**
     * Check it has been ever edited.
     * @return boolean Whether this content has ever been edited.
     */
    public function hasEverEdited()
    {
        $createdAtAttribute = $this->createdByAttribute;
        $updatedAtAttribute = $this->updatedByAttribute;
        if (!$createdAtAttribute || !$updatedAtAttribute) {
            return false;
        }
        return $this->$createdAtAttribute === $this->$updatedAtAttribute;
    }

    /**
     * Get blameable rules cache key.
     * @return string cache key.
     */
    public function getBlameableRulesCacheKey()
    {
        return static::className() . $this->cachePrefix . static::$cacheKeyBlameableRules;
    }

    /**
     * Get blameable rules cache tag.
     * @return string cache tag
     */
    public function getBlameableRulesCacheTag()
    {
        return static::className() . $this->cachePrefix . static::$cacheTagBlameableRules;
    }

    /**
     * Get the rules associated with content to be blamed.
     * @return array rules.
     */
    public function getBlameableRules()
    {
        $cache = $this->getCache();
        if ($cache) {
            $this->blameableLocalRules = $cache->get($this->getBlameableRulesCacheKey());
        }
        // 若当前规则不为空，且是数组，则认为是规则数组，直接返回。
        if (!empty($this->blameableLocalRules) && is_array($this->blameableLocalRules)) {
            return $this->blameableLocalRules;
        }

        // 父类规则与确认规则合并。
        if ($cache) {
            TagDependency::invalidate($cache, [$this->getEntityRulesCacheTag()]);
        }
        $rules = array_merge(
            parent::rules(),
            $this->getConfirmationRules(),
            $this->getBlameableAttributeRules(),
            $this->getDescriptionRules(),
            $this->getContentRules(),
            $this->getSelfBlameableRules()
        );
        $this->setBlameableRules($rules);
        return $this->blameableLocalRules;
    }

    /**
     * Get the rules associated with `createdByAttribute`, `updatedByAttribute`
     * and `idAttribute`-`createdByAttribute` combination unique.
     * @return array rules.
     */
    public function getBlameableAttributeRules()
    {
        $rules = [];
        // 创建者和上次修改者由 BlameableBehavior 负责，因此标记为安全。
        if (!is_string($this->createdByAttribute) || empty($this->createdByAttribute)) {
            throw new NotSupportedException('You must assign the creator.');
        }
        $rules[] = [
            [$this->createdByAttribute],
            'safe',
        ];

        if (is_string($this->updatedByAttribute) && !empty($this->updatedByAttribute)) {
            $rules[] = [
                [$this->updatedByAttribute],
                'safe',
            ];
        }

        if ($this->idCreatorCombinatedUnique && is_string($this->idAttribute)) {
            $rules ['id'] = [
                [$this->idAttribute,
                    $this->createdByAttribute],
                'unique',
                'targetAttribute' => [$this->idAttribute,
                    $this->createdByAttribute],
            ];
        }
        return $rules;
    }

    /**
     * Get the rules associated with `description` attribute.
     * @return array rules.
     */
    public function getDescriptionRules()
    {
        $rules = [];
        if (is_string($this->descriptionAttribute) && !empty($this->descriptionAttribute)) {
            $rules[] = [
                [$this->descriptionAttribute],
                'string'
            ];
            $rules[] = [
                [$this->descriptionAttribute],
                'default',
                'value' => $this->initDescription,
            ];
        }
        return $rules;
    }

    /**
     * Get the rules associated with `content` and `contentType` attributes.
     * @return array rules.
     */
    public function getContentRules()
    {
        if (!$this->contentAttribute) {
            return [];
        }
        $rules = [];
        $rules[] = [$this->contentAttribute, 'required'];
        if ($this->contentAttributeRule) {
            if (is_string($this->contentAttributeRule)) {
                $this->contentAttributeRule = [$this->contentAttributeRule];
            }
            if (is_array($this->contentAttributeRule)) {
                $rules[] = array_merge([$this->contentAttribute], $this->contentAttributeRule);
            }
        }

        if (!$this->contentTypeAttribute) {
            return $rules;
        }

        if (is_array($this->contentTypes) && !empty($this->contentTypes)) {
            $rules[] = [[
                $this->contentTypeAttribute],
                'required'];
            $rules[] = [[
                $this->contentTypeAttribute],
                'in',
                'range' => array_keys($this->contentTypes)];
        }
        return $rules;
    }

    /**
     * Set blameable rules.
     * @param array $rules
     */
    protected function setBlameableRules($rules = [])
    {
        $this->blameableLocalRules = $rules;
        $cache = $this->getCache();
        if ($cache) {
            $tagDependency = new TagDependency(['tags' => [$this->getBlameableRulesCacheTag()]]);
            $cache->set($this->getBlameableRulesCacheKey(), $rules, 0, $tagDependency);
        }
    }

    /**
     * Get blameable behaviors cache key.
     * @return string cache key.
     */
    public function getBlameableBehaviorsCacheKey()
    {
        return static::className() . $this->cachePrefix . static::$cacheKeyBlameableBehaviors;
    }

    /**
     * Get blameable behaviors cache tag.
     * @return string cache tag.
     */
    public function getBlameableBehaviorsCacheTag()
    {
        return static::className() . $this->cachePrefix . static::$cacheTagBlameableBehaviors;
    }

    /**
     * Get blameable behaviors. If current behaviors array is empty, the init
     * array will be given.
     * @return array
     */
    public function getBlameableBehaviors()
    {
        $cache = $this->getCache();
        if ($cache) {
            $this->blameableLocalBehaviors = $cache->get($this->getBlameableBehaviorsCacheKey());
        }
        if (empty($this->blameableLocalBehaviors) || !is_array($this->blameableLocalBehaviors)) {
            if ($cache) {
                TagDependency::invalidate($cache, [$this->getEntityBehaviorsCacheTag()]);
            }
            $behaviors = parent::behaviors();
            $behaviors['blameable'] = [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => $this->createdByAttribute,
                'updatedByAttribute' => $this->updatedByAttribute,
                'value' => [$this,
                    'onGetCurrentUserGuid'],
            ];
            $this->setBlameableBehaviors($behaviors);
        }
        return $this->blameableLocalBehaviors;
    }

    /**
     * Set blameable behaviors.
     * @param array $behaviors
     */
    protected function setBlameableBehaviors($behaviors = [])
    {
        $this->blameableLocalBehaviors = $behaviors;
        $cache = $this->getCache();
        if ($cache) {
            $tagDependencyConfig = ['tags' => [$this->getBlameableBehaviorsCacheTag()]];
            $tagDependency = new TagDependency($tagDependencyConfig);
            $cache->set($this->getBlameableBehaviorsCacheKey(), $behaviors, 0, $tagDependency);
        }
    }

    /**
     * Set description.
     * @return string description.
     */
    public function getDescription()
    {
        $descAttribute = $this->descriptionAttribute;
        return is_string($descAttribute) ? $this->$descAttribute : null;
    }

    /**
     * Get description.
     * @param string $desc description.
     * @return string|null description if enabled, or null if disabled.
     */
    public function setDescription($desc)
    {
        $descAttribute = $this->descriptionAttribute;
        return is_string($descAttribute) ? $this->$descAttribute = $desc : null;
    }

    /**
     * Get blame who owned this blameable model.
     * NOTICE! This method will not check whether `$userClass` exists. You should
     * specify it in `init()` method.
     * @return BaseUserQuery user.
     */
    public function getUser()
    {
        $userClass = $this->userClass;
        $model = $userClass::buildNoInitModel();
        return $this->hasOne($userClass::className(), [$model->guidAttribute => $this->createdByAttribute]);
    }

    /**
     * Get updater who updated this blameable model recently.
     * NOTICE! This method will not check whether `$userClass` exists. You should
     * specify it in `init()` method.
     * @return BaseUserQuery user.
     */
    public function getUpdater()
    {
        if (!is_string($this->updatedByAttribute)) {
            return null;
        }
        $userClass = $this->userClass;
        $model = $userClass::buildNoInitModel();
        return $this->hasOne($userClass::className(), [$model->guidAttribute => $this->updatedByAttribute]);
    }

    /**
     * This event is triggered before the model update.
     * This method is ONLY used for being triggered by event. DO NOT call,
     * override or modify it directly, unless you know the consequences.
     * @param ModelEvent $event
     */
    public function onContentChanged($event)
    {
        $sender = $event->sender;
        $sender->resetConfirmation();
    }

    /**
     * Return the current user's GUID if current model doesn't specify the owner
     * yet, or return the owner's GUID if current model has been specified.
     * This method is ONLY used for being triggered by event. DO NOT call,
     * override or modify it directly, unless you know the consequences.
     * @param ModelEvent $event
     * @return string the GUID of current user or the owner.
     */
    public function onGetCurrentUserGuid($event)
    {
        $sender = $event->sender;
        if (isset($sender->attributes[$sender->createdByAttribute])) {
            return $sender->attributes[$sender->createdByAttribute];
        }
        $identity = \Yii::$app->user->identity;
        if ($identity) {
            $igAttribute = $identity->guidAttribute;
            return $identity->$igAttribute;
        }
    }

    /**
     * Initialize type of content. the first of element[index is 0] of
     * $contentTypes will be used.
     * @param ModelEvent $event
     */
    public function onInitContentType($event)
    {
        $sender = $event->sender;
        if (!isset($sender->contentTypeAttribute) || !is_string($sender->contentTypeAttribute)) {
            return;
        }
        $contentTypeAttribute = $sender->contentTypeAttribute;
        if (!isset($sender->$contentTypeAttribute) &&
            !empty($sender->contentTypes) &&
            is_array($sender->contentTypes)) {
            $sender->$contentTypeAttribute = $sender->contentTypes[0];
        }
    }

    /**
     * Initialize description property with $initDescription.
     * @param ModelEvent $event
     */
    public function onInitDescription($event)
    {
        $sender = $event->sender;
        if (!isset($sender->descriptionAttribute) || !is_string($sender->descriptionAttribute)) {
            return;
        }
        $descriptionAttribute = $sender->descriptionAttribute;
        if (empty($sender->$descriptionAttribute)) {
            $sender->$descriptionAttribute = $sender->initDescription;
        }
    }

    /**
     * Attach events associated with blameable model.
     */
    public function initBlameableEvents()
    {
        $this->on(static::$eventConfirmationChanged, [$this, "onConfirmationChanged"]);
        $this->on(static::$eventNewRecordCreated, [$this, "onInitConfirmation"]);
        $contentTypeAttribute = $this->contentTypeAttribute;
        if (!isset($this->$contentTypeAttribute)) {
            $this->on(static::$eventNewRecordCreated, [$this, "onInitContentType"]);
        }
        $descriptionAttribute = $this->descriptionAttribute;
        if (!isset($this->$descriptionAttribute)) {
            $this->on(static::$eventNewRecordCreated, [$this, 'onInitDescription']);
        }
        $this->on(static::EVENT_BEFORE_UPDATE, [$this, "onContentChanged"]);
        $this->initSelfBlameableEvents();
    }

    /**
     * @inheritdoc
     */
    public function enabledFields()
    {
        $fields = parent::enabledFields();
        if (is_string($this->createdByAttribute)) {
            $fields[] = $this->createdByAttribute;
        }
        if (is_string($this->updatedByAttribute)) {
            $fields[] = $this->updatedByAttribute;
        }
        if (is_string($this->contentAttribute)) {
            $fields[] = $this->contentAttribute;
        }
        if (is_array($this->contentAttribute)) {
            $fields = array_merge($fields, $this->contentAttribute);
        }
        if (is_string($this->descriptionAttribute)) {
            $fields[] = $this->descriptionAttribute;
        }
        if (is_string($this->confirmationAttribute)) {
            $fields[] = $this->confirmationAttribute;
        }
        if (is_string($this->parentAttribute)) {
            $fields[] = $this->parentAttribute;
        }
        return $fields;
    }

    /**
     * Find all follows by specified identity. If `$identity` is null, the logged-in
     * identity will be taken.
     * @param string|integer $pageSize If it is 'all`, then will find all follows,
     * the `$currentPage` parameter will be skipped. If it is integer, it will be
     * regarded as sum of models in one page.
     * @param integer $currentPage The current page number, begun with 0.
     * @param {$this->userClass} $identity
     * @return static[] If no follows, null will be given, or return follow array.
     */
    public static function findAllByIdentityInBatch($pageSize = 'all', $currentPage = 0, $identity = null)
    {
        if ($pageSize === 'all') {
            return static::findByIdentity($identity)->all();
        }
        return static::findByIdentity($identity)->page($pageSize, $currentPage)->all();
    }

    /**
     * Find one follow by specified identity. If `$identity` is null, the logged-in
     * identity will be taken. If $identity doesn't has the follower, null will
     * be given.
     * @param integer $id user id.
     * @param boolean $throwException
     * @param {$this->userClass} $identity
     * @return static
     * @throws InvalidParamException
     */
    public static function findOneById($id, $throwException = true, $identity = null)
    {
        $query = static::findByIdentity($identity);
        if (!empty($id)) {
            $query = $query->id($id);
        }
        $model = $query->one();
        if (!$model && $throwException) {
            throw new InvalidParamException('Model Not Found.');
        }
        return $model;
    }

    /**
     * Get total of follows of specified identity.
     * @param {$this->userClass} $identity
     * @return integer total.
     */
    public static function countByIdentity($identity = null)
    {
        return static::findByIdentity($identity)->count();
    }

    /**
     * Get pagination, used for building contents page by page.
     * @param integer $limit
     * @param {$this->userClass} $identity
     * @return Pagination
     */
    public static function getPagination($limit = 10, $identity = null)
    {
        $limit = (int) $limit;
        $count = static::countByIdentity($identity);
        if ($limit > $count) {
            $limit = $count;
        }
        return new Pagination(['totalCount' => $count, 'pageSize' => $limit]);
    }
}

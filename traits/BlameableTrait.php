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

use yii\behaviors\BlameableBehavior;

/**
 * 该 Trait 用于处理属于用户的实例。包括以下功能：
 * 1.单列内容；多列内容待定；
 * 2.内容类型；具体类型应当自定义；
 * 3.内容规则；自动生成；
 * 4.归属用户 GUID；
 * 5.创建用户 GUID；
 * 6.上次更新用户 GUID；
 * 7.确认功能由 ConfirmationTrait 提供；
 * 8.实例功能由 EntityTrait 提供。
 * @property array $blameableRules Get or set all the rules associated with
 * creator, updater, content and its ID, as well as all the inherited rules.
 * @property array $blameableBehaviors Get or set all the behaviors assoriated
 * with creator and updater, as well as all the inherited behaviors. 
 * @property-read mixed $content
 * @property-read boolean $contentCanBeEdited
 * @property-read mixed $creator;
 * @property-read mixed $updater;
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
trait BlameableTrait {

    use ConfirmationTrait;

    private $_blameableRules = [];
    private $_blameableBehaviors = [];

    /**
     * @var boolean|string|array Specify the attribute(s) name of content(s). If
     * there is only one content attribute, you can assign its name. Or there
     * is multiple attributes associated with contents, you can assign their
     * names in array. If you don't want to use this feature, please assign
     * false.
     * 
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
     * 
     * If you don't need this feature, you should add rules corresponding with
     * `content` in `rules()` method of your user model by yourself.
     */
    public $contentAttribute = 'content';

    /**
     * @var array built-in validator name or validatation method name and
     * additional parameters.
     */
    public $contentAttributeRule = null;

    /**
     * @var boolean|string Specify the field which stores the type of content.
     */
    public $contentTypeAttribute = false;
    
    /**
     * @var boolean|array Specify the logic type of content, not data type. If
     * your content doesn't need this feature. please specify false. If the
     * $contentAttribute is specified to false, this attribute will be skipped.
     * 
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
     * @var string the attribute that will receive current user ID value
     * Set this property to false if you do not want to record the creator ID.
     */
    public $createdByAttribute = "user_guid";

    /**
     * @var string the attribute that will receive current user ID value
     * Set this property to false if you do not want to record the updater ID.
     */
    public $updatedByAttribute = "user_guid";
    
    public $userClass;
    
    public function getCreator() {
        if (!$this->createdByAttribute) {
            return null;
        }
        return $userClass::findOne($this->createdByAttribute);
    }
    
    public function getUpdater() {
        if (!$this->updatedByAttribute) {
            return null;
        }
        return $userClass::findOne($this->updatedByAttribute);
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return $this->blameableRules;
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return $this->blameableBehaviors;
    }

    /**
     * 
     * @return mixed
     */
    public function getContent() {
        $contentAttribute = $this->contentAttribute;
        if ($contentAttribute === false)
            return null;
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
     * 
     * @param mixed $content
     */
    public function setContent($content) {
        $contentAttribute = $this->contentAttribute;
        if ($contentAttribute === false)
            return;
        if (is_array($contentAttribute)) {
            foreach ($contentAttribute as $key => $value) {
                $this->$value = $content[$key];
            }
        } else {
            $this->$contentAttribute = $content;
        }
    }

    /**
     * 
     * @return boolean
     * @throws \yii\base\NotSupportedException
     */
    public function getContentCanBeEdited() {
        if ($this->contentAttribute === false)
            return false;
        throw new \yii\base\NotSupportedException("This method is not implemented.");
    }

    /**
     * 
     * @return boolean Whether this content has ever been edited.
     */
    public function hasEverEdited() {
        $createdAtAttribute = $this->createdByAttribute;
        $updatedAtAttribute = $this->updatedByAttribute;
        if (!$createdAtAttribute || !$updatedAtAttribute) {
            return false;
        }
        return $this->$createdAtAttribute === $this->$updatedAtAttribute;
    }

    /**
     * 
     * @return array
     */
    public function getBlameableRules() {
        // 若当前规则不为空，且是数组，则认为是规则数组，直接返回。
        if (!empty($this->_blameableRules) && is_array($this->_blameableRules)) {
            return $this->_blameableRules;
        }
        
        // 当前规则
        $rules = [];
        
        // 创建者和上次修改者由 BlameableBehavior 负责，因此标记为安全。
        if (is_string($this->createdByAttribute) && !empty($this->createdByAttribute)) {
            $rules[] = [
                [$this->createdByAttribute], 'safe',
            ];
        }
        
        if (is_string($this->updatedByAttribute) && !empty($this->updatedByAttribute)) {
            $rules[] = [
                [$this->updatedByAttribute], 'safe',
            ];
        }
        
        // 先将父类规则与确认规则合并。
        $this->_blameableRules = array_merge(
                parent::rules(), $this->confirmationRules, $rules
        );
        
        // 若 contentAttribute 未设置，则直接返回，否则合并。
        if (!$this->contentAttribute) {
            return $this->_blameableRules;
        }
        $this->_blameableRules[] = [
            [$this->contentAttribute], 'required'
        ];
        if ($this->contentAttributeRule && is_array($this->contentAttributeRule)) {
            $this->_blameableRules[] = array_merge(
                    [$this->contentAttribute], $this->contentAttributeRule
            );
        }
        
        if (!$this->contentTypeAttribute) {
            return $this->_blameableRules;
        }
        
        if (!is_array($this->contentTypes || empty($this->contentTypes))) {
            $this->_blameableRules[] = [
                [$this->contentTypeAttribute], 'required'
            ];
            $this->_blameableRules[] = [
                [$this->contentTypeAttribute], 'in', 'range' => array_keys($this->contentTypes)
            ];
        }
        return $this->_blameableRules;
    }

    /**
     * 
     * @param array $rules
     */
    public function setBlameableRules($rules = []) {
        $this->_blameableRules = $rules;
    }

    /**
     * 
     * @return array
     */
    public function getBlameableBehaviors() {
        if (empty($this->_blameableBehaviors) || !is_array($this->_blameableBehaviors)) {
            $behaviors = parent::behaviors();
            $behaviors[] = [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => $this->createdByAttribute,
                'updatedByAttribute' => $this->updatedByAttribute,
                'value' => [$this, 'onGetCurrentUserGuid'],
            ];
            $this->_blameableBehaviors = $behaviors;
        }
        return $this->_blameableBehaviors;
    }

    /**
     * 
     * @param array $behaviors
     */
    public function setBlameableBehaviors($behaviors = []) {
        $this->_blameableBehaviors = $behaviors;
    }

    /**
     * This event is triggered before the model update.
     * This method is ONLY used for being triggered by event. DO NOT call,
     * override or modify it directly, unless you know the consequences.
     * @param \yii\base\Event $event
     */
    public function onContentChanged($event) {
        $sender = $event->sender;
        $sender->resetConfirmation();
    }

    /**
     * Return the current user's GUID if current model doesn't specify the owner
     * yet, or return the owner's GUID if current model has been specified.
     * This method is ONLY used for being triggered by event. DO NOT call,
     * override or modify it directly, unless you know the consequences.
     * @param \yii\base\Event $event
     * @return string the GUID of current user or the owner.
     */
    public function onGetCurrentUserGuid($event) {
        $identity = \Yii::$app->user->identity;
        if (!$identity) {
            return null;
        }
        $identityGuidAttribute = $identity->guidAttribute;
        return $identity->$identityGuidAttribute;
    }

}

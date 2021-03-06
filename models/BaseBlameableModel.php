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

namespace vistart\Models\models;

use vistart\Models\queries\BaseBlameableQuery;
use vistart\Models\traits\BlameableTrait;

/**
 * BaseBlameableModel automatically fills the specified attributes with
 * the current user's GUID.
 * For example:<br/>
 * ~~~php
 * * @property string $comment
 * class Comment extends BaseBlameableModel
 * {
 *     public $contentAttribute = 'commment';
 *     public $contentAttributeRule = ['string', 'max' => 140];
 *     public static function tableName()
 *     {
 *         return <table_name>;
 *     }
 *     public function rules()
 *     {
 *         $rules = <Your Rules>;
 *         return array_merge(parent::rules(), $rules);
 *     }
 *     public function behaviors()
 *     {
 *         $behaviors = <Your Behaviors>;
 *         return array_merge(parent::behaviors(), $behaviors);
 *     }
 *     public function attributeLabels()
 *     {
 *         return [
 *             ...
 *         ];
 *     }
 * }
 * ~~~
 * Well, when you're signed-in, you can create and save a new `Comment` instance:
 * ~~~php
 * $comment = new Comment();
 * $comment->comment = 'New Comment.';
 * $comment->save();
 * ~~~
 * or update an existing one:
 * ~~~php
 * $comment = Comment::findByIdentity()->one();
 * if ($comment)
 * {
 *     $comment->comment = 'Updated Comment.';
 *     $comment->save();
 * }
 * ~~~
 * @property array createdByAttributeRules the whole validation rules of
 * creator attribute only, except of combination rules.
 * @property array updatedByAttributeRules the whole validation rules of
 * creator attribute only, except of combination rules.
 * @since 1.1
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
abstract class BaseBlameableModel extends BaseEntityModel
{
    use BlameableTrait;

    /**
     * Initialize the blameable model.
     * If query class is not specified, [[BaseBlameableQuery]] will be taken.
     */
    public function init()
    {
        if (!is_string($this->queryClass)) {
            $this->queryClass = BaseBlameableQuery::className();
        }
        if ($this->skipInit) {
            return;
        }
        $this->initBlameableEvents();
        parent::init();
    }

    /**
     * Get the query class with specified identity.
     * @param BaseUserModel $identity
     * @return BaseBlameableQuery
     */
    public static function findByIdentity($identity = null)
    {
        return static::find()->byIdentity($identity);
    }
}

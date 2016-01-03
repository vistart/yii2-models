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

use vistart\Models\traits\UserTrait;

/**
 * The abstract BaseUserModel is used for user identity class.
 * 
 * For example, you should create a table for user model before you want to
 * define a User class used for representing a user. Then, you can use base
 * user model generator to generate a new user model, like following:
 * ~~~php
 * * @property string $guid
 * class User extends \vistart\Models\models\BaseUserModel {
 *     public static function tableName() {
 *         return <table_name>;
 *     }
 * 
 *     public static function attributeLabels() {
 *         return [
 *             <All labels.>
 *         ];
 *     }
 * }
 * ~~~
 *
 * Well, if you want to register a new user, you should create a new user
 * instance, and prepare attributes for it. then call the `register()` method.
 * like following:
 * ~~~php
 * $user = new User(['password' => '123456']);
 * $user->register();
 * ~~~
 * 
 * If there is not only one user instance to be stored in database, but also
 * other associated models, such as Profile class, should be stored
 * synchronously, you can prepare their models and give them to parameter of
 * `register()` method, like following:
 * ~~~php
 * $profile = new Profile();
 * $user->register([$profile]);
 * ~~~
 * Note: you should supplement `get<ModelName>()` method(s) by yourself, or by
 * generator.
 * 
 * @see vistart\Models\models\BaseEntityModel
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
abstract class BaseUserModel extends BaseEntityModel implements \yii\web\IdentityInterface {

    use UserTrait;

    /**
     * Initialize user model.
     * This procedure will append events used for initialization of `status` and
     * `source` attributes.
     * When `$skipInit` is assigned to `false`, the above processes will be skipped.
     * If you want to modify or override this method, you should add `parent::init()`
     * statement at the end of your init() method.
     */
    public function init() {
        if ($this->skipInit)
            return;
        $this->on(self::$EVENT_NEW_RECORD_CREATED, [$this, 'onInitStatusAttribute']);
        $this->on(self::$EVENT_NEW_RECORD_CREATED, [$this, 'onInitSourceAttribute']);
        parent::init();
    }

}

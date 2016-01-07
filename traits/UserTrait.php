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
 * Assemble PasswordTrait, RegistrationTrait and IdentityTrait into UserTrait.
 *
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
trait UserTrait {

    use PasswordTrait,
        RegistrationTrait,
        IdentityTrait;

    /**
     * 
     * @param string $className Full qualified class name.
     * @param array $config name-value pairs that will be used to initialize
     * the object properties.
     */
    public function createNewModel($className, $config = []) {
        $guidAttribute = $this->guidAttribute;
        if (!isset($config[$guidAttribute])) {
            $config[$guidAttribute] = $this->$guidAttribute;
        }
        return new $className($config);
    }

    public function rules() {
        return array_merge(parent::rules(), $this->passwordHashRules, $this->passwordResetTokenRules, $this->sourceRules, $this->statusRules, $this->authKeyRules, $this->accessTokenRules);
    }

}
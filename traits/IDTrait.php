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

use vistart\helpers\Number;
use Yii;
use yii\base\ModelEvent;

/**
 * Entity features concerning ID.
 * @property-read array $idRules
 * @property mixed $id
 * @version 2.0
 * @author vistart <i@vistart.name>
 */
trait IDTrait
{

    /**
     * @var string OPTIONAL.The attribute that will receive the IDentifier No.
     * You can set this property to false if you don't use this feature.
     * @since 1.1
     */
    public $idAttribute = 'id';
    public static $idTypeString = 0;
    public static $idTypeInteger = 1;
    public static $idTypeAutoIncrement = 2;

    /**
     * @var integer type of id attribute.
     * @since 2.0
     */
    public $idAttributeType = 0;

    /**
     * @var boolean Determines whether its id has been pre-assigned. It will not
     * generate or assign ID if true.
     */
    public $idPreassigned = false;

    /**
     * @var string The prefix of ID. When ID type is Auto Increment, this feature
     * is skipped.
     * @since 2.0
     */
    public $idAttributePrefix = '';

    /**
     * @var integer OPTIONAL. The length of id attribute value, and max length
     * of this attribute in rules. If you set $idAttribute to false or ID type
     * to Auto Increment, this property will be ignored.
     * @since 1.1
     */
    public $idAttributeLength = 4;

    /**
     * @var boolean Determine whether the ID is safe for validation.
     * @since 1.1
     */
    protected $idAttributeSafe = false;

    /**
     * Get id.
     * @return string|integer
     */
    public function getId()
    {
        $idAttribute = $this->idAttribute;
        return is_string($idAttribute) ? $this->$idAttribute : null;
    }

    /**
     * Set id.
     * @param string|integer $identity
     * @return string|integer
     */
    public function setId($identity)
    {
        $idAttribute = $this->idAttribute;
        return is_string($idAttribute) ? $this->$idAttribute = $identity : null;
    }

    /**
     * Attach `onInitGuidAttribute` event.
     * @param string $eventName
     */
    protected function attachInitIdEvent($eventName)
    {
        $this->on($eventName, [$this, 'onInitIdAttribute']);
    }

    /**
     * Initialize the ID attribute with new generated ID.
     * If the model's id is pre-assigned, then it will return directly.
     * If the model's id is auto-increment, the id attribute will be marked safe.
     * This method is ONLY used for being triggered by event. DO NOT call,
     * override or modify it directly, unless you know the consequences.
     * @param ModelEvent $event
     * @since 1.1
     */
    public function onInitIdAttribute($event)
    {
        $sender = $event->sender;
        if ($sender->idPreassigned) {
            return;
        }
        if ($sender->idAttributeType === self::$idTypeAutoIncrement) {
            $sender->idAttributeSafe = true;
            return;
        }
        if (is_string($sender->idAttribute) &&
            is_int($sender->idAttributeLength) &&
            $sender->idAttributeLength > 0) {
            $idAttribute = $sender->idAttribute;
            $sender->$idAttribute = $sender->generateId();
        }
    }

    /**
     * Generate the ID. You can override this method to implement your own
     * generation algorithm.
     * @return string the generated ID.
     */
    public function generateId()
    {
        if ($this->idAttributeType == self::$idTypeInteger) {
            do {
                $result = Number::randomNumber($this->idAttributePrefix, $this->idAttributeLength);
            } while ($this->checkIdExists((int) $result));
            return $result;
        }
        if ($this->idAttributeType == self::$idTypeString) {
            return $this->idAttributePrefix .
                Yii::$app->security->generateRandomString($this->idAttributeLength - strlen($this->idAttributePrefix));
        }
        if ($this->idAttributeType == self::$idTypeAutoIncrement) {
            return null;
        }
        return false;
    }

    /**
     * Check if $identity existed.
     * @param mixed $identity
     * @return boolean
     */
    public function checkIdExists($identity)
    {
        if ($identity == null) {
            return false;
        }
        return (static::findOne([$this->idAttribute => $identity]) !== null);
    }

    /**
     * Get the rules associated with id attribute.
     * @return array
     */
    public function getIdRules()
    {
        if ($this->idAttribute == false) {
            return [];
        }
        if ($this->idAttributeSafe) {
            return [
                [[$this->idAttribute], 'safe'],
            ];
        }
        if (is_string($this->idAttribute) &&
            is_int($this->idAttributeLength) &&
            $this->idAttributeLength > 0) {
            $rules = [
                [[$this->idAttribute], 'required'],
                'id' => [[$this->idAttribute], 'unique'],
            ];
            if ($this->idAttributeType === self::$idTypeInteger) {
                $rules[] = [
                    [$this->idAttribute], 'number', 'integerOnly' => true
                ];
            }
            if ($this->idAttributeType === self::$idTypeString) {
                $rules[] = [[$this->idAttribute], 'string',
                    'max' => $this->idAttributeLength,];
            }
            if ($this->idAttributeType === self::$idTypeAutoIncrement) {
                $rules[] = [
                    [$this->idAttribute], 'safe',
                ];
            }
            return $rules;
        }
        return [];
    }
}

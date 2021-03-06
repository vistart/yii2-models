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

namespace vistart\Models\tests;

use vistart\Models\tests\data\ar\User;
use vistart\Models\tests\data\ar\UserComment;
use vistart\helpers\Ip;
use Yii;

/**
 * Description of BaseUserModelTest
 * 
 * @author vistart <i@vistart.name>
 * @since 2.0
 */
class BaseUserModelTest extends TestCase
{

    /**
     * @group user
     */
    public function testInit()
    {
        $users = [];
        for ($i = 0; $i < 100; $i++) {
            $users[] = new User();
        }
        foreach ($users as $key => $user) {
            if ($user->register() !== true) {
                unset($users[$key]);
            }
        }
        $count = count($users);
        //echo ("$count( / 100) users has been registered successfully.\n");
        foreach ($users as $user) {
            if ($user->deregister() !== true) {
                $this->assertTrue(false);
            }
        }
    }

    /**
     * @group user
     * @depends testInit
     */
    public function testNewUser()
    {
        $user = new User();
        $this->assertNotNull($user);
        $this->assertTrue($user->register());
        $statusAttribute = $user->statusAttribute;
        $this->assertEquals(1, $user->$statusAttribute);
        $password = '123456';
        $user->password = $password;
        $passwordHashAttribute = $user->passwordHashAttribute;
        $this->assertEquals(true, $this->validatePassword($password, $user->$passwordHashAttribute));
        $this->assertEquals(false, $this->validatePassword('1234567', $user->$passwordHashAttribute));
        $this->assertTrue($user->deregister());
    }

    /**
     * @group user
     * @depends testNewUser
     */
    public function testGUID()
    {
        $user = new User();
        $this->assertNotEmpty($user->guid);

        $user = new User();
        $guidAttribute = $user->guidAttribute;
        $this->assertEquals($user->guid, $user->$guidAttribute);
    }

    /**
     * @group user
     * @depends testGUID
     */
    public function testID()
    {
        $user = new User();
        $this->assertTrue($user->register());
        $this->assertNotEmpty($user->id);
        $idAttribute = $user->idAttribute;
        $this->assertEquals($user->id, $user->$idAttribute);
        $this->assertTrue($user->deregister());

        $user = new User(['idPreassigned' => true, 'id' => 123456]);
        $this->assertTrue($user->register());
        $this->assertEquals(123456, $user->id);

        $user = User::find()->id(123456, 'like')->one();
        $this->assertTrue($user->deregister());

        $user = new User(['idPreassigned' => true, 'id' => 'abcdefg']);
        $this->assertNotNull($user->register());
        $this->assertNotEmpty($user->errors);

        $user = new User(['id' => 123456]);
        $this->assertTrue($user->register());
        $this->assertNotEquals(123456, $user->id);
        $this->assertTrue($user->deregister());
    }

    /**
     * @group user
     * @depends testID
     */
    public function testIP()
    {
        $ipAddress = '::1';
        $user = new User(['enableIP' => User::$ipAll, 'ipAddress' => $ipAddress]);
        $this->assertTrue($user->register());
        $this->assertEquals(User::$ipAll, $user->enableIP);
        $this->assertEquals($ipAddress, $user->ipAddress);
        $ipTypeAttribute = $user->ipTypeAttribute;
        $this->assertEquals(Ip::IPv6, $user->$ipTypeAttribute);
        $this->assertTrue($user->deregister());

        $user = new User(['enableIP' => User::$ipv4, 'ipAddress' => $ipAddress]);
        $this->assertTrue($user->register());
        $this->assertEquals(User::$ipv4, $user->enableIP);
        $this->assertEquals(0, $user->ipAddress);
        $this->assertTrue($user->deregister());

        $user = new User(['enableIP' => User::$ipv6, 'ipAddress' => $ipAddress]);
        $this->assertTrue($user->register());
        $this->assertEquals(User::$ipv6, $user->enableIP);
        $this->assertEquals($ipAddress, $user->ipAddress);
        $this->assertTrue($user->deregister());

        $ipAddress = '127.0.0.1';
        $user = new User(['enableIP' => User::$ipAll, 'ipAddress' => $ipAddress]);
        $this->assertTrue($user->register());
        $this->assertEquals(User::$ipAll, $user->enableIP);
        $this->assertEquals($ipAddress, $user->ipAddress);
        $ipTypeAttribute = $user->ipTypeAttribute;
        $this->assertEquals(Ip::IPv4, $user->$ipTypeAttribute);
        $this->assertTrue($user->deregister());

        $user = new User(['enableIP' => User::$ipv4, 'ipAddress' => $ipAddress]);
        $this->assertTrue($user->register());
        $this->assertEquals(User::$ipv4, $user->enableIP);
        $this->assertEquals($ipAddress, $user->ipAddress);
        $this->assertTrue($user->deregister());

        $user = new User(['enableIP' => User::$ipv6, 'ipAddress' => $ipAddress]);
        $this->assertTrue($user->register());
        $this->assertEquals(User::$ipv6, $user->enableIP);
        $this->assertEquals(0, $user->ipAddress);
        $this->assertTrue($user->deregister());
    }

    /**
     * @group user
     * @depends testIP
     */
    public function testPassword()
    {
        $password = '123456';
        $user = new User();
        $this->assertTrue($user->hasEventHandlers(User::$eventAfterSetPassword));
        $this->assertEquals(false, $user->hasEventHandlers(User::$eventBeforeValidatePassword));

        $user->on(User::$eventAfterSetPassword, function($event) {
            $this->assertTrue(true, 'EVENT_AFTER_SET_PASSWORD');
            $sender = $event->sender;
            $this->assertInstanceOf(User::className(), $sender);
        });
        $this->assertEquals(true, $user->hasEventHandlers(User::$eventAfterSetPassword));

        $user->on(User::$eventBeforeValidatePassword, function($event) {
            $this->assertTrue(true, 'EVENT_BEFORE_VALIDATE_PASSWORD');
            $sender = $event->sender;
            $this->assertInstanceOf(User::className(), $sender);
        });
        $this->assertEquals(true, $user->hasEventHandlers(User::$eventBeforeValidatePassword));

        $user->password = $password;
        $passwordHashAttribute = $user->passwordHashAttribute;
        $this->assertTrue($this->validatePassword($password, $user->$passwordHashAttribute));
        $this->assertFalse($this->validatePassword($password . ' ', $user->$passwordHashAttribute));
    }

    public function onResetPasswordFailed($event)
    {
        $sender = $event->sender;
        var_dump($sender->errors);
        $this->assertFalse(true);
    }

    private function validatePassword($password, $hash)
    {
        return Yii::$app->security->validatePassword($password, $hash);
    }

    /**
     * @group user
     * @depends testPassword
     */
    public function testPasswordResetToken()
    {
        $password = '123456';
        $user = new User(['password' => $password]);
        $user->on(User::$eventResetPasswordFailed, [$this, 'onResetPasswordFailed']);
        $user->register();
        $this->assertTrue($user->applyForNewPassword());
        $password = $password . ' ';
        $passwordResetTokenAttribute = $user->passwordResetTokenAttribute;
        $user->resetPassword($password, $user->$passwordResetTokenAttribute);
        $user->deregister();
    }

    /**
     * @group user
     * @depends testPasswordResetToken
     */
    public function testStatus()
    {
        $user = new User();
        $guidAttribute = $user->guidAttribute;
        $guid = $user->guid;
        $this->assertTrue($user->register());
        $user = User::findOne($guid);
        $statusAttribute = $user->statusAttribute;
        $this->assertEquals(User::$statusActive, $user->$statusAttribute);

        $user = User::find()->where([$guidAttribute => $guid])->active(User::$statusInactive)->one();
        $this->assertNull($user);

        $user = User::find()->where([$guidAttribute => $guid])->active(User::$statusActive)->one();
        $this->assertInstanceOf(User::className(), $user);
        $this->assertTrue($user->deregister());
    }

    /**
     * @group user
     * @depends testStatus
     */
    public function testSource()
    {
        $user = new User();
        $guid = $user->guid;
        $guidAttribute = $user->guidAttribute;
        $this->assertTrue($user->register());
        $user = User::findOne($guid);
        $sourceAttribute = $user->sourceAttribute;
        $this->assertEquals(User::$sourceSelf, $user->$sourceAttribute);

        $user = User::find()->where([$guidAttribute => $guid])->source('1')->one();
        $this->assertNull($user);

        $user = User::find()->where([$guidAttribute => $guid])->source()->one();
        $this->assertInstanceOf(User::className(), $user);
        $this->assertTrue($user->deregister());
    }

    /**
     * @group user
     * @depends testSource
     */
    public function testTimestamp()
    {
        $user = new User();
        $createdAtAttribute = $user->createdAtAttribute;
        $updatedAtAttribute = $user->updatedAtAttribute;
        $this->assertNull($user->$createdAtAttribute);
        $this->assertNull($user->$updatedAtAttribute);
        $result = $user->register();
        if ($result instanceof \yii\db\Exception) {
            var_dump($result->getMessage());
            $this->assertFalse(false);
        } else {
            $this->assertTrue($result);
        }

        $this->assertNotNull($user->$createdAtAttribute);
        $this->assertNotNull($user->$updatedAtAttribute);
        $this->assertTrue($user->deregister());
    }

    public $beforeRegisterEvent = '';
    public $afterRegisterEvent = '';
    public $beforeDeregisterEvent = '';
    public $afterDeregisterEvent = '';

    /**
     * @group user
     * @depends testTimestamp
     */
    public function testRegister()
    {
        $user = new User();
        $user->on(User::$eventBeforeRegister, [$this, 'onBeforeRegister']);
        $user->on(User::$eventAfterRegister, [$this, 'onAfterRegister']);
        $this->assertTrue($user->register());
        $this->assertEquals('beforeRegister', $this->beforeRegisterEvent);
        $this->assertEquals('afterRegister', $this->afterRegisterEvent);
        $authKeyAttribute = $user->authKeyAttribute;
        $this->assertEquals(40, strlen($user->$authKeyAttribute));
        $accessTokenAttribute = $user->accessTokenAttribute;
        $this->assertEquals(40, strlen($user->$accessTokenAttribute));
        $sourceAttribute = $user->sourceAttribute;
        $this->assertEquals(User::$sourceSelf, $user->$sourceAttribute);
        $statusAttribute = $user->statusAttribute;
        $this->assertEquals(User::$statusActive, $user->$statusAttribute);
        $user->on(User::$eventBeforeDeregister, [$this, 'onBeforeDeregister']);
        $user->on(User::$eventAfterDeregister, [$this, 'onAfterDeregister']);
        $this->assertTrue($user->deregister());
        $this->assertEquals('beforeDeregister', $this->beforeDeregisterEvent);
        $this->assertEquals('afterDeregister', $this->afterDeregisterEvent);
    }

    public function onBeforeRegister($event)
    {
        $sender = $event->sender;
        $this->assertInstanceOf(User::className(), $sender);
        $this->beforeRegisterEvent = 'beforeRegister';
    }

    public function onAfterRegister($event)
    {
        $sender = $event->sender;
        $this->assertInstanceOf(User::className(), $sender);
        $this->afterRegisterEvent = 'afterRegister';
    }

    public function onBeforeDeregister($event)
    {
        $sender = $event->sender;
        $this->assertInstanceOf(User::className(), $sender);
        $this->beforeDeregisterEvent = 'beforeDeregister';
    }

    public function onAfterDeregister($event)
    {
        $sender = $event->sender;
        $this->assertInstanceOf(User::className(), $sender);
        $this->afterDeregisterEvent = 'afterDeregister';
    }

    /**
     * @group user
     * @depends testRegister
     * @large
     */
    public function atestNewUser256()
    {
        $users = [];
        for ($i = 0; $i < 256; $i++) {
            $password = '123456';
            $user = new User(['password' => $password]);
            $users[] = $user;
            if (!$user->register()) {
                $this->fail(($i + 1) . "\n" . $user->errors);
            }
        }
        foreach ($users as $key => $user) {
            if (!$user->deregister()) {
                $this->fail($key . "\n" . $user->errors);
            }
        }
        echo "$i\n";
    }

    /**
     * @group user
     * @depends testRegister
     */
    public function testCreateNonObject()
    {
        $user = new User();
        $this->assertNull($user->createProfile());
    }

    /**
     * @group user
     * @depends testCreateNonObject
     */
    public function testCreateCommentWithoutMap()
    {
        $user = new User(['password' => '123456']);
        $this->assertTrue($user->register());

        $comment = $user->createUserComment();
        $this->assertNull($comment);
        $this->assertTrue($user->deregister());
    }

    /**
     * @group user
     * @depends testCreateCommentWithoutMap
     */
    public function testCreateCommentWithMap()
    {
        $user = new User(['password' => '123456']);
        $this->assertTrue($user->register());

        $user->subsidiaryMap = [
            'Comment' => UserComment::className(),
        ];
        $comment = $user->createComment(['class' => UserComment::className()]);
        $this->assertInstanceOf(UserComment::className(), $comment);

        $comment = $user->createSubsidiary(UserComment::className(), ['class' => UserComment::className()]);
        $this->assertTrue($user->deregister());
    }

    /**
     * @group user
     * @depends testCreateCommentWithMap
     */
    public function testNormalizeSubsidiaryClass()
    {
        $user = new User(['password' => '123456']);
        $this->assertNull($user->normalizeSubsidiaryClass(null));
    }
}

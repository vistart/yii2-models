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

/**
 * Description of BaseUserCommentTest
 *
 * @author vistart <i@vistart.name>
 */
class BaseUserCommentTest extends TestCase
{

    /**
     * 
     * @return User
     */
    private function prepareUser()
    {
        $user = new User(['password' => '123456']);
        $this->assertTrue($user->register());
        return $user;
    }

    /**
     * 
     * @param User $user
     * @return UserComment
     */
    private function prepareComment($user)
    {
        $comment = $user->create(UserComment::className(), ['content' => 'comment']);
        return $comment;
    }

    /**
     * 
     * @param UserComment $comment
     * @return UserComment
     */
    private function prepareSubComment($comment)
    {
        $sub = $comment->bear();
        $createdByAttribute = $sub->createdByAttribute;
        $sub->$createdByAttribute = $comment->$createdByAttribute;
        $sub->content = 'sub';
        return $sub;
    }

    public function testNew()
    {
        $user = $this->prepareUser();
        $comment = $this->prepareComment($user);
        $subComment = $this->prepareSubComment($comment);
        if ($result = $comment->save()) {
            $this->assertTrue($result);
        } else {
            var_dump($comment->errors);
            $this->fail();
        }
        if ($result = $subComment->save()) {
            $this->assertTrue($result);
        } else {
            var_dump($subComment->errors);
            $this->fail();
        }
        $this->assertEquals(1, count($comment->getChildren()));
        $this->assertTrue($user->deregister());
    }

    /**
     * @depends testNew
     */
    public function testDeleteParentCascade()
    {
        $user = $this->prepareUser();
        $comment = $this->prepareComment($user);
        $subComment = $this->prepareSubComment($comment);
        $comment->save();
        $subComment->save();
        if ($comment->delete()) {
            $query = UserComment::find()->id($subComment->id);
            $copy = clone $query;
            var_dump($copy->createCommand()->getRawSql());
            $sub = UserComment::find()->id($subComment->id)->one();
            $this->assertNull($sub);
        } else {
            var_dump($comment->errors);
            $this->fail();
        }
        $this->assertTrue($user->deregister());
    }

    /**
     * @depends testDeleteParentCascade
     */
    public function testDeleteParentRestrict()
    {
        $user = $this->prepareUser();
        $comment = $this->prepareComment($user);
        $comment->onDeleteType = UserComment::$onRestrict;
        $comment->throwRestrictException = true;
        $subComment = $this->prepareSubComment($comment);
        $subComment->onDeleteType = UserComment::$onRestrict;
        $subComment->throwRestrictException = true;
        $comment->save();
        $subComment->save();
        try {
            $result = $comment->delete();
            $this->fail();
        } catch (\yii\db\IntegrityException $ex) {
            $this->assertEquals('Delete restrict.', $ex->getMessage());
        }
        $sub = UserComment::find()->id($subComment->id)->one();
        $this->assertInstanceOf(UserComment::className(), $sub);
        $this->assertTrue($user->deregister());

        $user = $this->prepareUser();
        $comment = $this->prepareComment($user);
        $comment->onDeleteType = UserComment::$onRestrict;
        $subComment = $this->prepareSubComment($comment);
        $subComment->onDeleteType = UserComment::$onRestrict;
        $comment->save();
        $subComment->save();
        if ($comment->delete()) {
            $this->fail();
        }
        $sub = UserComment::find()->id($subComment->id)->one();
        $this->assertInstanceOf(UserComment::className(), $sub);
        $this->assertTrue($user->deregister());
    }

    /**
     * @depends testDeleteParentRestrict
     */
    public function testDeleteParentNoAction()
    {
        $user = $this->prepareUser();
        $comment = $this->prepareComment($user);
        $comment->onDeleteType = UserComment::$onNoAction;
        $subComment = $this->prepareSubComment($comment);
        $subComment->onDeleteType = UserComment::$onNoAction;
        $comment->save();
        $subComment->save();
        if ($comment->delete()) {
            $this->assertTrue(true);
        } else {
            var_dump($comment->errors);
            $this->fail();
        }
        $sub = UserComment::find()->id($subComment->id)->one();
        $this->assertInstanceOf(UserComment::className(), $sub);
        $parentAttribute = $comment->parentAttribute;
        $this->assertEquals($subComment->$parentAttribute, $sub->$parentAttribute);
        $this->assertTrue($user->deregister());
    }

    /**
     * @depends testDeleteParentNoAction
     */
    public function testDeleteParentSetNull()
    {
        $user = $this->prepareUser();
        $comment = $this->prepareComment($user);
        $comment->onDeleteType = UserComment::$onSetNull;
        $subComment = $this->prepareSubComment($comment);
        $subComment->onDeleteType = UserComment::$onSetNull;
        $comment->save();
        $subComment->save();
        if ($comment->delete()) {
            $this->assertTrue(true);
        } else {
            var_dump($comment->errors);
            $this->fail();
        }
        $sub = UserComment::find()->id($subComment->id)->one();
        $this->assertInstanceOf(UserComment::className(), $sub);
        $parentAttribute = $comment->parentAttribute;
        $this->assertEquals('', $sub->$parentAttribute);
        $this->assertTrue($user->deregister());
    }

    /**
     * @depends testDeleteParentSetNull
     */
    public function testUpdateParentCascade()
    {
        $user = $this->prepareUser();
        $comment = $this->prepareComment($user);
        $subComment = $this->prepareSubComment($comment);
        $comment->save();
        $subComment->save();

        $comment->guid = UserComment::GenerateGuid();
        $this->assertTrue($comment->save());
        $sub = UserComment::find()->id($subComment->id)->one();
        $this->assertInstanceOf(UserComment::className(), $sub);
        $parentAttribute = $comment->parentAttribute;
        $this->assertEquals($comment->guid, $sub->$parentAttribute);
        $this->assertTrue($user->deregister());
    }

    /**
     * @depends testUpdateParentCascade
     */
    public function testUpdateParentRestrict()
    {
        $user = $this->prepareUser();
        $comment = $this->prepareComment($user);
        $comment->onUpdateType = UserComment::$onRestrict;
        $comment->throwRestrictException = true;
        $subComment = $this->prepareSubComment($comment);
        $subComment->onUpdateType = UserComment::$onRestrict;
        $subComment->throwRestrictException = true;
        $comment->save();
        $subComment->save();

        $comment->guid = UserComment::GenerateGuid();
        try {
            $result = $comment->save();
            $this->fail();
        } catch (\yii\db\IntegrityException $ex) {
            $this->assertEquals('Update restrict.', $ex->getMessage());
        }
        $sub = UserComment::find()->id($subComment->id)->one();
        $this->assertInstanceOf(UserComment::className(), $sub);
        $parentAttribute = $comment->parentAttribute;
        $this->assertEquals($comment->getOldAttribute($comment->guidAttribute), $sub->$parentAttribute);
        $this->assertTrue($user->deregister());
    }

    /**
     * @depends testUpdateParentRestrict
     */
    public function testUpdateParentNoAction()
    {
        $user = $this->prepareUser();
        $comment = $this->prepareComment($user);
        $comment->onUpdateType = UserComment::$onNoAction;
        $subComment = $this->prepareSubComment($comment);
        $subComment->onUpdateType = UserComment::$onNoAction;
        $comment->save();
        $subComment->save();

        $guid = $comment->guid;
        $comment->guid = UserComment::GenerateGuid();
        $comment->save();
        $sub = UserComment::find()->id($subComment->id)->one();
        $this->assertInstanceOf(UserComment::className(), $sub);
        $parentAttribute = $comment->parentAttribute;
        $this->assertEquals($guid, $sub->$parentAttribute);
        $this->assertTrue($user->deregister());
    }

    /**
     * @depends testUpdateParentNoAction
     */
    public function testUpdateParentSetNull()
    {
        $user = $this->prepareUser();
        $comment = $this->prepareComment($user);
        $comment->onUpdateType = UserComment::$onSetNull;
        $subComment = $this->prepareSubComment($comment);
        $subComment->onUpdateType = UserComment::$onSetNull;
        $comment->save();
        $subComment->save();

        $comment->guid = UserComment::GenerateGuid();
        $comment->save();
        $sub = UserComment::find()->id($subComment->id)->one();
        $this->assertInstanceOf(UserComment::className(), $sub);
        $parentAttribute = $comment->parentAttribute;
        $this->assertEquals('', $sub->$parentAttribute);
        $this->assertTrue($user->deregister());
    }
}

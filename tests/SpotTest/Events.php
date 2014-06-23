<?php
namespace SpotTest;

/**
 * @package Spot
 */
class Events extends \PHPUnit_Framework_TestCase
{
    private static $entities = ['Post', 'Post\Comment', 'Tag', 'PostTag', 'Author'];

    public static function setupBeforeClass()
    {
        foreach(self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }

        // Insert blog dummy data
        for( $i = 1; $i <= 3; $i++ ) {
            $tag_id = test_spot_mapper('SpotTest\Entity\Tag')->insert([
                'name' => "Title {$i}"
            ]);
        }
        for( $i = 1; $i <= 3; $i++ ) {
            $author_id = test_spot_mapper('SpotTest\Entity\Author')->insert([
                'email' => $i.'user@somewhere.com',
                'password' => 'securepassword'
            ]);
        }
        for( $i = 1; $i <= 10; $i++ ) {
            $post_id = test_spot_mapper('\SpotTest\Entity\Post')->insert([
                'title' => ($i % 2 ? 'odd' : 'even' ). '_title',
                'body' => '<p>' . $i  . '_body</p>',
                'status' => $i ,
                'date_created' => new \DateTime(),
                'author_id' => rand(1,3)
            ]);
            for( $j = 1; $j <= 2; $j++ ) {
                test_spot_mapper('\SpotTest\Entity\Post\Comment')->insert([
                    'post_id' => $post_id,
                    'name' => ($j % 2 ? 'odd' : 'even' ). '_title',
                    'email' => 'bob@somewhere.com',
                    'body' => ($j % 2 ? 'odd' : 'even' ). '_comment_body',
                ]);
            }
            for( $j = 1; $j <= $i % 3; $j++ ) {
                $posttag_id = test_spot_mapper('\SpotTest\Entity\PostTag')->insert([
                    'post_id' => $post_id,
                    'tag_id' => $j
                ]);
            }
        }
    }

    public static function tearDownAfterClass()
    {
        foreach(self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    protected function setUp()
    {
        Entity\Post::$events = array();
    }

    public function testSaveHooks()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $testcase = $this;

        $post = new \SpotTest\Entity\Post([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);

        $hooks = array();

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeSave', function($post, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, array());
            $hooks[] = 'called beforeSave';
        });

        $eventEmitter->on('afterSave', function($post, $mapper, $result) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, array('called beforeSave'));
            $testcase->assertInstanceOf('\SpotTest\Entity\Post', $post);
            $testcase->assertInstanceOf('\\Spot\\Mapper', $mapper);
            $hooks[] = 'called afterSave';
        });

        $this->assertEquals($hooks, array());

        $mapper->save($post);

        $this->assertEquals(['called beforeSave', 'called afterSave'], $hooks);

        $eventEmitter->removeAllListeners('afterSave');
        $eventEmitter->removeAllListeners('beforeSave');

        $mapper->save($post);

        // Verify that hooks were deregistered
        $this->assertEquals(['called beforeSave', 'called afterSave'], $hooks);
    }

    public function testInsertHooks()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $testcase = $this;

        $post = new \SpotTest\Entity\Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ));

        $hooks = array();

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeInsert', function($post, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, array());
            $hooks[] = 'called beforeInsert';
        });

        $eventEmitter->on('afterInsert', function($post, $mapper, $result) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, array('called beforeInsert'));
            $hooks[] = 'called afterInsert';
        });

        $this->assertEquals($hooks, array());

        $mapper->save($post);

        $this->assertEquals($hooks, array('called beforeInsert', 'called afterInsert'));

        $eventEmitter->removeAllListeners('beforeInsert');
        $eventEmitter->removeAllListeners('afterInsert');
    }

    public function testInsertHooksUpdatesProperty()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = new \SpotTest\Entity\Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1234,
            'date_created' => new \DateTime()
        ));

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeInsert', function($post, $mapper) {
            $post->status = 2;
        });
        $mapper->save($post);
        $post = $mapper->first(array('author_id' => 1234));
        $this->assertEquals(2, $post->status);

        $eventEmitter->removeAllListeners('beforeInsert');
    }

    public function testUpdateHooks()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $testcase = $this;

        $post = new \SpotTest\Entity\Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ));
        $mapper->save($post);

        $hooks = array();

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeInsert', function($post, $mapper) use (&$testcase) {
            $testcase->assertTrue(false);
        });

        $eventEmitter->on('beforeUpdate', function($post, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, array());
            $hooks[] = 'called beforeUpdate';
        });

        $eventEmitter->on('afterUpdate', function($post, $mapper, $result) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, array('called beforeUpdate'));
            $hooks[] = 'called afterUpdate';
        });

        $this->assertEquals($hooks, array());

        $mapper->save($post);

        $this->assertEquals($hooks, array('called beforeUpdate', 'called afterUpdate'));

        $eventEmitter->removeAllListeners('beforeInsert');
        $eventEmitter->removeAllListeners('beforeUpdate');
        $eventEmitter->removeAllListeners('afterUpdate');
    }

    public function testUpdateHookUpdatesProperly()
    {
        $author_id = __LINE__;
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $testcase = $this;

        $post = new \SpotTest\Entity\Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => $author_id,
            'date_created' => new \DateTime()
        ));
        $mapper->save($post);
        $this->assertEquals(1, $post->status);

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeUpdate', function($post, $mapper) {
            $post->status = 9;
        });
        $mapper->save($post);
        $post = $mapper->first(['author_id' => $author_id]);
        $this->assertEquals(9, $post->status);

        $eventEmitter->removeAllListeners('beforeUpdate');
    }

    public function testDeleteHooks()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $testcase = $this;

        $post = new \SpotTest\Entity\Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ));
        $mapper->save($post);

        $hooks = array();

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeDelete', function($post, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, array());
            $hooks[] = 'called beforeDelete';
        });

        $eventEmitter->on('afterDelete', function($post, $mapper, $result) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, array('called beforeDelete'));
            $hooks[] = 'called afterDelete';
        });

        $this->assertEquals($hooks, array());

        $mapper->delete($post);

        $this->assertEquals($hooks, array('called beforeDelete', 'called afterDelete'));

        $eventEmitter->removeAllListeners('beforeDelete');
        $eventEmitter->removeAllListeners('afterDelete');
    }


    public function testEntityHooks()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $eventEmitter = $mapper->eventEmitter();
        $post = new \SpotTest\Entity\Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ));

        $i = $post->status;

        \SpotTest\Entity\Post::$events = array(
            'beforeSave' => array('mock_save_hook')
        );
        $mapper->loadEvents();

        $mapper->save($post);

        $this->assertEquals($i + 1, $post->status);
        $eventEmitter->removeAllListeners('beforeSave');

        \SpotTest\Entity\Post::$events = array(
            'beforeSave' => array('mock_save_hook', 'mock_save_hook')
        );
        $mapper->loadEvents();

        $i = $post->status;

        $mapper->save($post);

        $this->assertEquals($i + 2, $post->status);

        $eventEmitter->removeAllListeners('beforeSave');
    }


    // public function testWithHooks()
    // {
    //     $mapper = test_spot_mapper('SpotTest\Entity\Post');
    //     $testcase = $this;

    //     $hooks = array();

    //     $eventEmitter->on('\SpotTest\Entity\Post', 'beforeWith', function($entityClass, $collection, $with, $mapper) use (&$hooks, &$testcase) {
    //         $testcase->assertEquals('\SpotTest\Entity\Post', $entityClass);
    //         $testcase->assertInstanceOf('\\Spot\\Entity\\Collection', $collection);
    //         $testcase->assertEquals(array('comments'), $with);
    //         $testcase->assertInstanceOf('\\Spot\\Mapper', $mapper);
    //         $hooks[] = 'Called beforeWith';
    //     });

    //     $eventEmitter->on('\SpotTest\Entity\Post', 'loadWith', function($entityClass, $collection, $relationName, $mapper) use (&$hooks, &$testcase) {
    //         $testcase->assertEquals('\SpotTest\Entity\Post', $entityClass);
    //         $testcase->assertInstanceOf('\\Spot\\Entity\\Collection', $collection);
    //         $testcase->assertInstanceOf('\\Spot\\Mapper', $mapper);
    //         $testcase->assertEquals('comments', $relationName);
    //         $hooks[] = 'Called loadWith';
    //     });

    //     $eventEmitter->on('\SpotTest\Entity\Post', 'afterWith', function($entityClass, $collection, $with, $mapper) use (&$hooks, &$testcase) {
    //         $testcase->assertEquals('\SpotTest\Entity\Post', $entityClass);
    //         $testcase->assertInstanceOf('\\Spot\\Entity\\Collection', $collection);
    //         $testcase->assertEquals(array('comments'), $with);
    //         $testcase->assertInstanceOf('\\Spot\\Mapper', $mapper);
    //         $hooks[] = 'Called afterWith';
    //     });

    //     $mapper->all('\SpotTest\Entity\Post', array('id' => array(1,2)))->with('comments')->execute();

    //     $this->assertEquals(array('Called beforeWith', 'Called loadWith', 'Called afterWith'), $hooks);
    // }


    // public function testWithAssignmentHooks()
    // {
    //     $mapper = test_spot_mapper('SpotTest\Entity\Post');
    //     $testcase = $this;

    //     $eventEmitter->on('\SpotTest\Entity\Post', 'loadWith', function($entityClass, $collection, $relationName, $mapper) use (&$testcase) {
    //         $relationObj = $mapper->loadRelation($collection, $relationName);
    //         $query = $relationObj->execute()->limit(1)->snapshot();

    //         foreach($collection as $post) {
    //             $one_comment = $query->execute();

    //             $post->comments->assignCollection($one_comment);
    //             $testcase->assertEquals(1, $post->comments->count());
    //         }
    //         return false;
    //     });

    //     $posts = $mapper->all('\SpotTest\Entity\Post')->with('comments')->execute();
    //     foreach($posts as $post) {
    //         $this->assertEquals(1, $post->comments->count());
    //     }
    // }

    public function testHookReturnsFalse()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = new \SpotTest\Entity\Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ));

        $hooks = array();

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeSave', function($post, $mapper) use (&$hooks) {
            $hooks[] = 'called beforeSave';
            return false;
        });

        $eventEmitter->on('afterSave', function($post, $mapper, $result) use (&$hooks) {
            $hooks[] = 'called afterSave';
        });

        $mapper->save($post);

        $this->assertEquals($hooks, array('called beforeSave'));

        $eventEmitter->removeAllListeners('afterSave');
    }

    public function testAfterSaveEvent()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $eventEmitter = $mapper->eventEmitter();
        $post = new \SpotTest\Entity\Post(array(
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ));

        $eventEmitter->removeAllListeners('afterSave');
        \SpotTest\Entity\Post::$events = [
            'afterSave' => ['mock_save_hook']
        ];
        $mapper->loadEvents();

        $result = $mapper->save($post);

        $this->assertEquals(2, $post->status);

        $eventEmitter->removeAllListeners('afterSave');
    }
}

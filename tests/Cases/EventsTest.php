<?php
namespace SpotTest\Cases;

/**
 * @package Spot
 */
class EventsTest extends \PHPUnit\Framework\TestCase
{
    private static $entities = ['PostTag', 'Post\Comment', 'Post', 'Tag', 'Author'];

    public static function setupBeforeClass(): void
    {
        foreach (self::$entities as $entity) {
            \test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }

        // Insert blog dummy data
        for ($i = 1; $i <= 3; $i++) {
            $tag_id = \test_spot_mapper('SpotTest\Entity\Tag')->insert([
                'name' => "Title {$i}"
            ]);
        }
        for ($i = 1; $i <= 4; $i++) {
            $author_id = \test_spot_mapper('SpotTest\Entity\Author')->insert([
                'email' => $i.'user@somewhere.com',
                'password' => 'securepassword'
            ]);
        }

        $postMapper = \test_spot_mapper('\SpotTest\Entity\Post');
        for ($i = 1; $i <= 10; $i++) {
            $post = $postMapper->build([
                'title' => ($i % 2 ? 'odd' : 'even' ). '_title',
                'body' => '<p>' . $i  . '_body</p>',
                'status' => $i ,
                'date_created' => new \DateTime(),
                'author_id' => rand(1,3)
            ]);
            $result = $postMapper->insert($post);

            if (!$result) {
                throw new \Exception("Unable to create post: " . var_export($post->data(), true));
            }

            for ($j = 1; $j <= 2; $j++) {
                \test_spot_mapper('SpotTest\Entity\Post\Comment')->insert([
                    'post_id' => $post->id,
                    'name' => ($j % 2 ? 'odd' : 'even' ). '_title',
                    'email' => 'bob@somewhere.com',
                    'body' => ($j % 2 ? 'odd' : 'even' ). '_comment_body',
                ]);
            }
            for ($j = 1; $j <= $i % 3; $j++) {
                $posttag_id = \test_spot_mapper('SpotTest\Entity\PostTag')->insert([
                    'post_id' => $post->id,
                    'tag_id' => $j
                ]);
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$entities as $entity) {
            \test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    protected function setUp(): void
    {
        \SpotTest\Entity\Post::$events = [];
    }

    public function testSaveHooks()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $testcase = $this;

        $post = new \SpotTest\Entity\Post([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1
        ]);

        $hooks = [];

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeSave', function ($post, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, []);
            $hooks[] = 'called beforeSave';
        });

        $eventEmitter->on('afterSave', function ($post, $mapper, $result) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, ['called beforeSave']);
            $testcase->assertInstanceOf('\SpotTest\Entity\Post', $post);
            $testcase->assertInstanceOf('Spot\Mapper', $mapper);
            $hooks[] = 'called afterSave';
        });

        $this->assertEquals($hooks, []);

        $result = $mapper->save($post);

        $this->assertEquals(['called beforeSave', 'called afterSave'], $hooks);

        $eventEmitter->removeAllListeners('afterSave');
        $eventEmitter->removeAllListeners('beforeSave');

        $mapper->save($post);

        // Verify that hooks were deregistered (not called again)
        $this->assertEquals(['called beforeSave', 'called afterSave'], $hooks);
    }

    public function testInsertHooks()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $testcase = $this;

        $post = new \SpotTest\Entity\Post([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);

        $hooks = [];

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeInsert', function ($post, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, []);
            $hooks[] = 'called beforeInsert';
        });

        $eventEmitter->on('afterInsert', function ($post, $mapper, $result) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, ['called beforeInsert']);
            $hooks[] = 'called afterInsert';
        });

        $this->assertEquals($hooks, []);

        $mapper->save($post);

        $this->assertEquals($hooks, ['called beforeInsert', 'called afterInsert']);

        $eventEmitter->removeAllListeners('beforeInsert');
        $eventEmitter->removeAllListeners('afterInsert');
    }

    public function testInsertHooksUpdatesProperty()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post = new \SpotTest\Entity\Post([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 4,
            'date_created' => new \DateTime()
        ]);

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeInsert', function ($post, $mapper) {
            $post->status = 2;
        });
        $mapper->save($post);
        $post = $mapper->first(['author_id' => 4]);
        $this->assertEquals(2, $post->status);

        $eventEmitter->removeAllListeners('beforeInsert');
    }

    public function testUpdateHooks()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $testcase = $this;

        $post = new \SpotTest\Entity\Post([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);
        $mapper->save($post);

        $hooks = [];

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeInsert', function ($post, $mapper) use (&$testcase) {
            $testcase->fail();
        });

        $eventEmitter->on('beforeUpdate', function ($post, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, []);
            $hooks[] = 'called beforeUpdate';
        });

        $eventEmitter->on('afterUpdate', function ($post, $mapper, $result) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, ['called beforeUpdate']);
            $hooks[] = 'called afterUpdate';
        });

        $this->assertEquals($hooks, []);

        $mapper->save($post);

        $this->assertEquals($hooks, ['called beforeUpdate', 'called afterUpdate']);

        $eventEmitter->removeAllListeners('beforeInsert');
        $eventEmitter->removeAllListeners('beforeUpdate');
        $eventEmitter->removeAllListeners('afterUpdate');
    }

    public function testUpdateHookUpdatesProperly()
    {
        $author_id = __LINE__;
        $author = \test_spot_mapper('SpotTest\Entity\Author')->insert([
            'id' => $author_id,
            'email' => $author_id.'user@somewhere.com',
            'password' => 'securepassword'
        ]);

        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $testcase = $this;

        $post = new \SpotTest\Entity\Post([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => $author_id,
            'date_created' => new \DateTime()
        ]);
        $mapper->save($post);
        $this->assertEquals(1, $post->status);

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeUpdate', function ($post, $mapper) {
            $post->status = 9;
        });
        $mapper->save($post);
        $post = $mapper->first(['author_id' => $author_id]);
        $this->assertEquals(9, $post->status);

        $eventEmitter->removeAllListeners('beforeUpdate');
    }

    public function testDeleteHooks()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $testcase = $this;

        $post = new \SpotTest\Entity\Post([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);
        $mapper->save($post);

        $hooks = [];

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeDelete', function ($post, $mapper) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, []);
            $hooks[] = 'called beforeDelete';
        });

        $eventEmitter->on('afterDelete', function ($post, $mapper, $result) use (&$hooks, &$testcase) {
            $testcase->assertEquals($hooks, ['called beforeDelete']);
            $hooks[] = 'called afterDelete';
        });

        $this->assertEquals($hooks, []);

        $mapper->delete($post);

        $this->assertEquals($hooks, ['called beforeDelete', 'called afterDelete']);

        $eventEmitter->removeAllListeners('beforeDelete');
        $eventEmitter->removeAllListeners('afterDelete');
    }

    public function testDeleteHooksForArrayConditions()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $testcase = $this;

        $post = new \SpotTest\Entity\Post([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);
        $mapper->save($post);

        $entityHooks = [];
        $arrayHooks = [];

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeDelete', function ($conditions, $mapper) use (&$entityHooks) {
            $entityHooks[] = 'called beforeDelete';
        });
        $eventEmitter->on('beforeDeleteConditions', function ($conditions, $mapper) use (&$arrayHooks, &$testcase) {
            $testcase->assertEquals($arrayHooks, []);
            $arrayHooks[] = 'called beforeDeleteConditions';
        });

        $eventEmitter->on('afterDelete', function ($conditions, $mapper, $result) use (&$entityHooks) {
            $entityHooks[] = 'called afterDelete';
        });
        $eventEmitter->on('afterDeleteConditions', function ($conditions, $mapper, $result) use (&$arrayHooks, &$testcase) {
            $testcase->assertEquals($arrayHooks, ['called beforeDeleteConditions']);
            $arrayHooks[] = 'called afterDeleteConditions';
        });

        $this->assertEquals($entityHooks, []);
        $this->assertEquals($arrayHooks, []);

        $mapper->delete([
            $post->primaryKeyField() => $post->primaryKey()
        ]);

        $this->assertEquals($entityHooks, []);
        $this->assertEquals($arrayHooks, ['called beforeDeleteConditions', 'called afterDeleteConditions']);

        $eventEmitter->removeAllListeners('beforeDelete');
        $eventEmitter->removeAllListeners('beforeDeleteConditions');
        $eventEmitter->removeAllListeners('afterDelete');
        $eventEmitter->removeAllListeners('afterDeleteConditions');
    }

    public function testEntityHooks()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $eventEmitter = $mapper->eventEmitter();
        $post = new \SpotTest\Entity\Post([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);

        $i = $post->status;

        \SpotTest\Entity\Post::$events = [
            'beforeSave' => ['mock_save_hook']
        ];
        $mapper->loadEvents();

        $mapper->save($post);

        $this->assertEquals($i + 1, $post->status);
        $eventEmitter->removeAllListeners('beforeSave');

        \SpotTest\Entity\Post::$events = [
            'beforeSave' => ['mock_save_hook', 'mock_save_hook']
        ];
        $mapper->loadEvents();

        $i = $post->status;

        $mapper->save($post);

        $this->assertEquals($i + 2, $post->status);

        $eventEmitter->removeAllListeners('beforeSave');
    }

    public function testWithHooks()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $eventEmitter = $mapper->eventEmitter();
        $testcase = $this;

        $hooks = [];

        $eventEmitter->on('beforeWith', function ($mapper, $collection, $with) use (&$hooks, &$testcase) {
            $testcase->assertEquals('\SpotTest\Entity\Post', $mapper->entity());
            $testcase->assertInstanceOf('Spot\Entity\Collection', $collection);
            $testcase->assertEquals(['comments'], $with);
            $testcase->assertInstanceOf('Spot\Mapper', $mapper);
            $hooks[] = 'Called beforeWith';
        });

        $eventEmitter->on('loadWith', function ($mapper, $collection, $relationName) use (&$hooks, &$testcase) {
            $testcase->assertEquals('\SpotTest\Entity\Post', $mapper->entity());
            $testcase->assertInstanceOf('Spot\Entity\Collection', $collection);
            $testcase->assertInstanceOf('Spot\Mapper', $mapper);
            $testcase->assertEquals('comments', $relationName);
            $hooks[] = 'Called loadWith';
        });

        $eventEmitter->on('afterWith', function ($mapper, $collection, $with) use (&$hooks, &$testcase) {
            $testcase->assertEquals('\SpotTest\Entity\Post', $mapper->entity());
            $testcase->assertInstanceOf('Spot\Entity\Collection', $collection);
            $testcase->assertEquals(['comments'], $with);
            $testcase->assertInstanceOf('Spot\Mapper', $mapper);
            $hooks[] = 'Called afterWith';
        });

        $mapper->all('\SpotTest\Entity\Post', ['id' => [1,2]])->with('comments')->execute();

        $this->assertEquals(['Called beforeWith', 'Called loadWith', 'Called afterWith'], $hooks);
        $eventEmitter->removeAllListeners();
    }

    public function testWithAssignmentHooks()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $eventEmitter = $mapper->eventEmitter();

        $eventEmitter->on('loadWith', function ($mapper, $collection, $relationName) {
            foreach ($collection as $post) {
                $comments = [];
                $comments[] = new \SpotTest\Entity\Post\Comment([
                    'post_id' => $post->id,
                    'name'    => 'Chester Tester',
                    'email'   => 'chester@tester.com',
                    'body'    => 'Some body content here that Chester made!'
                ]);

                $post->relation($relationName, new \Spot\Entity\Collection($comments));
            }

            return false;
        });

        $posts = $mapper->all()->with('comments')->execute();
        foreach ($posts as $post) {
            $this->assertEquals(1, $post->comments->count());
        }

        $eventEmitter->removeAllListeners();
    }

    public function testHookReturnsFalse()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post = new \SpotTest\Entity\Post([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);

        $hooks = [];

        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeSave', function ($post, $mapper) use (&$hooks) {
            $hooks[] = 'called beforeSave';

            return false;
        });

        $eventEmitter->on('afterSave', function ($post, $mapper, $result) use (&$hooks) {
            $hooks[] = 'called afterSave';
        });

        $mapper->save($post);

        $this->assertEquals($hooks, ['called beforeSave']);

        $eventEmitter->removeAllListeners('afterSave');
    }

    public function testAfterSaveEvent()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $eventEmitter = $mapper->eventEmitter();
        $post = new \SpotTest\Entity\Post([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);

        $eventEmitter->removeAllListeners('afterSave');
        \SpotTest\Entity\Post::$events = [
            'afterSave' => ['mock_save_hook']
        ];
        $mapper->loadEvents();

        $result = $mapper->save($post);

        $this->assertEquals(2, $post->status);

        $eventEmitter->removeAllListeners('afterSave');
    }

    public function testValidationEvents()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $eventEmitter = $mapper->eventEmitter();
        $post = new \SpotTest\Entity\Post([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);

        $hooks = [];
        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeValidate', function ($post, $mapper, $validator) use (&$hooks) {
            $hooks[] = 'called beforeValidate';
        });
        $eventEmitter->on('afterValidate', function ($post, $mapper, $validator) use (&$hooks) {
            $hooks[] = 'called afterValidate';
        });

        $mapper->validate($post);

        $this->assertEquals(['called beforeValidate', 'called afterValidate'], $hooks);

        $eventEmitter->removeAllListeners();
    }

    public function testBeforeValidateEventStopsValidation()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $eventEmitter = $mapper->eventEmitter();
        $post = new \SpotTest\Entity\Post([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);

        $hooks = [];
        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeValidate', function ($post, $mapper, $validator) use (&$hooks) {
            $hooks[] = 'called beforeValidate';

            return false; // Should stop validation
        });
        $eventEmitter->on('afterValidate', function ($post, $mapper, $validator) use (&$hooks) {
            $hooks[] = 'called afterValidate';
        });

        $mapper->validate($post);

        $this->assertEquals(['called beforeValidate'], $hooks);

        $eventEmitter->removeAllListeners();
    }

    public function testSaveEventsTriggeredOnCreate()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');

        $hooks = [];
        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeSave', function ($post, $mapper) use (&$hooks) {
            $hooks[] = 'before';
        });
        $eventEmitter->on('afterSave', function ($post, $mapper) use (&$hooks) {
            $hooks[] = 'after';
        });

        $mapper->create([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);

        $this->assertEquals(['before', 'after'], $hooks);
        $eventEmitter->removeAllListeners();
    }

    public function testLoadEventCallOnGet()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');

        $hooks = [];
        $eventEmitter = $mapper->eventEmitter();

        $eventEmitter->on('afterLoad', function ($post, $mapper) use (&$hooks) {
            $hooks[] = 'after';
        });

        $mapper->create([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);

        $this->assertEquals(['after'], $hooks);
        $eventEmitter->removeAllListeners();
    }

    public function testSaveEventsTriggeredOnUpdate()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $eventEmitter = $mapper->eventEmitter();

        $hooks = [];
        $eventEmitter = $mapper->eventEmitter();
        $eventEmitter->on('beforeSave', function ($post, $mapper) use (&$hooks) {
            $hooks[] = 'before';
        });
        $eventEmitter->on('afterSave', function ($post, $mapper) use (&$hooks) {
            $hooks[] = 'after';
        });

        $post = $mapper->create([
            'title' => 'A title',
            'body' => '<p>body</p>',
            'status' => 1,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);

        $post->status = 2;
        $mapper->update($post);

        $this->assertEquals(['before', 'after', 'before', 'after'], $hooks);
        $eventEmitter->removeAllListeners();
    }
}

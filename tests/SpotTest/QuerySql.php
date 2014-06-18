<?php
namespace SpotTest;

/**
 * @package Spot
 */
class QuerySql extends \PHPUnit_Framework_TestCase
{
    public static function setupBeforeClass()
    {
        $mapper = test_spot_mapper();
        foreach(['Post', 'Post\Comment', 'Tag', 'PostTag', 'Author'] as $entity) {
            $mapper->entity('\SpotTest\Entity\\' . $entity)->migrate();
        }

        // Insert blog dummy data
        $tags = [];
        for( $i = 1; $i <= 3; $i++ ) {
            $tags[] = $mapper->entity('SpotTest\Entity\Tag')->insert([
                'name' => "Title {$i}"
            ]);
        }
        for( $i = 1; $i <= 3; $i++ ) {
            $author_id = $mapper->entity('SpotTest\Entity\Author')->insert([
                'email' => $i.'user@somewhere.com',
                'password' => 'securepassword'
            ]);
        }
        for( $i = 1; $i <= 10; $i++ ) {
            $post_id = $mapper->entity('SpotTest\Entity\Post')->insert([
                'title' => ($i % 2 ? 'odd' : 'even' ). '_title',
                'body' => '<p>' . $i  . '_body</p>',
                'status' => $i ,
                'date_created' => new \DateTime(),
                'author_id' => rand(1,3)
            ]);
            for( $j = 1; $j <= 2; $j++ ) {
                $mapper->entity('SpotTest\Entity\Post\Comment')->insert([
                    'post_id' => $post_id,
                    'name' => ($j % 2 ? 'odd' : 'even' ). '_title',
                    'email' => 'bob@somewhere.com',
                    'body' => ($j % 2 ? 'odd' : 'even' ). '_comment_body',
                ]);
            }
            foreach($tags as $tag_id) {
                $posttag_id = $mapper->entity('SpotTest\Entity\PostTag')->insert([
                    'post_id' => $post_id,
                    'tag_id' => $tag_id
                ]);
            }
        }
    }

    public static function tearDownAfterClass()
    {
        $mapper = test_spot_mapper();
        foreach(['Post', 'Post\Comment', 'Tag', 'PostTag', 'Author'] as $entity) {
            $mapper->entity('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testWhereArrayMultipleSeparatedByAnd()
    {
        $mapper = test_spot_mapper()->entity('SpotTest\Entity\Post');
        $query = $mapper->select()->noQuote()->where(['status' => 2, 'title' => 'even_title']);
        $this->assertEquals("SELECT * FROM test_posts test_posts WHERE test_posts.status = ? AND test_posts.title = ?", $query->toSql());
    }

    public function testInsertPostTagWithUniqueConstraint()
    {
        $mapper = test_spot_mapper();
        $posttag_id = $mapper->entity('SpotTest\Entity\PostTag')->insert([
            'post_id' => 55,
            'tag_id' => 55
        ]);
    }

    public function testQueryInstance()
    {
        $mapper = test_spot_mapper();
        $posts = $mapper->entity('SpotTest\Entity\Post')->all(['title' => 'even_title']);
        $this->assertTrue($posts instanceof \Spot\Query);
    }

    public function testQueryCollectionInstance()
    {
        $mapper = test_spot_mapper();
        $posts = $mapper->entity('SpotTest\Entity\Post')->all(['title' => 'even_title']);
        $this->assertTrue($posts instanceof \Spot\Query);
        $this->assertTrue($posts->execute() instanceof \Spot\Entity\Collection);
    }

    // Bare (implicit equals)
    public function testOperatorNone()
    {
        $mapper = test_spot_mapper()->entity('SpotTest\Entity\Post');
        $query = $mapper->select()->noQuote()->where(['status' => 2]);
        $this->assertEquals("SELECT * FROM test_posts test_posts WHERE test_posts.status = ?", $query->toSql());
        $this->assertEquals(count($query), 1);
    }

    // Equals
    public function testOperatorEq()
    {
        $mapper = test_spot_mapper();
        $query = $mapper->entity('SpotTest\Entity\Post')->select()->noQuote()->where(['status :eq' => 2]);
        $this->assertEquals("SELECT * FROM test_posts test_posts WHERE test_posts.status = ?", $query->toSql());
        $this->assertEquals(count($query), 1);
    }

    // Less than
    public function testOperatorLt()
    {
        $mapper = test_spot_mapper();
        $this->assertEquals(4, $mapper->entity('SpotTest\Entity\Post')->where(['status <' => 5])->count());
        $this->assertEquals(4, $mapper->entity('SpotTest\Entity\Post')->where(['status :lt' => 5])->count());
    }

    // Greater than
    public function testOperatorGt()
    {
        $mapper = test_spot_mapper();
        $this->assertFalse($mapper->entity('SpotTest\Entity\Post')->first(['status >' => 10]));
        $this->assertFalse($mapper->entity('SpotTest\Entity\Post')->first(['status :gt' => 10]));
    }

    // Greater than or equal to
    public function testOperatorGte()
    {
        $mapper = test_spot_mapper();
        $this->assertEquals(6, $mapper->entity('SpotTest\Entity\Post')->where(['status >=' => 5])->count());
        $this->assertEquals(6, $mapper->entity('SpotTest\Entity\Post')->where(['status :gte' => 5])->count());
    }

    // Use same column name more than once
    public function testFieldMultipleUsage()
    {
        $mapper = test_spot_mapper();
        $countResult = $mapper->entity('SpotTest\Entity\Post')
            ->where(['status' => 1])
            ->orWhere(['status' => 2])
            ->count();
        $this->assertEquals(2, $countResult);
    }

    public function testArrayDefaultIn()
    {
        $mapper = test_spot_mapper();
        $query = $mapper->entity('SpotTest\Entity\Post')->select()->noQuote()->where(['status' => [2]]);
        $post = $query->first();
        $this->assertEquals("SELECT * FROM test_posts test_posts WHERE test_posts.status IN (?) LIMIT 1", $query->toSql());
        $this->assertEquals(2, $post->status);
    }

    public function testArrayInSingle()
    {
        $mapper = test_spot_mapper();

        // Numeric
        $query = $mapper->entity('SpotTest\Entity\Post')->where(['status :in' => [2]]);
        $this->assertContains('IN', $query->toSql());
        $this->assertEquals(2, $query->first()->status);

        // Alpha
        $post = $mapper->entity('SpotTest\Entity\Post')->first(['status :in' => ['a']]);
        $this->assertFalse($post);
    }

    public function testArrayNotInSingle()
    {
        $mapper = test_spot_mapper();
        $post = $mapper->entity('SpotTest\Entity\Post')->first(['status !=' => [2]]);
        $this->assertFalse($post->status == 2);
        $post = $mapper->entity('SpotTest\Entity\Post')->first(['status :not' => [2]]);
        $this->assertFalse($post->status == 2);
    }

    public function testArrayMultiple()
    {
        $mapper = test_spot_mapper();
        $posts = $mapper->entity('SpotTest\Entity\Post')->where(['status' => [3,4,5]]);
        $this->assertContains('IN', $posts->toSql());
        $this->assertEquals(3, $posts->count());
        $posts = $mapper->entity('SpotTest\Entity\Post')->where(['status :in' => [3,4,5]]);
        $this->assertContains('IN', $posts->toSql());
        $this->assertEquals(3, $posts->count());
    }

    public function testArrayNotInMultiple()
    {
        $mapper = test_spot_mapper();
        $posts = $mapper->entity('SpotTest\Entity\Post')->where(['status !=' => [3,4,5]]);
        $this->assertContains('NOT IN', $posts->toSql());
        $this->assertEquals(7, $posts->count());
        $posts = $mapper->entity('SpotTest\Entity\Post')->where(['status :not' => [3,4,5]]);
        $this->assertContains('NOT IN', $posts->toSql());
        $this->assertEquals(7, $posts->count());
    }

    public function testQueryHavingClause()
    {
        $mapper = test_spot_mapper();

        if (false /* @TODO: If SQLite connection */) {
            $this->markTestSkipped('Not support in Sqlite - requires group by');
        }

        $posts = $mapper->entity('SpotTest\Entity\Post')
            ->select('id, MAX(status) as maximus')
            ->having(['maximus' => 10]);
        $this->assertContains('HAVING', $posts->toSql());
        $this->assertEquals(1, count($posts->toArray()));
    }

    public function testQueryEmptyArrayIsNullToAvoidSQLErrorOnEmptyINClause()
    {
        $mapper = test_spot_mapper();
        $posts = $mapper->entity('SpotTest\Entity\Post')->where(['status' => []]);
        $this->assertContains('IS NULL', $posts->toSql());
        $this->assertEquals(0, count($posts));
    }
}

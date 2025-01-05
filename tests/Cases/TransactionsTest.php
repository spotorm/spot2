<?php
namespace SpotTest\Cases;

/**
 * @package Spot
 */
class TransactionsTest extends \PHPUnit\Framework\TestCase
{
    private static $entities = ['Post', 'Author'];

    public static function setupBeforeClass(): void
    {
        foreach (self::$entities as $entity) {
            \test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }

        $authorMapper = \test_spot_mapper('SpotTest\Entity\Author');
        $author = $authorMapper->build([
            'id' => 1,
            'email' => 'example@example.com',
            'password' => 't00r',
            'is_admin' => false
        ]);
        $result = $authorMapper->insert($author);

        if (!$result) {
            throw new \Exception("Unable to create author: " . var_export($author->data(), true));
        }
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$entities as $entity) {
            \test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testInsertWithTransaction()
    {
        $post = new \SpotTest\Entity\Post();
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post->title = "Test Post with Transaction";
        $post->body = "<p>This is a really awesome super-duper post -- in a TRANSACTION!.</p>";
        $post->date_created = new \DateTime();
        $post->author_id = 1;

        // Save in transation
        $mapper->transaction(function ($mapper) use ($post) {
            $result = $mapper->insert($post);
        });

        // Ensure save was successful
        $this->assertInstanceOf('\SpotTest\Entity\Post', $mapper->first(['title' => $post->title]));
    }

    public function testInsertWithTransactionRollbackOnException()
    {
        $post = new \SpotTest\Entity\Post();
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post->title = "Rolledback";
        $post->body = "<p>This is a really awesome super-duper post -- in a TRANSACTION!.</p>";
        $post->date_created = new \DateTime();
        $post->author_id = 1;

        // Save in transation
        $phpunit = $this;

        try {
            $mapper->transaction(function ($mapper) use ($post, $phpunit) {
                $result = $mapper->insert($post);

                // Throw exception AFTER save to trigger rollback
                throw new \LogicException("Exceptions should trigger auto-rollback");
            });
        } catch (\LogicException $e) {
            // Ensure record was NOT saved
            $this->assertFalse($mapper->first(['title' => $post->title]));
        }
    }

    public function testInsertWithTransactionRollbackOnReturnFalse()
    {
        $post = new \SpotTest\Entity\Post();
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post->title = "Rolledback";
        $post->body = "<p>This is a really awesome super-duper post -- in a TRANSACTION!.</p>";
        $post->date_created = new \DateTime();
        $post->author_id = 1;

        // Save in transation
        $phpunit = $this;

        $mapper->transaction(function ($mapper) use ($post, $phpunit) {
            $result = $mapper->insert($post);

            // Return false AFTER save to trigger rollback
            return false;
        });

        // Ensure record was NOT saved
        $this->assertFalse($mapper->first(['title' => $post->title]));
    }
}

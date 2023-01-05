<?php

namespace Bdf\Prime\MongoDB\Driver\ResultSet;

use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use MongoDB\BSON\Unserializable;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Query;
use PHPUnit\Framework\TestCase;

/**
 * Class CursorResultSetTest
 */
class CursorResultSetTest extends TestCase
{
    /**
     * @var MongoConnection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $collection = 'person';


    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $manager = new ConnectionManager(new ConnectionRegistry([
            'mongo' => [
                'driver' => 'mongodb',
                'host'   => $_ENV['MONGO_HOST'],
                'dbname' => 'TEST',
            ],
        ]));
        ConnectionFactory::registerDriverMap('mongodb', MongoDriver::class, MongoConnection::class);

        $this->connection = $manager->getConnection('mongo');


        $this->connection->insert($this->collection, [
            '_id'        => '1',
            'name.first' => 'John',
            'name.last'  => 'Doe',
            'birth'      => new \DateTime('1985-10-05')
        ]);

        $this->connection->insert($this->collection, [
            '_id'        => '2',
            'name.first' => 'François',
            'name.last'  => 'Dupont',
            'birth'      => new \DateTime('1978-05-03')
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->connection->dropDatabase();
    }

    /**
     *
     */
    public function test_fetchObject()
    {
        $cursor = $this->cursor();
        $this->assertSame($cursor, $cursor->fetchMode(ResultSetInterface::FETCH_OBJECT));

        $this->assertEquals([
            (object) [
                '_id'  => '1',
                'name' => (object) [
                    'first' => 'John',
                    'last'  => 'Doe'
                ],
                'birth' => new UTCDateTime((new \DateTime('1985-10-05'))->getTimestamp() * 1000)
            ],
            (object) [
                '_id'  => '2',
                'name' => (object) [
                    'first' => 'François',
                    'last'  => 'Dupont'
                ],
                'birth' => new UTCDateTime((new \DateTime('1978-05-03'))->getTimestamp() * 1000)
            ],
        ], $cursor->all());
    }

    /**
     *
     */
    public function test_fetchAssoc()
    {
        $cursor = $this->cursor();
        $this->assertSame($cursor, $cursor->fetchMode(ResultSetInterface::FETCH_ASSOC));

        $this->assertEquals([
            [
                '_id'  => '1',
                'name.first' => 'John',
                'name.last'  => 'Doe',
                'birth' => new UTCDateTime((new \DateTime('1985-10-05'))->getTimestamp() * 1000)
            ],
            [
                '_id'  => '2',
                'name.first' => 'François',
                'name.last'  => 'Dupont',
                'birth' => new UTCDateTime((new \DateTime('1978-05-03'))->getTimestamp() * 1000)
            ],
        ], $cursor->all());
    }

    /**
     *
     */
    public function test_fetchNum()
    {
        $cursor = $this->cursor();
        $this->assertSame($cursor, $cursor->fetchMode(ResultSetInterface::FETCH_NUM));

        $this->assertEquals([
            ['1', 'John', 'Doe', new UTCDateTime((new \DateTime('1985-10-05'))->getTimestamp() * 1000)],
            ['2', 'François', 'Dupont', new UTCDateTime((new \DateTime('1978-05-03'))->getTimestamp() * 1000)],
        ], $cursor->all());
    }

    /**
     *
     */
    public function test_fetchColumn_embedded()
    {
        $cursor = $this->cursor();
        $this->assertSame($cursor, $cursor->fetchMode(ResultSetInterface::FETCH_COLUMN, 1));

        $this->assertEquals(['John', 'François'], $cursor->all());
    }

    /**
     *
     */
    public function test_fetchColumn_root()
    {
        $cursor = $this->cursor();
        $this->assertSame($cursor, $cursor->fetchMode(ResultSetInterface::FETCH_COLUMN, 3));

        $this->assertEquals([new UTCDateTime((new \DateTime('1985-10-05'))->getTimestamp() * 1000), new UTCDateTime((new \DateTime('1978-05-03'))->getTimestamp() * 1000)], $cursor->all());
    }

    /**
     *
     */
    public function test_fetchColumn_first()
    {
        $cursor = $this->cursor();
        $this->assertSame($cursor, $cursor->fetchMode(ResultSetInterface::FETCH_COLUMN, 0));

        $this->assertEquals(['1', '2'], $cursor->all());
    }

    /**
     *
     */
    public function test_fetchClass_simple()
    {
        $cursor = $this->cursor();
        $this->assertSame($cursor, $cursor->fetchMode(ResultSetInterface::FETCH_CLASS, MyPerson::class));

        $this->assertEquals([
            new MyPerson([
                '_id'  => '1',
                'name' => [
                    'first' => 'John',
                    'last'  => 'Doe'
                ],
                'birth' => new UTCDateTime((new \DateTime('1985-10-05'))->getTimestamp() * 1000)
            ]),
            new MyPerson([
                '_id'  => '2',
                'name' => [
                    'first' => 'François',
                    'last'  => 'Dupont'
                ],
                'birth' => new UTCDateTime((new \DateTime('1978-05-03'))->getTimestamp() * 1000)
            ]),
        ], $cursor->all());
    }

    /**
     *
     */
    public function test_fetchClass_unserializable()
    {
        $cursor = $this->cursor();
        $this->assertSame($cursor, $cursor->fetchMode(ResultSetInterface::FETCH_CLASS, UnserializablePerson::class));

        $this->assertEquals([
            new UnserializablePerson([
                '_id'  => '1',
                'name' => [
                    'first' => 'John',
                    'last'  => 'Doe'
                ],
                'birth' => new UTCDateTime((new \DateTime('1985-10-05'))->getTimestamp() * 1000)
            ]),
            new UnserializablePerson([
                '_id'  => '2',
                'name' => [
                    'first' => 'François',
                    'last'  => 'Dupont'
                ],
                'birth' => new UTCDateTime((new \DateTime('1978-05-03'))->getTimestamp() * 1000)
            ]),
        ], $cursor->all());
    }

    /**
     * @return CursorResultSet
     */
    private function cursor()
    {
        return new CursorResultSet($this->connection->executeSelect($this->collection, new Query([])));
    }
}

class MyPerson
{
    use ArrayInjector;

    public $_id;
    public $name;
    public $birth;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}

class UnserializablePerson implements Unserializable
{
    use ArrayInjector;

    public $_id;
    public $name;
    public $birth;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }

    #[\ReturnTypeWillChange]
    public function bsonUnserialize(array $data)
    {
        $this->_id = $data['_id'];
        $this->name = (array) $data['name'];
        $this->birth = $data['birth'];
    }
}

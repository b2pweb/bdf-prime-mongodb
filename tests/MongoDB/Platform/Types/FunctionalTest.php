<?php

namespace Bdf\Prime\Types;

use PHPUnit\Framework\TestCase;
use Bdf\Prime\Platform\PlatformTypes;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;

/**
 * @group Bdf
 * @group Bdf_Prime
 * @group Bdf_Prime_MongoDB
 * @group Bdf_Prime_MongoDB_Platform
 * @group Bdf_Prime_MongoDB_Platform_Types
 * @group Bdf_Prime_MongoDB_Platform_Types_Functional
 */
class FunctionalTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var PlatformTypes
     */
    private $types;


    /**
     *
     */
    protected function setUp()
    {
        $this->primeStart();
        $this->primeStart();

        Prime::service()->connections()->declareConnection('mongo', [
            'driver' => 'mongodb',
            'host'   => '127.0.0.1',
            'dbname' => 'TEST',
        ]);

        $this->types = $this->prime()->connection('mongo')->platform()->types();
    }

    /**
     * @dataProvider typesProvider
     */
    public function test_to_from_database($name, $value)
    {
        $type = $this->types->get($name);

        $this->assertEquals($value, $type->fromDatabase($type->toDatabase($value)));
        $this->assertEquals($name, $type->name());
    }

    /**
     * @todo uncomment when types will be implemented
     */
    public function typesProvider()
    {
        return [
            [TypeInterface::TARRAY,     ['foo', 'bar']],
            [TypeInterface::JSON,       ['foo' => 'bar']],
            [TypeInterface::OBJECT,     (object) ['foo' => 'bar']],
            [TypeInterface::BOOLEAN,    true],
            [TypeInterface::TINYINT,    5],
            [TypeInterface::SMALLINT,   652],
            [TypeInterface::INTEGER,    14587],
            [TypeInterface::BIGINT,     '455741'],
            [TypeInterface::DOUBLE,     1.23],
            [TypeInterface::FLOAT,      1.23],
            [TypeInterface::DECIMAL,    1.23],
            [TypeInterface::STRING,     'azerty'],
            [TypeInterface::TEXT,       'azerty'],
            [TypeInterface::BLOB,       'azerty'],
            [TypeInterface::BINARY,     'azerty'],
            [TypeInterface::GUID,       '123'],
            [TypeInterface::DATETIME,   new \DateTime('2017-08-25 12:14:23')],
            //[TypeInterface::DATETIMETZ, new \DateTime('2017-08-25 12:14:23')],
            //[TypeInterface::DATE,       new \DateTime('2017-08-25')],
            //[TypeInterface::TIME,       new \DateTime('12:14:23')],
        ];
    }
}

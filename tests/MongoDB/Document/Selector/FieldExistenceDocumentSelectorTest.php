<?php

namespace MongoDB\Document\Selector;

use Bdf\Prime\MongoDB\Collection\MongoCollection;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Document\MongoDocument;
use Bdf\Prime\MongoDB\Document\Selector\DocumentSelectorInterface;
use Bdf\Prime\MongoDB\Document\Selector\FieldExistenceDocumentSelector;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use MongoDB\Document\Person;
use PHPUnit\Framework\TestCase;

class FieldExistenceDocumentSelectorTest extends TestCase
{
    use PrimeTestCase;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->primeStart();

        Prime::service()->connections()->declareConnection('mongo', [
            'driver' => 'mongodb',
            'host'   => $_ENV['MONGO_HOST'],
            'dbname' => 'TEST',
        ]);

        Mongo::configure(new MongoCollectionLocator(Prime::service()->connections()));
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        Prime::connection('mongo')->dropDatabase();
    }

    public function test_functional()
    {
        $base = new UserDocument('foo', 'bar');
        $webUser = new WebUserDocument('bae', 'baz');
        $webUser->pseudo = 'baba';
        $webUser->avatar = 'baba.jpg';
        $admin = new AdminUserDocument('zab', 'rab');
        $admin->code = 123;
        $admin->roles = ['foo', 'bar'];

        UserDocument::collection()->add($base);
        UserDocument::collection()->add($webUser);
        UserDocument::collection()->add($admin);

        $this->assertEquals([$base, $webUser, $admin], UserDocument::findAllRaw());
        $this->assertEquals(3, UserDocument::count());
        $this->assertEquals([$webUser], WebUserDocument::findAllRaw());
        $this->assertEquals(1, WebUserDocument::count());
        $this->assertEquals([$admin], AdminUserDocument::findAllRaw());
        $this->assertEquals(1, AdminUserDocument::count());
    }

    public function test_unit()
    {
        $selector = new FieldExistenceDocumentSelector(UserDocument::class, [
            WebUserDocument::class => ['pseudo', 'avatar'],
            AdminUserDocument::class => ['roles', 'code'],
        ]);

        $this->assertInstanceOf(UserDocument::class, $selector->instantiate([]));
        $this->assertInstanceOf(UserDocument::class, $selector->instantiate(['foo' => 'bar']));
        $this->assertInstanceOf(UserDocument::class, $selector->instantiate(['pseudo' => 'bar']));
        $this->assertInstanceOf(WebUserDocument::class, $selector->instantiate(['pseudo' => 'bar', 'avatar' => null]));
        $this->assertInstanceOf(AdminUserDocument::class, $selector->instantiate(['roles' => [], 'code' => 123]));

        $this->assertSame([], $selector->filters(UserDocument::class));
        $this->assertSame(['pseudo' => ['$exists' => true], 'avatar' => ['$exists' => true]], $selector->filters(WebUserDocument::class));
        $this->assertSame(['roles' => ['$exists' => true], 'code' => ['$exists' => true]], $selector->filters(AdminUserDocument::class));
    }
}

class UserDocument extends MongoDocument
{
    public ?string $username = null;
    public ?string $password = null;

    public function __construct(?string $username = null, ?string $password = null)
    {
        $this->username = $username;
        $this->password = $password;
    }
}

class WebUserDocument extends UserDocument
{
    public ?string $pseudo = null;
    public ?string $avatar = null;
}

class AdminUserDocument extends UserDocument
{
    public array $roles = [];
    public ?int $code = null;
}

class UserDocumentMapper extends DocumentMapper
{
    public function connection(): string
    {
        return 'mongo';
    }

    public function collection(): string
    {
        return 'user';
    }

    protected function createDocumentSelector(string $documentBaseClass): DocumentSelectorInterface
    {
        return new FieldExistenceDocumentSelector($documentBaseClass, [
            WebUserDocument::class => ['pseudo', 'avatar'],
            AdminUserDocument::class => ['roles', 'code'],
        ]);
    }
}

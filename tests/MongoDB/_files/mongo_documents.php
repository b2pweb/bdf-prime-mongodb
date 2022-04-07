<?php

namespace Bdf\Prime\MongoDB\TestDocument;

use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Document\MongoDocument;
use Bdf\Prime\MongoDB\Document\Selector\DiscriminatorFieldDocumentSelector;
use Bdf\Prime\MongoDB\Document\Selector\DocumentSelectorInterface;
use MongoDB\BSON\ObjectId;

class DiscrimiatorDocument extends MongoDocument
{
    public string $_type = '';
    public ?int $value = null;

    /**
     * @param int|null $value
     */
    public function __construct(?int $value = null)
    {
        $this->value = $value;
    }
}

class FooDocument extends DiscrimiatorDocument
{
    public string $_type = 'foo';
    public ?string $foo = null;

    public function __construct(?int $value = null, ?string $foo = null)
    {
        parent::__construct($value);

        $this->foo = $foo;
    }
}

class BarDocument extends DiscrimiatorDocument
{
    public string $_type = 'bar';
    public ?int $bar = null;

    public function __construct(?int $value = null, ?int $bar = null)
    {
        parent::__construct($value);

        $this->bar = $bar;
    }
}

class DiscrimiatorDocumentMapper extends DocumentMapper
{
    public function connection(): string
    {
        return 'mongo';
    }

    public function collection(): string
    {
        return 'with_discriminator';
    }

    protected function createDocumentSelector(string $documentBaseClass): DocumentSelectorInterface
    {
        return new DiscriminatorFieldDocumentSelector($documentBaseClass, [
            'foo' => FooDocument::class,
            'bar' => BarDocument::class,
        ]);
    }
}

class DocumentWithoutBaseClass
{
    private ?ObjectId $_id = null;
    private ?string $value = null;

    public function __construct(?string $value = null)
    {
        $this->value = $value;
    }

    public function id(): ?ObjectId
    {
        return $this->_id;
    }

    public function setId(?ObjectId $id): DocumentWithoutBaseClass
    {
        $this->_id = $id;
        return $this;
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): DocumentWithoutBaseClass
    {
        $this->value = $value;
        return $this;
    }
}

class DocumentWithoutBaseClassMapper extends DocumentMapper
{
    public function connection(): string
    {
        return 'mongo';
    }

    public function collection(): string
    {
        return 'without_base_class';
    }
}

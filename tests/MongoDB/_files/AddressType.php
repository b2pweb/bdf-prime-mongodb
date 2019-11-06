<?php

namespace Bdf\Prime;

use Bdf\Prime\MongoDB\Test\Address;
use Bdf\Prime\Types\TypeInterface;

class AddressType implements TypeInterface
{
    public function fromDatabase($value, array $fieldOptions = [])
    {
        return $value ? new Address($value) : null;
    }

    public function toDatabase($value)
    {
        return $value ? $value->export() : null;
    }

    public function name()
    {
        return 'Address';
    }

    public function phpType()
    {
        return Address::class;
    }
}

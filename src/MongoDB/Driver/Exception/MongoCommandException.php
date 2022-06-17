<?php

namespace Bdf\Prime\MongoDB\Driver\Exception;

use MongoDB\Driver\Exception\CommandException;

/**
 * @method CommandException getPrevious()
 */
class MongoCommandException extends MongoDBALException
{
    public function __construct(CommandException $previous)
    {
        parent::__construct('MongoDB : ' . $previous->getMessage(), $previous->getCode(), $previous);
    }

    /**
     * Get the command error code
     *
     * @return string|null
     * @see https://github.com/mongodb/mongo/blob/master/src/mongo/base/error_codes.yml
     */
    public function errorCode(): ?string
    {
        return $this->getPrevious()->getResultDocument()->codeName ?? null;
    }
}

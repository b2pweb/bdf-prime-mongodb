<?php

namespace Bdf\Prime\MongoDB\Query;

/**
 * The query allow configuring options parameters
 *
 * Note: in mongo, options are not shared between write and querying, so be aware of the context when using it
 */
interface OptionsConfigurable
{
    /**
     * Define an option for the current query
     *
     * @param string $name The option name
     * @param mixed $value The option value
     *
     * @return $this
     */
    public function option(string $name, $value);

    /**
     * Define the collation to use on the current query
     *
     * Collation allows users to specify language-specific rules for string comparison,
     * such as rules for lettercase and accent marks.
     *
     * To enable case-insensitive search or sort you can use `$query->collation(['locale' => 'en', 'strength' => 1])`
     *
     * This option is used by all query types
     *
     * @param array{
     *     locale: string,
     *     strength?: int,
     *     caseLevel?: bool,
     *     caseFirst?: "upper"|"lower"|"off",
     *     numericOrdering?: bool,
     *     alternate?: "non-ignorable"|"shifted",
     *     maxVariable?: "punct"|"space",
     *     backwards?: bool,
     *     normalization?: bool,
     * } $collation
     * @return mixed
     *
     * @see https://docs.mongodb.com/upcoming/reference/collation/#collation-document
     */
    public function collation(array $collation);

    /**
     * Index specification.
     * Specify either the index name as a string or the index key pattern.
     * If specified, then the query system will only consider plans using the hinted index.
     *
     * This option is used by all query types
     *
     * @param string|array|object $hint
     * @return $this
     */
    public function hint($hint);

    /**
     * Update only the first matching document if false, or all matching documents true.
     * This option cannot be true if newObj is a replacement document.
     *
     * This option is only supported by "update" query
     *
     * @param bool $flag
     * @return $this
     */
    public function multi(bool $flag = true);

    /**
     * If filter does not match an existing document, insert a single document.
     * The document will be created from newObj if it is a replacement document (i.e. no update operators); otherwise,
     * the operators in newObj will be applied to filter to create the new document.
     *
     * This option is only supported by "update" query
     *
     * @param bool $flag
     * @return $this
     */
    public function upsert(bool $flag = true);

    /**
     * An array of filter documents that determines which array elements to modify for an update operation on an array field.
     *
     * This option is only supported by "update" query
     *
     * @param array $filters
     * @return $this
     *
     * @see https://docs.mongodb.com/manual/reference/command/update/#update-command-arrayfilters
     */
    public function arrayFilters(array $filters);

    /**
     * Delete all matching documents (false), or only the first matching document (true)
     *
     * The configured option is "limit", which is in conflict with `Limitable::limit()` of the "select" query.
     * So use this option only on "delete" query
     *
     * This option is only supported by "delete" query
     *
     * @param bool $flag
     * @return $this
     */
    public function onlyDeleteFirst(bool $flag = true);
}

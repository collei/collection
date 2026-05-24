<?php
namespace Collei\Collections;

/**
 * Methods for sortable collections.
 */
interface SortableInterface
{
	/**
	 * Sort-asc the collection, optionally using a callback.
	 *
	 * @param int|callable $callback = null
	 * @return static
	 */
    public function sort($callback = null);

	/**
	 * Sort the collection by one or more fields or a callback.
	 *
	 * @param string|array|callable $field
	 * @param int $options
	 * @param bool $descending
	 * @return static
	 */
	public function sortBy($field, $options = SORT_REGULAR, $descending = false);

	/**
	 * Sort-desc the collection by one field or a callback.
	 *
	 * @param string|array|callable $field
	 * @param int $options
	 * @return static
	 */
	public function sortByDesc($field, $options = SORT_REGULAR);

	/**
	 * Sort-asc the collection by key, optionally using a callback.
	 *
	 * @param int|callable $callback = null
	 * @return static
	 */
    public function sortByKey($callback = null);

	/**
	 * Sort-desc the collection by key, optionally using a callback.
	 *
	 * @param int|callable $callback = null
	 * @return static
	 */
    public function sortByKeyDesc($options = SORT_REGULAR);

	/**
	 * Sort-desc the collection.
	 *
	 * @param int $options = null
	 * @return static
	 */
    public function sortDesc($options = SORT_REGULAR);
}
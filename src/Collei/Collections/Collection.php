<?php
namespace Collei\Collections;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;
use Closure;
use ArgumentCountError;
use InvalidArgumentException;
use Collei\Collections\Traits\ArrayAccessTrait;
use Collei\Collections\Traits\CollectionTrait;
use Collei\Collections\Exceptions\CollectionException;
use Collei\Collections\Exceptions\ItemNotFoundException;
use Collei\Support\Arr;

/**
 * Reunites array helper functions
 */
class Collection implements CollectionInterface, ArrayAccess, Countable, IteratorAggregate
{
	use ArrayAccessTrait;
	use CollectionTrait;

	/**
	 * @var array
	 */
    private $items = [];

	/**
	 * Initialization.
	 * 
	 * @param iterable $items = []
	 * @param bool $useKeys = false
	 */
    public function __construct(iterable $items = [], bool $useKeys = false)
    {
        $this->items = is_array($items) ? $items : iterator_to_array($items, $useKeys);
    }

	/**
	 * Debug available information.
	 * 
	 * @return array
	 */
	public function __debugInfo()
	{
		return [
			'items' => $this->items
		];
	}



}
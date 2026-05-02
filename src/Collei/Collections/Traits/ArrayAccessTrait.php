<?php
namespace Collei\Collections\Traits;

/**
 * Provide array access abilites.
 * Just need to provide an $items property. 
 */
trait ArrayAccessTrait
{
    /**
     * Tells if offset does exist.
     * 
     * @param int|string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->items);
    }

    /**
     * Returns the item by offset.
     * 
     * @param int|string $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * Sets the item by offset with a value.
     * 
     * @param int|string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->items[$offset] = $value;
    }

    /**
     * Removes the item by offset.
     * 
     * @param int|string $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}
<?php
namespace Collei\Support;

interface Jsonable
{
    /**
     * Returns the instance as a JSON string.
     * 
     * @return string
     */
    public function toJson();
}
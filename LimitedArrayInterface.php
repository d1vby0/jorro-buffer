<?php

namespace Jorro\Buffer;

interface LimitedArrayInterface extends \ArrayAccess
{
    public array $array {
        get;
        set;
    }

    /**
     * @return void
     */
    public function clear(): void;

    /**
     * Incliment array count and truncate array if count reached array size.
     *
     * @return void
     */
    public function incliment(): void;

}
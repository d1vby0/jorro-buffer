<?php

namespace Jorro\Buffer;

/**
 * Size limited array
 *
 * Like a ring buffer, it stores value only within a specified buffer size and provides array access using a key.
 * note: arraySize is a logical count, and truncateSize applies to one-dimensional arrays, so they may not be exact.
 */
class LimitedArray implements LimitedArrayInterface
{
    protected static array $sharedGroups = [];

    public array $array = [];
    protected int $count = 0;

    /**
     * Create shared instance
     *
     * @param  string  $sharedGroup   Shared group name
     * @param  int     $arraySize     Array size
     * @param  int     $truncateSize  Truncates array to this size. If -1 is specified, array_shift is applied.
     *                                If a value is -1  or greater is specified
     *
     * @return static
     */
    public static function newInstance(string $sharedGroup, int $arraySize = 100, int $truncateSize = -1): static
    {
        if ($instance = (static::$sharedGroups[$sharedGroup] ?? null)?->get()) {
            return $instance;
        }
        $instance = new static($arraySize, $sliceSize);
        static::$sharedGroups[$sharedGroup] = \WeakReference::create($instance);

        return $instance;
    }

    /**
     * @param  int  $arraySize  Array size
     * @param  int  $sliceSize  Truncates array to this size. If -1 is specified, array_shift is applied.
     */
    public function __construct(protected int $arraySize = 100, protected int $truncateSize = -1)
    {
        $this->count = $arraySize;
    }

    public function __destruct()
    {
        gc_collect_cycles();
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $this->count = $this->arraySize;
        $this->array = [];
    }

    /**
     * Incliment array count and truncate array if count reached array size.
     *
     * @return void
     */
    public function incliment(): void
    {
        if ($this->count) {
            $this->count--;
        } else {
            if ($this->truncateSize === -1) {
                array_shift($this->array);
            } else {
                $this->array = array_slice($this->array, $this->truncateSize * -1);
                $this->count = $this->arraySize - $this->truncateSize;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->array[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): void
    {
        if (isset($this->array[$offset])) {
            $this->count++;
            unset ($this->array[$offset]);
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->array[$offset] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->incliment();
            $this->array[] = $value;
        } else {
            if (isset($this->array[$offset])) {
                $this->incliment();
            } else {
                unset($this->array[$offset]);
            }
            $this->array[$offset] = $value;
        }
    }
}

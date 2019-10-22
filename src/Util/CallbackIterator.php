<?php

namespace RebelCode\Wpra\Core\Util;

use Iterator;
use OuterIterator;

/**
 * A decorator iterator that passes each value yielded from the inner iterator through a callback before yielding.
 *
 * @since [*next-version*]
 */
class CallbackIterator implements OuterIterator
{
    /**
     * @since [*next-version*]
     *
     * @var Iterator
     */
    protected $inner;

    /**
     * @since [*next-version*]
     *
     * @var callable
     */
    protected $callback;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param Iterator $inner    The inner iterator.
     * @param callable $callback The callback function to run for each element. Receives the original element from the
     *                           inner iterator and the corresponding key as arguments.
     */
    public function __construct(Iterator $inner, callable $callback)
    {
        $this->inner = $inner;
        $this->callback = $callback;
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function getInnerIterator()
    {
        return $this->inner;
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function rewind()
    {
        $this->inner->rewind();
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function key()
    {
        return $this->inner->key();
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function current()
    {
        return call_user_func_array($this->callback, [$this->inner->current(), $this->inner->key()]);
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function next()
    {
        $this->inner->next();
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function valid()
    {
        return $this->inner->valid();
    }
}

<?php

namespace Funda\QueryBundle\Model;

class Entry
{
    /**
     * The key.
     *
     * @var mixed
     */
    private $key;

    /**
     * The display value.
     *
     * @var string
     */
    private $display;

    /**
     * The quantity.
     *
     * @var string
     */
    private $quantity = 0;

    /**
     * Construct.
     *
     * @param mixed $key The key.
     * @param string $display The display value.
     */
    public function __construct($key, $display)
    {
        $this->key = $key;
        $this->display = $display;
    }

    /**
     * Increase the quantity.
     *
     * @param integer $quantity The quantity to increase.
     */
    public function increaseQuantity($quantity = 1)
    {
        $this->quantity += $quantity;
    }

    /**
     * @return string
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
}

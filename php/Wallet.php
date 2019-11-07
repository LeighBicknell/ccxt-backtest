<?php

namespace ccxt\backtest;

/**
 * Store a users funds
 *
 * @category
 * @package
 * @subpackage
 * @author     Leigh Bicknell <leigh@orangeleaf.com>
 * @license    Copyright Orangeleaf Systems Ltd 2013
 * @link       http://orangeleaf.com
 *
 */
class Wallet
{
    protected $name;
    protected $quantity;

    /**
     * initialQuantity
     *
     * This is used purely for calculating profit/loss etc
     *
     * @var float
     */
    protected $initialQuantity;

    public function __construct($name = '', $quantity = 0)
    {
        $this->name = $name;
        $this->quantity = $this->inititalQuantity = $quantity;
    }

    /**
     * Getter for $name
     *
     * return mixed
     * @access public
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Setter for name
     *
     * @param mixed $name Set $name
     *
     * @return self
     * @access public
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }


    /**
     * Getter for $quantity
     *
     * return mixed
     * @access public
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Setter for quantity
     *
     * @param mixed $quantity Set $quantity
     *
     * @return self
     * @access public
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function increment($quantity)
    {
        $this->quantity+= $quantity;
    }

    public function decrement($quantity)
    {
        $this->quantity-= $quantity;
    }
}

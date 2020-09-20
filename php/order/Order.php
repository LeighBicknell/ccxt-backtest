<?php

namespace ccxt\backtest\order;

use ccxt\backtest\Market;
use ccxt\backtest\Wallet;

/**
 * Stores an order
 *
 * @category
 * @package
 * @subpackage
 * @author     Leigh Bicknell <leigh@orangeleaf.com>
 * @license    Copyright Orangeleaf Systems Ltd 2013
 * @link       http://orangeleaf.com
 *
 */
abstract class Order
{
    protected static $count = 0;

    protected $invalidStatuses = array(
        'closed', 'canceled', 'rejected', 'expired'
    );

    protected $id;
    protected $timestamp;
    protected $datetime;
    protected $lastTradeTimestamp;
    protected $status; // open, closed, cancelled
    protected $symbol;
    protected $type;
    protected $side;
    protected $price;
    protected $amount;
    protected $cost;
    protected $filled;
    protected $fee;
    protected $quoteWallet;
    protected $baseWallet;
    protected $market;

    public function __construct(
        Market &$market,
        Wallet &$quoteWallet,
        Wallet &$baseWallet,
        $symbol,
        $side,
        $amount,
        $price = null,
        $params = array()
    ) {
        Order::$count++;
        $this->setId(Order::$count);
        $this->setMarket($market);
        $this->quoteWallet = &$quoteWallet;
        $this->baseWallet = &$baseWallet;
        $this->setSymbol($symbol);
        $this->setSide($side);
        $this->setAmount(round($amount, 8));
        $this->setPrice(round($price, 8));
        $this->init();
    }

    /**
     * Getter for $id
     *
     * return mixed
     * @access public
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter for id
     *
     * @param mixed $id Set $id
     *
     * @return self
     * @access public
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }


    /**
     * Getter for $market
     *
     * return Market
     * @access public
     */
    public function getMarket()
    {
        return $this->market;
    }

    /**
     * Setter for market
     *
     * @param  $market Set $market
     *
     * @return self
     * @access public
     */
    public function setMarket(Market $market)
    {
        $this->market = $market;
        return $this;
    }

    /**
     * Getter for $timestamp
     *
     * return mixed
     * @access public
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Setter for timestamp
     *
     * @param mixed $timestamp Set $timestamp
     *
     * @return self
     * @access public
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * Getter for $datetime
     *
     * return mixed
     * @access public
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * Setter for datetime
     *
     * @param mixed $datetime Set $datetime
     *
     * @return self
     * @access public
     */
    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;
        return $this;
    }


    /**
     * Getter for $lastTradeTimestamp
     *
     * return mixed
     * @access public
     */
    public function getLastTradeTimestamp()
    {
        return $this->lastTradeTimestamp;
    }

    /**
     * Setter for lastTradeTimestamp
     *
     * @param mixed $lastTradeTimestamp Set $lastTradeTimestamp
     *
     * @return self
     * @access public
     */
    public function setLastTradeTimestamp($lastTradeTimestamp)
    {
        $this->lastTradeTimestamp = $lastTradeTimestamp;
        return $this;
    }

    /**
     * Getter for $status
     *
     * return mixed
     * @access public
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Setter for status
     *
     * @param mixed $status Set $status
     *
     * @return self
     * @access public
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Getter for $symbol
     *
     * return mixed
     * @access public
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * Setter for symbol
     *
     * @param mixed $symbol Set $symbol
     *
     * @return self
     * @access public
     */
    public function setSymbol($symbol)
    {
        $this->symbol = $symbol;
        return $this;
    }

    /**
     * Getter for $type
     *
     * return mixed
     * @access public
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Setter for type
     *
     * @param mixed $type Set $type
     *
     * @return self
     * @access public
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Getter for $side
     *
     * return mixed
     * @access public
     */
    public function getSide()
    {
        return $this->side;
    }

    /**
     * Setter for side
     *
     * @param mixed $side Set $side
     *
     * @return self
     * @access public
     */
    public function setSide($side)
    {
        $validSides = array('buy', 'sell');
        if (!in_array($side, $validSides)) {
            throw new \InvalidArgumentException("Invalid side $side");
        }
        $this->side = $side;
        return $this;
    }

    /**
     * Getter for $price
     *
     * return mixed
     * @access public
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Setter for price
     *
     * @param mixed $price Set $price
     *
     * @return self
     * @access public
     */
    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    /**
     * Getter for $amount
     *
     * return mixed
     * @access public
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Setter for amount
     *
     * @param mixed $amount Set $amount
     *
     * @return self
     * @access public
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Getter for $cost
     *
     * return mixed
     * @access public
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Setter for cost
     *
     * @param mixed $cost Set $cost
     *
     * @return self
     * @access public
     */
    public function setCost($cost)
    {
        $this->cost = $cost;
        return $this;
    }

    /**
     * Getter for $filled
     *
     * return mixed
     * @access public
     */
    public function getFilled()
    {
        return $this->filled;
    }

    /**
     * Setter for filled
     *
     * @param mixed $filled Set $filled
     *
     * @return self
     * @access public
     */
    public function setFilled($filled)
    {
        $this->filled = $filled;
        return $this;
    }

    /**
     * Getter for $remaining
     *
     * return mixed
     * @access public
     */
    public function getRemaining()
    {
        return $this->getAmount() - $this->getFilled();
    }

    /**
     * Getter for $fee
     *
     * return mixed
     * @access public
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * Setter for fee
     *
     * @param mixed $fee Set $fee
     *
     * @return self
     * @access public
     */
    public function setFee($fee)
    {
        $this->fee = $fee;
        return $this;
    }

    /**
     * Returns the amount of funds locked up in an order,
     *
     * Not all orders actually lock funds. A stop limit order for example.
     *
     * @return float
     */
    public function getLockedAmount()
    {
        return 0;
    }

    public function getSpendWallet()
    {
        if ($this->getSide() == 'sell') {
            return $this->baseWallet;
        }
        return $this->quoteWallet;
    }

    public function getLockedWallet()
    {
        return $this->getSpendWallet();
    }

    public function getBuyWallet()
    {
        if ($this->getSide() == 'sell') {
            return $this->quoteWallet;
        }
        return $this->baseWallet;
    }

    public function isActive()
    {
        if ($this->getStatus() == 'open') {
            return true;
        }
        return false;
    }

    public function getMinimumSpend()
    {
        return 0;
    }
}

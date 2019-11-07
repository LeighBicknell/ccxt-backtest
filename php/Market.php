<?php

namespace ccxt\backtest;

/**
 * Stores data for an exchange pair OHLCV
 *
 * @category
 * @package
 * @subpackage
 * @author     Leigh Bicknell <leigh@orangeleaf.com>
 * @license    Copyright Orangeleaf Systems Ltd 2013
 * @link       http://orangeleaf.com
 *
 */
class Market
{
    protected $id;
    protected $symbol;
    protected $base;
    protected $quote;
    protected $baseId;
    protected $quoteId;
    protected $active = true;
    protected $precision;
    protected $limits;
        /* in ccxt format:
        return [
            $timestamp,
            floatval ($ohlcv['open']),
            floatval ($ohlcv['max']),
            floatval ($ohlcv['min']),
            floatval ($ohlcv['close']),
            floatval ($ohlcv['volume']),
            floatval ($ohlcv['quoteVolume']), // addedQuote volume because it's a niiice
        ];
         */
    protected $ohlcvv = array();
    protected $currentKey = 0;

    public function increment()
    {
        // If we're already on the last one, return false but leave our pointer
        // where it is
        if ($this->currentKey >= count($this->ohlcvv) - 1) {
            return false;
        }
        $current = $this->ohlcvv[$this->currentKey];
        $this->currentKey++;
        return $current;
    }

    public function reset()
    {
        $this->currentKey = 0;
        return $this->ohlcvv[0];
    }

    public function key()
    {
        return $this->currentKey;
    }

    /**
     * Getter for $ohlcvv
     *
     * return mixed
     * @access public
     */
    public function getOhlcvv($since = null, $limit = null, $restrict_to_past = false)
    {
        // If we have no restrictions just return the lot
        if (!$since && !$limit && !$restrict_to_past) {
            return $this->ohlcvv;
        }

        // Filter our candles
        $ohlcvv = [];
        foreach ($this->olhcvv as $k => $v) {
            if ($since && $v[0] < $since) {
                continue;
            }
            if ($limit && count($ohlcvv) > $limit) {
                break;
            }
            if ($restrict_to_past && $k > $this->currentKey) {
                break;
            }

            $ohlcvv[] = $v;
        }

        return $ohlcvv;
    }

    /**
     * Setter for ohlcvv
     *
     * @param mixed $ohlcvv Set $ohlcvv
     *
     * @return self
     * @access public
     */
    public function setOhlcvv($ohlcvv)
    {
        $this->ohlcvv = $ohlcvv;
        return $this;
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
     * Getter for $base
     *
     * return mixed
     * @access public
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * Setter for base
     *
     * @param mixed $base Set $base
     *
     * @return self
     * @access public
     */
    public function setBase($base)
    {
        $this->base = $base;
        return $this;
    }

    /**
     * Getter for $quote
     *
     * return mixed
     * @access public
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * Setter for quote
     *
     * @param mixed $quote Set $quote
     *
     * @return self
     * @access public
     */
    public function setQuote($quote)
    {
        $this->quote = $quote;
        return $this;
    }

    /**
     * Getter for $baseId
     *
     * return mixed
     * @access public
     */
    public function getBaseId()
    {
        return $this->baseId;
    }

    /**
     * Setter for baseId
     *
     * @param mixed $baseId Set $baseId
     *
     * @return self
     * @access public
     */
    public function setBaseId($baseId)
    {
        $this->baseId = $baseId;
        return $this;
    }

    /**
     * Getter for $quoteId
     *
     * return mixed
     * @access public
     */
    public function getQuoteId()
    {
        return $this->quoteId;
    }

    /**
     * Setter for quoteId
     *
     * @param mixed $quoteId Set $quoteId
     *
     * @return self
     * @access public
     */
    public function setQuoteId($quoteId)
    {
        $this->quoteId = $quoteId;
        return $this;
    }

    /**
     * Getter for $active
     *
     * return bool
     * @access public
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Setter for active
     *
     * @param mixed $active Set $active
     *
     * @return self
     * @access public
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * Getter for $precision
     *
     * return mixed
     * @access public
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * Setter for precision
     *
     * @param mixed $precision Set $precision
     *
     * @return self
     * @access public
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;
        return $this;
    }

    /**
     * Getter for $limits
     *
     * return mixed
     * @access public
     */
    public function getLimits()
    {
        return $this->limits;
    }

    /**
     * Setter for limits
     *
     * @param mixed $limits Set $limits
     *
     * @return self
     * @access public
     */
    public function setLimits($limits)
    {
        $this->limits = $limits;
        return $this;
    }

    public function getTicker()
    {
        return array(
            'symbol' => $this->getSymbol(),
            'timestamp' => $this->getCandleTimestamp(),
            'high' => $this->getCandleHigh(),
            'low' => $this->getCandleLow(),
            'bid' => $this->getCandleClose(),
            'bidVolume' => null,
            'ask' => $this->getCandleClose(),
            'askVolume' => null,
            'vwap' => null,
            'open' => $this->getCandleOpen(),
            'close' => $this->getCandleClose(),
            'last' => $this->getCandleClose(),
            'previousClose' => null,
            'change' => null,
            'percentage' => null,
            'average' => null,
            'baseVolume' => $this->getCandleBaseVolume(),
            //'quoteVolume' => $this->getCandleQuoteVolume()
        );
    }

    public function getCandle()
    {
        return $this->ohlcvv[$this->currentKey];
    }
    public function getCandleTimestamp()
    {
        $candle = $this->getCandle();
        return $candle[0];
    }
    public function getCandleOpen()
    {
        $candle = $this->getCandle();
        return $candle[1];
    }
    public function getCandleHigh()
    {
        $candle = $this->getCandle();
        return $candle[2];
    }
    public function getCandleLow()
    {
        $candle = $this->getCandle();
        return $candle[3];
    }
    public function getCandleClose()
    {
        $candle = $this->getCandle();
        return $candle[4];
    }
    public function getCandleBaseVolume()
    {
        $candle = $this->getCandle();
        return $candle[5];
    }
    public function getCandleQuoteVolume()
    {
        // @FIXME
        $candle = $this->getCandle();
        return $candle[6];
    }
}

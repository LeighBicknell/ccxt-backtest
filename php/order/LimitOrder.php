<?php

namespace ccxt\backtest\order;

use ccxt\InsufficientFunds;

/**
 * Class LimitOrder
 *
 * For a limit order $amount is the amount you want to buy/sell
 *
 * @see Order
 */
class LimitOrder extends Order
{
    protected $type = 'limit';

    protected function init()
    {
        $this->setStatus('open');

        $wallet = $this->getSpendWallet();
        $decrementAmount = $this->getLockedAmount();

        if ($wallet->getQuantity() < $decrementAmount) {
            throw new InsufficientFunds();
        }

        // @TODO implement minimum order test


        $wallet->decrement($decrementAmount);
    }

    public function process()
    {
        if (in_array($this->getStatus(), $this->invalidStatuses)) {
            // @TODO return whatever ccxt would normally return
            return false;
        }

        if ($this->getSide() == 'buy') {
            return $this->processBuy();
        }
        return $this->processSell();
    }

    protected function processBuy()
    {

        // @TODO come up with a better way of customizing this logic without
        // having to extend our Orders once again
        // We could pass in a 'BacktestingOrderLogic' class which handles all
        // of the logic for us.
        // Or we could allow passing in of an executable that contains the
        // logic. Or maybe something else.

        $candle = $this->market->getCandle();
        if ($this->market->getCandleLow() >= $this->getPrice()) {
            return false;
        }

        // If we got this far the order has been filled
        $this->setStatus('closed');
        $filled = $this->getAmount();
        $this->baseWallet->increment($filled);
        $this->setFilled($filled);
        $this->setCost($this->getAmount() * $this->getPrice());
        $this->setLastTradeTimestamp($this->market->getCandleTimestamp());
    }

    protected function processSell()
    {
        $candle = $this->market->getCandle();
        if ($this->market->getCandleHigh() <= $this->getPrice()) {
            return false;
        }

        // If we got this far the order has been filled
        $this->setStatus('closed');
        $filled = $this->getAmount() * $this->getPrice();
        $this->quoteWallet->increment($filled);
        $this->setFilled($filled);
        $this->setCost($this->getAmount() * $this->getPrice());
        $this->setLastTradeTimestamp($this->market->getCandleTimestamp());
    }

    public function cancel()
    {
        if (in_array($order->getStatus(), $this->invalidStatuses)) {
            // @TODO return whatever ccxt would normally return
            return false;
        }

        $this->setStatus('cancelled');
        switch ($order->getSide()) {
            case 'buy':
                $this->quoteWallet->increment($order->getRemaining());
                break;
            case 'sell':
                $this->baseWallet->increment($order->getRemaining());
        }

        return $this;
    }

    public function getLockedAmount()
    {
        if (!$this->isActive()) {
            return 0;
        }
        if ($this->getSide() == 'sell') {
            return $this->getAmount();
        }
        return $this->getAmount() * $this->getPrice();
    }
}

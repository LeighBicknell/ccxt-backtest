<?php

namespace ccxt\backtest;

class LimitOrder extends Order
{

    protected function init()
    {
        $wallet = $baseWallet;
        if ($this->getSide() == 'buy') {
            $wallet = $quoteWallet;
        }

        if ($wallet->getQuantity() < $this->getAmount()) {
            throw new InsufficientFunds();
        }

        // @TODO implement minimum order test

        $wallet->decrement($amount);
        $this->setStatus('open');
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
        $candle = $this->market->getCandle();
        // @TODO come up with a better way of customizing this logic without
        // having to extend our Orders once again
        // We could pass in a 'BacktestingOrderLogic' class which handles all
        // of the logic for us.
        // Or we could allow passing in of an executable that contains the
        // logic. Or maybe something else.
        if ($this->market->getCandleMin() >= $this->getPrice()) {
            return false;
        }

        // If we got this far the order has been filled
        $this->setStatus('filled');
        $filled = $this->getAmount();
        $this->baseWallet->increment($filled);
        $this->setFilled($filled);
    }

    protected function processSell()
    {
        $candle = $this->market->getCandle();
        // @TODO come up with a better way of customizing this logic without
        // having to extend our Orders once again
        // We could pass in a 'BacktestingOrderLogic' class which handles all
        // of the logic for us.
        // Or we could allow passing in of an executable that contains the
        // logic. Or maybe something else.
        if ($this->market->getCandleMax() <= $this->getPrice()) {
            return false;
        }

        // If we got this far the order has been filled
        $this->setStatus('filled');
        $filled = $this->getAmount() * $this->getPrice();
        $this->quoteWallet->increment($filled);
        $this->setFilled($filled);
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
}

<?php

namespace ccxt\backtest;

use ccxt\InsufficientFunds;

class LimitOrder extends Order
{

    protected function init()
    {
        $this->setStatus('open');

        $wallet = $this->getLockedWallet();
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
        $candle = $this->market->getCandle();

        // @TODO come up with a better way of customizing this logic without
        // having to extend our Orders once again
        // We could pass in a 'BacktestingOrderLogic' class which handles all
        // of the logic for us.
        // Or we could allow passing in of an executable that contains the
        // logic. Or maybe something else.
        if ($this->market->getCandleLow() >= $this->getPrice()) {

            return false;
        }

        // @FIXME If user creates a limit buy at a price way above the current
        // market price, it is essentially just a market order, and should be
        // filled at current price

        // If we got this far the order has been filled
        $this->setStatus('closed');
        $filled = $this->getAmount();
        $this->baseWallet->increment($filled);
        $this->setFilled($filled);
        echo "Filled buy $filled\r\n";

        // DEBUG
        echo "\r\n<pre><!-- \r\n";
        $DBG_DBG = debug_backtrace();
        foreach ($DBG_DBG as $DD) {
            echo implode(':', array(@$DD['file'], @$DD['line'], @$DD['function'])) . "\r\n";
        }
        echo " -->\r\n";
        var_dump($this->market->getTicker());
        echo "</pre>\r\n";

    }

    protected function processSell()
    {
        $candle = $this->market->getCandle();
        // @TODO come up with a better way of customizing this logic without
        // having to extend our Orders once again
        // of the logic for us.
        // Or we could allow passing in of an executable that contains the
        // logic. Or maybe something else.
        if ($this->market->getCandleHigh() <= $this->getPrice()) {
            return false;
        }

        // If we got this far the order has been filled
        $this->setStatus('closed');
        $filled = $this->getAmount() * $this->getPrice();
        $this->quoteWallet->increment($filled);
        $this->setFilled($filled);
        echo "Filled sell $filled\r\n";

        // DEBUG
        echo "\r\n<pre><!-- \r\n";
        $DBG_DBG = debug_backtrace();
        foreach ($DBG_DBG as $DD) {
            echo implode(':', array(@$DD['file'], @$DD['line'], @$DD['function'])) . "\r\n";
        }
        echo " -->\r\n";
        var_dump($this->market->getTicker());
        echo "</pre>\r\n";

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

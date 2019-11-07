<?php

namespace ccxt\backtest\order;

use ccxt\InsufficientFunds;

/**
 * Class MarketOrder
 *
 * A market order is fired on the current candle, it doesn't wait for
 * increment()
 *
 * @see Order
 */
class MarketOrder extends Order
{
    protected $type = 'market';

    protected function init()
    {
        $this->setStatus('open');

        // @TODO implement minimum order test
        $wallet = $this->getSpendWallet();
        if ($wallet->getQuantity() < $this->getMinimumSpend()) {
            throw new InsufficientFunds();
        }

        // Market orders are processed immediately
        $this->process();
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

        $spendAmount = $this->getAmount() * $this->getPrice();
        if ($this->getSpendWallet()->getQuantity() < $spendAmount) {
            throw new InsufficientFunds();
        }

        // If we got this far the order has been filled
        $this->setStatus('closed');
        $this->quoteWallet->decrement($spendAmount);
        $filled = $this->getAmount();
        $this->baseWallet->increment($filled);
        $this->setFilled($filled);
        $this->setCost($this->getAmount() * $this->getPrice());
        $this->setLastTradeTimestamp($this->market->getCandleTimestamp());
    }

    protected function processSell()
    {
        $price = $this->market->getCandleClose();
        if ($this->getSpendWallet()->getQuantity() < $this->getAmount()) {
            throw new InsufficientFunds();
        }

        // If we got this far the order has been filled
        $this->setStatus('closed');
        $filled = $this->getAmount() * $this->getPrice();
        $this->baseWallet->decrement($this->getAmount());
        $this->quoteWallet->increment($filled);
        $this->setFilled($filled);
        $this->setCost($this->getAmount() * $this->getPrice());
        $this->setLastTradeTimestamp($this->market->getCandleTimestamp());
    }

    public function cancel()
    {
        // Can't cancel a market order
        // @TODO return whatever ccxt would normally return
        return false;
    }

    public function getPrice()
    {
        // @TODO Allow users to customize this price
        return $this->market->getCandleClose();
    }
}

<?php

namespace ccxt\backtest\order;

use ccxt\backtest\Market;
use ccxt\backtest\Wallet;

class OrderFactory
{
    public function build(
        $type,
        Market $market,
        Wallet $quoteWallet,
        Wallet $baseWallet,
        $symbol,
        $side,
        $amount,
        $price = null,
        $params = []
    ) {
        $class = __NAMESPACE__.'\\'.ucwords($type).'Order';
        return new $class($market, $quoteWallet, $baseWallet, $symbol, $side, $amount, $price, $params);
    }
}

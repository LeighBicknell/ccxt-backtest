<?php

use ccxt\backtest\BacktestExchange;
use ccxt\backtest\MarketFactory;
use ccxt\backtest\Wallet;

include_once 'bootstrap.php';

// Load up some data from a real exchange
$exchange = new \ccxt\gdax();
$exchange->urls['api'] = 'https://api-public.sandbox.gdax.com';
$exchange->load_markets();
$market = $exchange->markets['BTC/USD'];
$ohlcvv = $exchange->fetchOHLCV($market['symbol'], '1h');

// Load the data into our backtesting exchange
$testMarkets[$market['symbol']] = MarketFactory::buildFromCCXTMarket($market, $ohlcvv);
$exchange = new BacktestExchange();
$exchange->setBacktestMarkets($testMarkets);
$exchange->loadMarkets();
$usdWallet = new Wallet();
$usdWallet->setName('USD');
$usdWallet->increment(1000);
$btcWallet = new Wallet();
$btcWallet->setName('BTC');
$exchange->setBacktestWallets([$usdWallet, $btcWallet]);

$continue = true;
while ($continue) {
    // Our backtest logic
    foreach ($exchange->markets as $symbol => $market) {
        $funds = $exchange->fetchBalance();


        if ($funds['free'][$market['quote']] >= 500) {
            $exchange->createOrder($symbol, 'limit', 'buy', 1, 500);
        }
    }


    if (method_exists($exchange, 'increment')) {
        $continue = $exchange->increment();
        $i++;
        echo $i."\r\n";
    }
}

// DEBUG
echo "\r\n<pre><!-- \r\n";
$DBG_DBG = debug_backtrace();
foreach ($DBG_DBG as $DD) {
    echo implode(':', array(@$DD['file'], @$DD['line'], @$DD['function'])) . "\r\n";
}
echo " -->\r\n";
var_dump($funds);
var_dump($exchange->fetchTicker('BTC/USD'));
echo "</pre>\r\n";
die();


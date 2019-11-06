<?php

use ccxt\backtest\BacktestExchange;
use ccxt\backtest\MarketFactory;
use ccxt\backtest\Wallet;

include_once 'bootstrap.php';

// Load up some data from a real exchange
$exchange = new \ccxt\coinbasepro();
// Note this data is extremely inaccurate!
//$exchange->urls['api'] = 'https://api-public.sandbox.pro.coinbase.com';
$exchange->load_markets();
$market = $exchange->markets['BTC/USD'];

$ohlcvv = $exchange->fetch_ohlcv($market['symbol'], '1d', strtotime('2019-01-01')*1000);

// Load the data into our backtesting exchange
$testMarkets[$market['symbol']] = MarketFactory::buildFromCCXTMarket($market, $ohlcvv);
$exchange = new BacktestExchange();
$exchange->setBacktestMarkets($testMarkets);
$exchange->loadMarkets();
$usdWallet = new Wallet();
$usdWallet->setName('USD');
$usdWallet->increment(20000);
$btcWallet = new Wallet();
$btcWallet->setName('BTC');
$exchange->setBacktestWallets([$usdWallet, $btcWallet]);

$continue = true;
$i = 0;
$orders = [];
while ($continue) {
    // Our backtest logic (just backtest one market for now)
    $symbol = $market['symbol'];
    $market = $exchange->markets[$symbol];

    $funds = $exchange->fetchBalance();
    $orders[$symbol] = ['buy' => [], 'sell' => []];
    $ccxtMarket = $exchange->getBacktestMarket($market['symbol']);

    // If we have > $500
    if ($funds['free'][$market['quote']] >= 5000) {
        // Create a buy limit order
        $orders['buy'][] = $exchange->createOrder($symbol, 'limit', 'buy', 1, 5000);
        echo "Create buy limit order for 1 at 5000\r\n";
    } elseif ($funds['free'][$market['base']] > 1) {
        $orders['sell'][] = $exchange->createOrder($symbol, 'limit', 'sell', 1, 10000);
        echo "Create sell limit order for 1 at 10000\r\n";
    }


    if (method_exists($exchange, 'increment')) {
        $i++;
        $continue = $exchange->increment();

        echo "Wallets: \r\n";
        var_dump($exchange->getBacktestWallets());

        //echo "Orders: \r\n";
        //var_dump($exchange->getBacktestOrders());

        echo "Ticker: \r\n";
        var_dump($ccxtMarket->getTicker());

        echo "BALANCE: \r\n";
        var_dump($exchange->fetchBalance());
        echo "\r\n\r\n\r\n".$i."\r\n\r\n\r\n";
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
//var_dump($orders);
echo "</pre>\r\n";
die();


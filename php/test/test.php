<?php

use ccxt\backtest\BacktestExchange;
use ccxt\backtest\MarketFactory;

include_once 'bootstrap.php';

$exchange = new \ccxt\gdax();
$exchange->urls['api'] = 'https://api-public.sandbox.gdax.com';

$exchange->load_markets();

$market = $exchange->markets['BTC/USD'];

$ohlcvv = $exchange->fetchOHLCV($market['symbol'], '1h');

$testMarket = MarketFactory::buildFromCCXTMarket($market, $ohlcvv);
$testExchange = new BacktestExchange();
$testExchange->setBacktestMarkets(array($testMarket));

$testExchange->loadMarkets();

// DEBUG
echo "\r\n<pre><!-- \r\n";
$DBG_DBG = debug_backtrace();
foreach ($DBG_DBG as $DD) {
    echo implode(':', array(@$DD['file'], @$DD['line'], @$DD['function'])) . "\r\n";
}
echo " -->\r\n";
var_dump($testExchange->fetchTicker($testMarket->getSymbol()));
echo "</pre>\r\n";
die();


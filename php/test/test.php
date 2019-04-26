<?php

use ccxt\backtest\MarketFactory;

include_once 'bootstrap.php';

$exchange = new \ccxt\gdax();
$exchange->urls['api'] = 'https://api-public.sandbox.gdax.com';

$exchange->load_markets();

$market = $exchange->markets['BTC/USD'];

$ohlcvv = $exchange->fetchOHLCV($market['symbol'], '1h');

$backtestMarket = MarketFactory::buildFromCCXTMarket($market, $ohlcvv);



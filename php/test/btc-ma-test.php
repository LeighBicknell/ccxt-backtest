<?php

use ccxt\backtest\BacktestExchange;
use ccxt\backtest\MarketFactory;
use ccxt\backtest\order\OrderFactory;
use ccxt\backtest\renderer\ChartRenderer;
use ccxt\backtest\Wallet;

include_once 'bootstrap.php';


// Load up some data from a real exchange
$exchange = new \ccxt\coinbasepro();
$exchange->load_markets();
$market = $exchange->markets['BTC/USD'];


$ohlcvv = $exchange->fetch_ohlcv($market['symbol'], '1d', strtotime('-900 days', strtotime('2019-01-01')) * 1000);
$ohlcvv2 = $exchange->fetch_ohlcv($market['symbol'], '1d', strtotime('-600 days', strtotime('2019-01-01')) * 1000);
$ohlcvv3 = $exchange->fetch_ohlcv($market['symbol'], '1d', strtotime('-300 days', strtotime('2019-01-01')) * 1000);
$ohlcvv4 = $exchange->fetch_ohlcv($market['symbol'], '1d', strtotime('2019-01-01')*1000);

$ohlcvv = array_merge($ohlcvv, $ohlcvv2, $ohlcvv3, $ohlcvv4);

// Load the data into our backtesting exchange
$testMarkets[$market['symbol']] = MarketFactory::buildFromCCXTMarket($market, $ohlcvv);
$exchange = new BacktestExchange(new OrderFactory());
$exchange->setBacktestMarkets($testMarkets);
$exchange->loadMarkets();
$usdWallet = new Wallet('USD', 20000);
$btcWallet = new Wallet('BTC');
$exchange->setBacktestWallets([$usdWallet, $btcWallet]);

$continue = true;
$i = 0;
$orders = ['buy' => [], 'sell' => []];
$symbol = $market['symbol'];
$tickers = [];
$prices = [];
$maLength = 100;
// @TODO Switch this to market orders and just buy percentages
$botOrders = [
    [
        'buyAt' => 0.9,
        'sellAt' => 1.5,
        'quantity' => 0.5,
        'side' => 'buy',
        'id' => null
    ],
    [
        'buyAt' => 0.8,
        'sellAt' => 2,
        'quantity' => 0.5,
        'side' => 'buy',
        'id' => null
    ],
];
$maSeriesData = [];
while ($continue) {
    // This should be the only backtest specific code in this loop:
    if (method_exists($exchange, 'increment')) {
        $i++;
        $continue = $exchange->increment();
    }

    // Treat this like a real bot, we wouldn't get the entire ohlcvv from the
    // exchange on every loop, we'd just append to what we already have
    $ticker = $exchange->fetchTicker($symbol);
    $tickers[] = [$ticker['timestamp'], $ticker['open'], $ticker['high'], $ticker['low'], $ticker['close'], $ticker['baseVolume']];
    $prices[] = $ticker['close'];

    $mas = trader_ma($prices, $maLength);
    if (!$mas) {
        continue;
    }
    $ma = end($mas);
    $maSeriesData[$ticker['timestamp']] = $ma;

    $funds = $exchange->fetchBalance();
    foreach ($botOrders as $k => &$botOrder) {
        if ($botOrder['id']) {
            $order = $exchange->fetchOrder($botOrder['id']);

            // Check if the order has been filled
            if ($order['status'] == 'closed') {

                echo $k.' '.$botOrder['side'].' '.$order['amount'].' '.$order['price']."<br/>";

                // The order has succeeded
                // Clear the id
                $botOrder['id'] = null;

                // Swap sides
                $side = 'buy';
                if ($botOrder['side'] == 'buy') {
                    $side = 'sell';
                }
                $botOrder['side'] = $side;
            } else {
                // Cancel the order so we can re-make it with the new MA.
                $exchange->cancelOrder($order['id']);
                $botOrder['id'] = null;
            }
        }
        // @FIXME there's a bug somewhere in the order logic, we're selling
        // more BTC than we ever bought!
        if (!$botOrder['id']) {
            $side = $botOrder['side'];
            $rate = $botOrder['buyAt'] * $ma;
            $quoteBase = 'quote';
            if ($side == 'sell') {
                $rate = $botOrder['sellAt'] * $ma;
                $quoteBase = 'base';
            }
            $wallet = $market[$quoteBase];
            // We subtract 1 from our wallet because of rounding issues
            $quantity = ($funds[$wallet]['total'] - 0.01) * $botOrder['quantity'];
            if ($botOrder['side'] == 'buy') {
                $quantity = round($quantity / $rate, 6, PHP_ROUND_HALF_DOWN);
            }

            $order = $exchange->createOrder($symbol, 'limit', $botOrder['side'], $quantity, $rate);
            $botOrder['id'] = $order['id'];
        }
    }
}

$chartRenderer = new ChartRenderer();
$maSeries = [
    //'data' => $maSeriesData,
    'type' => 'sma',
    'name' => '100 SMA',
    'linkedTo' => 'ohlc',
    'params' => ['period' => 100]
];
$chartRenderer->addSeries($maSeries, $market['symbol']);

?>
<html>
    <head>
        <title>OHLC</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    </head>
    <body>
    <?php echo $chartRenderer->render($exchange); ?>

<?php
// DEBUG
echo "\r\n<pre><!-- \r\n";
$DBG_DBG = debug_backtrace();
foreach ($DBG_DBG as $DD) {
    echo implode(':', array(@$DD['file'], @$DD['line'], @$DD['function'])) . "\r\n";
}
echo " -->\r\n";
var_dump($exchange->fetchBalance());
echo "</pre>\r\n";
?>
    </body>
</html>


<?php

use ccxt\backtest\BacktestExchange;
use ccxt\backtest\MarketFactory;
use ccxt\backtest\Wallet;
use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;

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
    } elseif ($funds['free'][$market['base']] > 1) {
        $orders['sell'][] = $exchange->createOrder($symbol, 'limit', 'sell', 1, 10000);
    }


    if (method_exists($exchange, 'increment')) {
        $i++;
        $continue = $exchange->increment();

        //echo "Wallets: \r\n";
        //var_dump($exchange->getBacktestWallets());

        ////echo "Orders: \r\n";
        ////var_dump($exchange->getBacktestOrders());

        //echo "Ticker: \r\n";
        //var_dump($ccxtMarket->getTicker());

        //echo "BALANCE: \r\n";
        //var_dump($exchange->fetchBalance());
        //echo "\r\n\r\n\r\n".$i."\r\n\r\n\r\n";
    }
}

/*
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
 */




$chart = new Highchart(Highchart::HIGHSTOCK);
$chart->chart->renderTo = "container";
$chart->title->text = "Backtest test";

$chart->yAxis[] = [
    'labels' => [
        'align' => 'left'
    ],
    'height' => '80%',
    'resize' => [
        'enabled' => true
    ]
];
$chart->yAxis[] = [
    'labels' => [
        'align' => 'left'
    ],
    'top' => '80%',
    'height' => '20%',
    'offset' => 0
];

$ohlc = [];
$volume = [];
foreach ($ohlcvv as $v) {
    $ohlc[] = [
        $v[0],
        $v[1],
        $v[2],
        $v[3],
        $v[4]
    ];
    $volume[] = [$v[0], $v[5]];
}

$orders = [];
foreach ($exchange->getBacktestOrders() as $order) {
    $orders[$order->getSide()][] = [
        'x' => $order->getLastTradeTimestamp(),
        'title' => $order->getSide(),
        'text' => "
            Amount: {$order->getAmount()}\r\n
            Cost: {$order->getCost()}\r\n
        "
    ];
}

usort($orders['buy'], function($a, $b) {
    return $a['x'] - $b['x'];
});
usort($orders['sell'], function($a, $b) {
    return $a['x'] - $b['x'];
});
// ohlc
$chart->series[] = [
    'id' => 'ohlc',
    'type' => "ohlc",
    'name' => "BTC/USD Backtest",
    'data' => $ohlc,
];
// volume
$chart->series[] = [
    'type' => "column",
    'name' => "BTC/USD Backtest",
    'data' => $volume,
    'yAxis' => 1
];
$chart->series[] = [
    'onKey' => 'high',
    'type' => "flags",
    'data' => $orders['buy'],
    'shape' => 'flag',
    'onSeries' => 'ohlc',
    'allowOverlapX' => true
];
$chart->series[] = [
    'onKey' => 'low',
    'type' => "flags",
    'data' => $orders['sell'],
    'shape' => 'flag',
    'onSeries' => 'ohlc',
    'allowOverlapX' => true
];
?>
<html>
    <head>
        <title>OHLC</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <?php $chart->printScripts(); ?>
    </head>
    <body>
        <div id="container"></div>
        <script type="text/javascript">
            <?php echo $chart->render("chart"); ?>
        </script>
    </body>
</html>

<?php

namespace ccxt\backtest\renderer;

use ccxt\backtest\BacktestExchange;
use ccxt\backtest\Market;
use Ghunti\HighchartsPHP\Highchart;
use Ghunti\HighchartsPHP\HighchartJsExpr;

class ChartRenderer
{
    /**
     * render
     *
     * @param BacktestExchange $exchange
     *
     * @return string
     * @access public
     */
    public function render(BacktestExchange $exchange)
    {
        $charts = '';
        foreach ($exchange->getBacktestMarkets() as $market) {
            $charts.= $this->renderMarket($exchange, $market);
        }
        return $charts;
    }

    protected function renderMarket(BacktestExchange $exchange, Market $market)
    {
        // Fetch the candles
        $ohlcvv = $market->getOhlcvv();

        // Separate volume and ohlc into sep arrays
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

        // Structure the orders data
        $orders = ['buy' => [], 'sell' => []];
        foreach ($exchange->getBacktestOrders() as $order) {


            if ($order->getMarket() == $market && $order->getStatus() == 'closed') {
                $orders[$order->getSide()][] = [
                    'x' => $order->getLastTradeTimestamp(),
                    'title' => $order->getSide(),
                    'text' => "Type: {$order->getType()}<br/>
                                Amount: {$order->getAmount()}<br/>
                                Cost: {$order->getCost()}<br/>"
                ];
            }
        }


        usort($orders['buy'], function ($a, $b) {
            return $a['x'] - $b['x'];
        });
        usort($orders['sell'], function ($a, $b) {
            return $a['x'] - $b['x'];
        });



        // Random ID for the container
        $chartId = 'chart-'.rand(1, 99999);

        // Build the chart
        $chart = new Highchart(Highchart::HIGHSTOCK);
        $chart->chart->renderTo = $chartId;
        $chart->title->text = $market->getSymbol();

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

        // Create the chart series
        // ohlc
        $chart->series[] = [
            'id' => 'ohlc',
            'type' => "ohlc",
            'name' => $market->getSymbol()." Backtest",
            'data' => $ohlc,
        ];
        // volume
        $chart->series[] = [
            'type' => "column",
            'name' => $market->getSymbol()." Backtest",
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

        $container = '<div id="'.$chartId.'"></div>';

        return $chart->printScripts(true).$container.$chart->render(null, null, true);
    }
}

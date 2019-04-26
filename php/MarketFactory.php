<?php

namespace ccxt\backtest;

class MarketFactory
{
    public static function buildFromCCXTMarket($market, $ohlcvv)
    {
        $backtestMarket = new Market();
        $backtestMarket->setId($market['id']);
        $backtestMarket->setSymbol($market['symbol']);
        $backtestMarket->setBase($market['base']);
        $backtestMarket->setQuote($market['quote']);
        $backtestMarket->setActive($market['active']);
        $backtestMarket->setPrecision($market['precision']);
        $backtestMarket->setLimits($market['limits']);
        $backtestMarket->setOhlcvv($ohlcvv);
        return $backtestMarket;
    }
}

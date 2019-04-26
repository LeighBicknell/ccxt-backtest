<?php

namespace ccxt\backtest;

use ccxt\Exchange;
use ccxt\NotSupported;

class BacktestExchange extends Exchange
{
    protected $backtestOrders = array();
    protected $backtestWallets = array();
    protected $backtestMarkets = array();

    public function __construct($options = array())
    {
        parent::__construct($options = array());
        // Load the markets so we don't have to do it at the top of all of the
        // method calls
        $this->loadMarkets();
    }

    /**
     * setMarkets
     *
     * Assigns an array of pre-configured market objects to backtest against.
     *
     * @param Market[] $markets Array of Market objects
     *
     * @return void
     * @throws [ExceptionClass] [Description]
     * @access
     */
    public function setMarkets(array $markets)
    {
        foreach ($markets as $k => $v) {
            $this->backtestMarkets[$v->getSymbol()] = $market;
        }
    }

    public function describe() {
        return array_replace_recursive (parent::describe (), array (
            'id' => 'backtest',
            'name' => 'BacktestExchange',
            'countries' => array ('US'),
            'version' => 'v0.1',
            'rateLimit' => 0,
            // new metainfo interface
            'has' => array (
                'CORS' => false,
                'createMarketOrder' => false,
                'fetchDepositAddress' => false,
                'fetchClosedOrders' => false,
                'fetchCurrencies' => false,
                'fetchMyTrades' => false,
                'fetchOHLCV' => false,
                'fetchOrder' => true,
                'fetchOpenOrders' => false,
                'fetchTickers' => true,
                'withdraw' => false,
            ),
            'fees' => array (
                'trading' => array (
                    'tierBased' => false,
                    'percentage' => true,
                    'maker' => 0.0025,
                    'taker' => 0.0025,
                ),
                'funding' => array (
                    'tierBased' => false,
                    'percentage' => false,
                    'withdraw' => array (
                        'BTC' => 0.001,
                        'LTC' => 0.01,
                        'DOGE' => 2,
                        'VTC' => 0.02,
                        'PPC' => 0.02,
                        'FTC' => 0.2,
                        'RDD' => 2,
                        'NXT' => 2,
                        'DASH' => 0.002,
                        'POT' => 0.002,
                    ),
                    'deposit' => array (
                        'BTC' => 0,
                        'LTC' => 0,
                        'DOGE' => 0,
                        'VTC' => 0,
                        'PPC' => 0,
                        'FTC' => 0,
                        'RDD' => 0,
                        'NXT' => 0,
                        'DASH' => 0,
                        'POT' => 0,
                    ),
                ),
            ),
            'options' => array (
                // price precision by quote currency code
                'pricePrecisionByCode' => array (
                    'USD' => 3,
                ),
                'parseOrderStatus' => false,
                'hasAlreadyAuthenticatedSuccessfully' => false, // a workaround for APIKEY_INVALID
            ),
            'commonCurrencies' => array (
                'BITS' => 'SWIFT',
                'CPC' => 'CapriCoin',
            ),
        ));
    }

    public function fetchTicker($symbol, $params = array())
    {
        $market = $this->market($symbol);
        $ticker = $this->backtestMarkets[$symbol]->getTicker();
        return $this->parseTicker($ticker, $market);
    }

    /**
     * If we are loading our data from an exchange using CCXT then technically
     * this isn't needed at all... however we'll throw it in to allow us to
     * load our data from elsewhere and have a unified format
     *
     * @param mixed $ticker
     * @param mixed $market
     *
     * @return array
     * @access public
     */
    public function parseTicker($ticker, $market = null)
    {
        $symbol = $ticker['symbol'];
        $timestamp = $this->parse8601($ticker['timestamp']);
        $baseVolume = $this->safe_float($ticker, 'baseVolume');
        $quoteVolume = $this->safe_float($ticker, 'volumeQuote');
        $open = $this->safe_float($ticker, 'open');
        $last = $this->safe_float($ticker, 'last');
        $change = null;
        $percentage = null;
        $average = null;
        if ($last !== null && $open !== null) {
            $change = $last - $open;
            $average = $this->sum ($last, $open) / 2;
            if ($open > 0)
                $percentage = $change / $open * 100;
        }
        $vwap = null;
        if ($quoteVolume !== null)
            if ($baseVolume !== null)
                if ($baseVolume > 0)
                    $vwap = $quoteVolume / $baseVolume;
        return array (
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'high' => $this->safe_float($ticker, 'high'),
            'low' => $this->safe_float($ticker, 'low'),
            'bid' => $this->safe_float($ticker, 'bid'),
            'bidVolume' => null,
            'ask' => $this->safe_float($ticker, 'ask'),
            'askVolume' => null,
            'vwap' => $vwap,
            'open' => $open,
            'close' => $last,
            'last' => $last,
            'previousClose' => null,
            'change' => $change,
            'percentage' => $percentage,
            'average' => $average,
            'baseVolume' => $baseVolume,
            'quoteVolume' => $quoteVolume,
            'info' => $ticker,
        );
    }

    public function fetchOrder($id, $symbol = null, $params = array())
    {
        return $this->parseOrder($this->backtestOrders[$id]);
    }

    public function parseOrder($order)
    {
        return array(
            'id' => $order->getId(),
            'timestamp' => $order->getTimestamp(),
            'datetime' => $order->getDatetime(),
            'lastTradeTimestamp' => $order->getLastTradeTimestamp(),
            'status' => $order->getStatus(),
            'symbol' => $order->getSymbol(),
            'type' => $order->getType(),
            'side' => $order->getSide(),
            'price' => $order->getPrice(),
            'amount' => $order->getAmount(),
            'cost' => $order->getCost(),
            'filled' => $order->getFilled(),
            'remaining' => $order->getRemaining(),
            'fee' => $order->getFee(),
            'info' => array()
        );
    }

    public function fetchBalance($params = array())
    {
        /* @TODO
         *
            indexed by availability of funds first, then by currency

            'free':  {           // money, available for trading, by currency
                'BTC': 321.00,   // floats...
                'USD': 123.00,
                ...
            },

            'used':  { ... },    // money on hold, locked, frozen, or pending, by currency

            'total': { ... },    // total (free + used), by currency

            //-------------------------------------------------------------------------
            // indexed by currency first, then by availability of funds

            'BTC':   {           // string, three-letter currency code, uppercase
                'free': 321.00   // float, money available for trading
                'used': 234.00,  // float, money on hold, locked, frozen or pending
                'total': 555.00, // float, total balance (free + used)
            },

            'USD':   {           // ...
                'free': 123.00   // ...
                'used': 456.00,
                'total': 579.00,
            },
        */
    }

    public function cancelOrder($id, $symbol = null, $params = array())
    {
        $order = $this->getOrderById($id);
        $order->cancel();
        return $this->parseOrder($order);
    }

    public function createOrder($symbol, $type, $side, $amount, $price = null, $params = array())
    {
        // Validate parameters
        if (!$backtestMarket = $this->getBacktestMarket($symbol)) {
            throw new \InvalidArgumentException("Market $symbol does not exist");
            return false;
        }

        $validTypes = array('limit');
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("$type order not supported");
        }

        $baseWallet = $this->getBacktestWallet($backtestMarket->getBase());
        $quoteWallet = $this->getBacktestWallet($backtestMarket->getQuote());

        // @TODO consider a factory when we start supporting different order
        // types
        $order = new LimitOrder($quoteWallet, $baseWallet, $symbol, $type, $side, $amount, $price, $params);

        $this->backtestOrders[$order->getId()] = $order;
        return $this->parseOrder($order);
    }

    public function fetchMarkets()
    {
        $results = array();
        foreach ($this->backtestMarkets as $market) {
            $results[] = array(
                'id' => $market->getId(),
                'symbol' => $market->getSymbol(),
                'base' => $market->getBase(),
                'quote' => $market->getQuote(),
                'baseId' => $market->getBaseId(),
                'quoteId' => $market->getQuoteId(),
                'active' => $market->isActive(),
                'precision' => $market->getPrecision(),
                'limits' => $market->getLimits()
            );
        }
        return $results;
    }

    public function loadMarkets()
    {
        return $this->load_markets();
    }

    public function fetchOHLCV($symbol, $timeframe = 'default', $since = null, $limit = null, $params = array ())
    {

    }

    /**
     * increment
     *
     *
     * @return void
     * @throws [ExceptionClass] [Description]
     * @access
     */
    public function increment($count = 1)
    {
        // Move each of the markets forwards one candle
        foreach ($this->backtestMarkets as $market) {
            $market->increment();
        }
        // Now calculate any triggered orders and update wallet values
        $this->processBacktestOrders();
    }

    public function __call($name, $args = array())
    {
        $name = str_replace('_', '', ucwords($key, '_'));
        if (method_exists($this, $name)) {
            return call_user_func_array($name, $args);
        }

        throw new NotSupported($this->id . ' API does not support '.$name);
    }

    /**
     * processOrders
     *
     * This method is to check to see if the latest tick (increment) has
     * triggered any orders and update them and the wallets accordingly
     *
     * @return void
     * @access protected
     */
    protected function processBacktestOrders()
    {
        foreach ($this->backtestOrders as $k => $order) {
            $market = $this->getMarket($order->getSymbol());
            $order->process($market, $this->getBacktestWallet($market->getQuote()), $this->getBacktestWallet($market->getBase()));
        }
    }

    protected function getOrderById()
    {
        return $this->backtestOrders[$id];
    }

    protected function getBacktestMarket($symbol)
    {
        return $this->backtestMarkets[$symbol];
    }

    protected function getBacktestWallet($wallet)
    {
        return $this->backtestWallets[$wallet];
    }
}

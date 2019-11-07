<?php

namespace ccxt\backtest;

use ccxt\backtest\order\OrderFactory;
use ccxt\Exchange;
use ccxt\InvalidOrder;
use ccxt\NotSupported;

class BacktestExchange extends Exchange
{
    protected $backtestOrders = array();
    protected $backtestWallets = array();
    protected $backtestMarkets = array();

    /**
     * createMarketBuyOrderRequiresPrice
     *
     * @TODO Not yet implemented, but would be handy for backtesting against
     * exchanges that use this.
     *
     * @var mixed
     */
    protected $createMarketBuyOrderRequiresPrice = false;

    /**
     * orderFactory
     *
     * @var OrderFactory
     */
    protected $orderFactory;

    public function __construct(OrderFactory $orderFactory, $options = array())
    {
        $this->orderFactory = $orderFactory;
        parent::__construct($options = array());
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
    public function setBacktestMarkets(iterable $markets)
    {
        foreach ($markets as $k => $market) {
            $this->backtestMarkets[$market->getSymbol()] = $market;
        }
    }

    public function setBacktestWallets(iterable $wallets)
    {
        foreach ($wallets as $k => $wallet) {
            $this->backtestWallets[$wallet->getName()] = $wallet;
        }
    }

    public function describe()
    {
        return array_replace_recursive(parent::describe(), array(
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
                'fetchOrders' => true,
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
                'createMarketBuyOrderRequiresPrice' => $this->createMarketBuyOrderRequiresPrice,
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
        $timestamp = $ticker['timestamp'];
        $baseVolume = $this->safe_float($ticker, 'baseVolume');
        $quoteVolume = $this->safe_float($ticker, 'volumeQuote');
        $open = $this->safe_float($ticker, 'open');
        $last = $this->safe_float($ticker, 'last');
        $change = null;
        $percentage = null;
        $average = null;
        if ($last !== null && $open !== null) {
            $change = $last - $open;
            $average = $this->sum($last, $open) / 2;
            if ($open > 0) {
                $percentage = $change / $open * 100;
            }
        }
        $vwap = null;
        if ($quoteVolume !== null && $baseVolume !== null && $baseVolume > 0) {
            $vwap = $quoteVolume / $baseVolume;
        }
        return array (
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601($timestamp),
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

    public function fetchOrders($symbol = null, $since = null, $limit = null, $params = [])
    {
        $orders = [];
        foreach ($this->backtestOrders as $k => $v) {
            if ($symbol && $v->getSymbol() !== $symbol) {
                continue;
            }

            if ($since && $order->getTimestamp() < $since) {
                continue;
            }

            if ($limit && count($orders) > $limit) {
                break;
            }

            $orders[] = $this->parseOrder($v);
        }
        return $orders;
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

    public function cancelOrder($id, $symbol = null, $params = array())
    {
        $order = $this->getOrderById($id);
        if (!$order) {

            // DEBUG
            echo "\r\n<pre><!-- \r\n";
            $DBG_DBG = debug_backtrace();
            foreach ($DBG_DBG as $DD) {
                echo implode(':', array(@$DD['file'], @$DD['line'], @$DD['function'])) . "\r\n";
            }
            echo " -->\r\n";
            var_dump('Order doesnt exist?!');
            var_dump($id);
            var_dump($this->backtestOrders[$id]);
            echo "</pre>\r\n";
            die();

        }
        $order->cancel();
        return $this->parseOrder($order);
    }

    /**
     * createOrder
     *
     * @param mixed $symbol
     * @param mixed $type
     * @param mixed $side
     * @param mixed $amount BASE CURRENCY (BTC in BTC/USD)
     * @param mixed $price
     * @param array $params
     *
     * @return void
     * @access public
     */
    public function createOrder($symbol, $type, $side, $amount, $price = null, $params = array())
    {
        // Validate parameters
        if (!$backtestMarket = $this->getBacktestMarket($symbol)) {
            throw new \InvalidArgumentException("Market $symbol does not exist");
            return false;
        }

        if ($type === 'market') {
            // for market buy it requires the $amount of quote currency to spend
            if ($side === 'buy') {
                if ($this->options['createMarketBuyOrderRequiresPrice']) {
                    if ($price === null) {
                        throw new InvalidOrder($this->id . " createOrder()
                            requires the $price argument with market buy orders
                            to calculate total order cost ($amount to spend),
                            where cost = $amount * $price-> Supply a $price
                            argument to createOrder() call if you want the cost
                            to be calculated for you from $price and $amount,
                            or, alternatively, add
                            .options['createMarketBuyOrderRequiresPrice'] =
                            false to supply the cost in the $amount argument
                            (the exchange-specific behaviour)");
                    } else {
                        $amount = $amount * $price;
                    }
                }
            }
        }

        $market = $this->getBacktestMarket($symbol);
        $baseWallet = $this->getBacktestWallet($backtestMarket->getBase());
        $quoteWallet = $this->getBacktestWallet($backtestMarket->getQuote());

        // If It's a limit buy/sell above/below the current price, execute a
        // marketorder instead
        if ($type === 'limit') {
            // @TODO allow customization of currentPrice
            $currentPrice = $market->getCandleClose();
            if (($side == 'buy' && $currentPrice < $price) || ($side == 'sell' && $currentPrice > $price)) {
                $type = 'market';
                $price = null;
            }
        }

        $order = $this->orderFactory->build(
            $type,
            $market,
            $quoteWallet,
            $baseWallet,
            $symbol,
            $side,
            $amount,
            $price,
            $params
        );

        $this->backtestOrders[$order->getId()] = $order;

        $parsedOrder = $this->parseOrder($order);
        return $parsedOrder;
    }

    public function fetchMarkets($params = array())
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

    //phpcs:ignore
    public function fetch_markets($params = array())
    {
        return $this->fetchMarkets($params);
    }

    /**
     * fetchOHLCV
     *
     * @param string $symbol
     * @param string $timeframe // @NOTE this does not work in backtests
     * @param mixed $since
     * @param mixed $limit
     * @param array $params // @NOTE Not used in backtests
     *
     * @return array
     * @access public
     */
    public function fetchOHLCV($symbol, $timeframe = 'default', $since = null, $limit = null, $params = array ())
    {
        return $this->markets[$symbol]->getOhlcvv($since, $limit, true);
    }

    /**
     * increment
     *
     * Move all markets forward by one candle
     *
     * @param int $count
     *
     * @return bool
     * @access public
     */
    public function increment($count = 1)
    {
        $success = false;
        // Move each of the markets forwards one candle
        foreach ($this->backtestMarkets as $market) {
            if ($market->isActive()) {
                if (false === $market->increment()) {
                    $market->setActive(false);
                } else {
                    // We've successfully advanced at least one market
                    $success = true;
                }
            }
        }
        // Now calculate any triggered orders and update wallet values
        $this->processBacktestOrders();
        return $success;
    }

    public function fetchBalance($params = array())
    {
        $data = array();
        foreach ($this->backtestOrders as $id => $order) {
            $name = $order->getLockedWallet()->getName();
            if (!isset($data['used'][$name])) {
                $data['used'][$name] = 0;
                $data[$name]['used'] = 0;
            }
            $data['used'][$name] += $order->getLockedAmount();
            $data[$name]['used'] += $order->getLockedAmount();
        }

        foreach ($this->backtestWallets as $name => $wallet) {
            if (!isset($data['used'][$name])) {
                $data['used'][$name] = 0;
                $data[$name]['used'] = 0;
            }
            $data['free'][$name] = $wallet->getQuantity();
            $data[$name]['free'] = $wallet->getQuantity();
            $data['total'][$name] = $data[$name]['free'] + $data[$name]['used'];
            $data[$name]['total'] = $data['total'][$name];
        }

        return $data;
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
            $market = $this->getBacktestMarket($order->getSymbol());
            if ($order->getStatus() == 'open') {
                $order->process();
            }
        }
    }

    protected function getOrderById($id)
    {
        return $this->backtestOrders[$id];
    }

    public function getBacktestMarkets()
    {
        return $this->backtestMarkets;
    }

    public function getBacktestMarket($symbol)
    {
        return $this->backtestMarkets[$symbol];
    }

    public function getBacktestWallet($wallet)
    {
        if (!isset($this->backtestWallets[$wallet])) {
            throw new \InvalidArgumentException("Wallet $wallet does not exist");
        }
        return $this->backtestWallets[$wallet];
    }

    public function getBacktestWallets()
    {
        return $this->backtestWallets;
    }

    public function getBacktestOrders()
    {
        return $this->backtestOrders;
    }


    /**
     * __call
     *
     * @NOTE Starting to think use camel instead of case to override
     * CCXT/Exchange methods was a bad idea... may have to switch it back :/
     *
     * @param mixed $name
     * @param array $args
     *
     * @return void
     * @throws [ExceptionClass] [Description]
     * @access
     */
    public function __call($name, $args = array())
    {
        $camelName = str_replace('_', '', ucwords($name, '_'));
        if (method_exists($this, $camelName)) {
            return call_user_func_array($this->$camelName, $args);
        }
        if (method_exists($this, $name)) {
            return call_user_func_array($this->$name, $args);
        }

        return parent::__call($name, $args);
    }
}

# CCXT Backtesting library

This is very much a work in progress and not yet ready for use. It was very 
quickly spammed out over a few days, and then semi-abandoned due to a priority 
change.

As such there are no unit tests, and little documentation.


## What is it?

The project is essentially a php backtesting extension for ccxt library.

It works with a `BacktestExchange` class which implements a CCXT Exchange interface,
(who's usual job would be to communicate with a real exchange (Bitfinex, Binance etc).

Instead of communicating with a real exchange, our `BacktestExchange` acts as a 
fake exchange, calculating the results of orders etc itself.

In order to do this, we hand it data (ohlcv) from real markets, give it some 
wallets with some funds and then fire orders at it just like we would a real 
CCXT Exchange.

## Notes/Usage/Etc

First thing to do is to create Markets and populate them with OLHCV data.
Next create Wallet(s) and give some money.
Next create BacktestingExchange and provide it with Markets and Wallets.

We can create orders in the same method we would in ccxt 
`$exchange->createOrder()`.
Calling increment() on BacktestingExchange will move time forwards by 1 OLHCV bar 
in every market.
The orders will then be checked, and adjusted based on new price, and funds in 
wallets re-calculated.


**It's worth noting that for now we need to use camelCase methods and not 
snake_case. snake_case methods will call the methods on the parent ccxt `Exchange` 
class that `BacktestingExchange` extends and as such not actually work.**

## Examples

See `test/test.php`


## TODO

### Seperate CCXT Exchange implementation and actual 'Fake Exchange' logic.

At the moment `BacktestExchange` has two jobs...

One to implement a ccxt/Exchange.

Two to actually act as a 'fake' exchange.

These should be seperated, so that Exchanges public interface matches that of a 
ccxt/Exchange.

We can then add a `BacktestExchange::getFakeExchange()` method, allowing us to 
access historical data for charting/debugging etc, while preventing 
accidentally using none ccxt methods in our actual backtests.

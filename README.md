# CCXT Backtesting library

This is very much a work in progress and not yet ready for use.

## Notes/Usage/Etc

First thing to do is to create Markets and populate them with OLHCV data.
Next create Wallet(s) and give some money.
Next create BacktestingExchange and provide it with Markets and Wallets.

Calling increment() on BacktestingExchange will move time forwards by 1 OLHCV bar.
The orders will then be checked, and adjusted based on new price, and funds in 
wallets re-calculated.

# Erply POS system Integration with Magento2

[![Build Status](https://img.shields.io/travis/cakephp/app/master.svg?style=flat-square)](https://travis-ci.org/cakephp/app)
[![License](https://img.shields.io/packagist/l/cakephp/app.svg?style=flat-square)](https://packagist.org/packages/cakephp/app)

Erply Lite Version  | Erply Full Version ($1500)

## Installation

1. Download [Erply Extension](https://github.com/yogendramaurya/ErplyM/archive/master.zip) 
2. Upload the extension on /app/code/{upload here}
3. Run the below commands

Upgrade Setup

```bash
bin/magento setup:upgrade
```

Compile

```bash
bin/magento setup:di:compile
```

Flush the cache and check.

## Configuration

Go to Admin Configuration and requried detail for connect erply 

![alt text](https://image.ibb.co/mSY1D0/erplyconfiguration.png)


## Sync Commands
```bash
bin/magento acodesh:importproduct
bin/magento acodesh:importproduct price
bin/magento acodesh:importcustomer
```

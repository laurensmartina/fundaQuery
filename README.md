# Funda Query

## What is Funda Query

A small application to query Funda. Using the Funda API described in http://docs.funda.nl/api/products/.

## Installing

Install PHP 5.4 or newer and composer.

```bash
git clone https://github.com/laurensmartina/fundaQuery.git
composer.phar install
```
Set read/write permissions for app/cache and app/logs.

## Requirements
**fundaQuery** has the same requirements as [Symfony2]

## COPYRIGHT
Copyright (C) 2014 Laurens Martina. All rights reserved.

## LICENSE
http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2

## Usage

When running composer install you can configure the application and the parameters.yml will be created for you.

Available commands:

* funda:query     Fetch a list of real estate that is for sale, sorted by the top 10 real estate agents.
* funda:query -g  Fetch a list of real estate with a garden that is for sale, sorted by the top 10 real estate agents.

[1]:  http://symfony.com/doc/2.4/book/installation.html
[2]:  http://getcomposer.org/

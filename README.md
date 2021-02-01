# [Sendinblue](http://sendinblue.com/) Extension for Magento 2

## ATTENTION
This module is not stable. We're working to make it works :)

## Features
  * With the Sendinblue plugin, you can find everything you need to easily and efficiently send your email & SMS campaigns to your prospects and customers.
  * Synchronize your subscribers with Sendinblue (subscribed and unsubscribed contacts)
  * Easily create good looking emailings
  * Schedule your campaigns
  * Track your results and optimize
  * Monitor your transactional emails (purchase confirmation, password reset, etc) with a better deliverability and real-time analytics
  * Optimized deliverability

## Requirements
  * Magento Community Edition 2.3.x or higher / Magento Enterprise Edition 2.3.x or higher
  

## Installation Method 1 - Installing using archive
  * Download [ZIP Archive](https://github.com/dadolun95/magento2-sendinblue/archive/master.zip)
  * Extract files
  * In your Magento 2 root directory create folder app/code/Sendinblue/Sendinblue/
  * Copy files and folders from archive to that folder
  * Install Mageplaza SMTP module, follow instructions [here](https://github.com/mageplaza/magento-2-smtp)
  * In your Magento 2 root directory create folder app/code/Sendinblue/Sendinblue/
  * In command line, using "cd", navigate to your Magento 2 root directory
  * Run commands:
```
follow steps to install module 

composer require sendinblue/api-v3-sdk:7.2.0

bin/magento module:enable Mageplaza_Smtp
bin/magento module:enable Sendinblue_Sendinblue
bin/magento setup:di:compile
bin/magento setup:upgrade
```

## Installation Method 2 (recommended) - Installing using composer
  * Update composer repositories:
```
{
  "type": "vcs",
  "url":  "git@github.com:dadolun95/magento2-sendinblue"
}
```
  * Add module with composer:
```
composer require dadolun95/magento2-sendinblue
```
  * In command line, using "cd", navigate to your Magento 2 root directory
  * Run commands:
```
follow steps to install module 

bin/magento module:enable Sendinblue_Sendinblue
bin/magento setup:di:compile
bin/magento setup:upgrade
```

## License
The code is licensed under [Open Software License ("OSL") v. 3.0](http://opensource.org/licenses/osl-3.0.php).

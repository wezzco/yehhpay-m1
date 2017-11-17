# Yehhpay Magento 1 Extension

This extension will enable merchants to accept post delivery payments via Yehhpay to increase their conversion rates.
With Yehhpay, merhants can offer their customers an convenient post delivery payment method and at the same time control credit risks.

When installing this extension, an additional payment method will be added to your checkout.

In this installation manual we assume you have already got an account with us. If not, you can apply for an account with Yehhpay on https://account.postcode.nl/registreer

The extension was created by Wezz e-Commerce for Yehhpay.

__Yehhpay Magento 2 Extension__

https://www.wezz.co/extensions/yehhpay

__FAQ__

https://www.wezz.co/extensions/yehhpay

__API Documentation__

This extension uses Yehhpay's Merchant API. Documentation of the API can be found on
https://api.yehhpay.nl/documentation/merchant/api

# Installation

## 1. Preparation

### 1.1 Backup

Make a backup of all your files and database before starting installation.

### 1.2 Disable Caching

Log into the Magento Admin and disable all caching using the path below:

__System__ -> __Cache Management__

Select all cache types and disable them.

### 1.3 Disable Compilation

Next, make sure compilation is disabled using the path below:

__System__ -> __Tools__ -> __Compilation__

## 2. Installation

### 2.1 Installation Through FTP

Upload all your files to the root directory of your Magento installation. Make sure you use the __merge__ function where prompted about overwriting existing files and folders.

When finished log out of the admin and log back in.

### 2.2 Installation through Composer

Make sure you have set up [composer to work](https://github.com/Cotya/magento-composer-installer) with your Magento install.
`
composer require wezz/yehhpay-m1
`

When finished log out of the admin and log back in.

## Configuration

Navigate to the module configuration using the following path:

__System__ -> __Configuration__ -> __Sales__ -> Yehhpay

### Fields

Fill in the following fields to configure your Yehhpay extension.


#### General
| Field | Explanation |
| :--- | :--- |
| Enabled | Option to enable/disable the module on your Magento store |
| Payment mode | Switch between test and live payment mode |
| Application key | Please find your Application key in your Yehhpay account |
| Application secret | Please find your Application secret in your Yehhpay account |
| Minimum Order Total | The minimum order amount that can be payed with Yehhpay |
| Maximum Order Total | The maximum order amount that can be payed with Yehhpay |

#### Advanced

| Field | Explanation |
| :--- | :--- |
| Check on billing and delivery addresses | Yehhpay requires customers shippings and invoice address to be the same, must be set to __yes__. |
| Service identifier | Please find your Application identifier in your Yehhpay account |
| Frontend label | The name of the Yehhpay Payment method in the Magento checkout |
| Payment success status | The status of the order when the payment module returns status success |
| Payment failed status | The status of the order when the payment module returns status failed |
| Payment from Specific Countries | Select specific country from with customers can pay with Yehhpay, leave empty for __all countries__ |

## Troubleshooting


For __Frequently Asked Questions__ please visit [https://www.wezz.co/extensions/yehhpay](https://www.wezz.co/extensions/yehhpay).

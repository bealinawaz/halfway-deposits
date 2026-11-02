# Halfway – Deposits for WooCommerce

Take a fixed amount or a percentage of the order as a deposit at WooCommerce checkout. The rest of the order is shown as a "Balance due later" line, and each order records the full order value, the deposit, and the balance due.

![Tested up to WordPress 7.1](https://img.shields.io/badge/WordPress-7.1-blue)
![WooCommerce 7.1+](https://img.shields.io/badge/WooCommerce-7.1%2B-7f54b3)
![PHP 7.4 to 8.5](https://img.shields.io/badge/PHP-7.4%E2%80%938.5-777bb4)
![License GPLv2 or later](https://img.shields.io/badge/license-GPLv2%2B-green)

> The balance is **not** collected automatically. The store collects it manually, for example with a new order for the balance and its "Customer payment page" link.

## Features

- Fixed or percentage deposit.
- Shipping charged in full with the deposit, or included in the order value.
- Minimum order value and product category rules (applies when any cart item matches; subcategories included).
- Editable balance line label and cart/checkout notice.
- Order value, deposit, and balance due on the admin order screen, order received page, My Account, and emails.
- Classic and block cart/checkout (Store API), HPOS compatible.

## Setup

1. Install WooCommerce and activate the plugin.
2. **WooCommerce → Settings → Deposit**: enable deposits and set the type and amount.

## How it works

- On `woocommerce_cart_calculate_fees` (priority 1000) the plugin adds a fee with ID `hdwc_balance` and a negative amount equal to the order value minus the deposit. The order value is products after discounts plus tax, other fees, and shipping (or without shipping, which is then paid in full).
- WooCommerce spreads tax over negative fees, so the plugin removes the tax from this line in the cart (`woocommerce_cart_totals_get_fees_from_cart_taxes`) and on order recalculation (`woocommerce_order_item_fee_after_calculate_taxes`). Tax is charged in full with the deposit.
- A one-time recalculation corrects any one-cent rounding difference.
- WooCommerce will not let negative fees reduce the total below the order's tax, so a deposit smaller than the tax results in the tax amount being charged.
- Order meta (`_hdwc_order_value`, `_hdwc_deposit`, `_hdwc_balance_due`) is saved on `woocommerce_checkout_create_order` (classic) and `woocommerce_store_api_checkout_update_order_meta` (blocks), and shown through `woocommerce_get_order_item_totals` and `woocommerce_admin_order_totals_after_total`.

## Project structure

```
halfway-deposits.php        Plugin header, bootstrap, WooCommerce compatibility
includes/class-hdwc-settings.php         Reads and validates settings
includes/class-hdwc-deposit.php          Cart calculation, tax handling, notices
includes/class-hdwc-orders.php           Order meta and totals rows
includes/class-hdwc-settings-page.php    WooCommerce → Settings → Deposit tab
includes/class-hdwc-admin.php            Tab registration, plugin links, support box
assets/admin.css                         Admin styles
uninstall.php                            Removes settings (order data is kept)
readme.txt                               WordPress.org readme
```

## Custom development

Need automatic balance payments, payment plans, or per-product deposit rules? The plugin is built and maintained by [Ali Nawaz](https://bealinawaz.com/?utm_source=github&utm_medium=readme&utm_campaign=halfway-deposits), a WordPress and WooCommerce developer. [Get in touch for custom work](https://bealinawaz.com/contact-me/?utm_source=github&utm_medium=readme&utm_campaign=halfway-deposits).

## License

GPLv2 or later. See [LICENSE](LICENSE).

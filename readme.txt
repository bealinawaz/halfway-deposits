=== Fixed Deposit for WooCommerce – Take a Set Deposit at Checkout ===
Contributors: imalinawaz
Donate link: https://www.buymeacoffee.com/bealinawaz
Tags: woocommerce deposit, deposit, partial payment, down payment, pay deposit
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Take a fixed amount or a percentage of the order as a deposit at WooCommerce checkout, and record the balance due on the order.

== Description ==

Fixed Deposit for WooCommerce lets customers pay a deposit at checkout instead of the full order total. Choose a fixed amount, such as $50, or a percentage of the order, such as 25%. The rest of the order is shown as a "Balance due later" line in the cart and checkout, and the order records the full order value, the deposit, and the balance due.

It suits made-to-order products, furniture, events, bookings, custom work, and any store that takes a down payment before the rest is settled.

**Important: the balance is not collected automatically.** The plugin takes the deposit through your normal payment methods and records what is still owed. You collect the balance yourself, for example by creating a new order for the balance under WooCommerce → Orders and sending the customer its "Customer payment page" link, or by invoicing them another way. Need automatic balance payments? See Custom development below.

**Features**

* **Fixed or percentage deposit:** take a set amount or a share of the order.
* **Shipping your way:** charge shipping in full with the deposit, or include it in the order value the deposit is taken from.
* **Minimum order value:** only take a deposit on larger orders.
* **Category rules:** take a deposit only when the cart contains a product from selected categories (or their subcategories).
* **Clear for customers:** a "Balance due later" line in the cart and checkout (label editable), and a notice explaining the deposit.
* **Recorded on every order:** Order value, Deposit paid, and Balance due appear on the order screen, the order received page, My Account, and order emails.
* **Block and classic checkout:** works with the Cart and Checkout blocks and the classic shortcode checkout.
* **HPOS compatible:** order data is saved through WooCommerce's order API.

**How the deposit is calculated**

The order value is the products after discounts, plus tax, plus other fees, plus shipping. If shipping is charged in full, the deposit is taken from the products and fees, and shipping is added on top. The plugin adds a negative, non-taxable line so the total to pay equals the deposit. Taxes are charged in full with the deposit and the balance carries no tax. If the order is already at or below the deposit amount, no deposit is taken and the customer pays the full total.

**Custom development**

Need automatic balance payments, scheduled payment plans, per-product deposit rules, or deposits that follow your local tax rules? The plugin is built and maintained by Ali Nawaz, a WordPress and WooCommerce developer. [Get in touch for custom work](https://bealinawaz.com/contact-me/?utm_source=wordpress.org&utm_medium=plugin-readme&utm_campaign=fixed-deposit-for-woocommerce).

== Installation ==

1. Install and activate WooCommerce, then this plugin.
2. Go to **WooCommerce → Settings → Deposit**.
3. Tick **Enable deposits**, choose a fixed amount or percentage, and enter the amount.
4. Optional: choose how shipping is handled, set a minimum order value, limit deposits to categories, and change the balance label and notice.
5. Place a test order to check the totals.

== Frequently Asked Questions ==

= How do I take a deposit in WooCommerce? =

Enable deposits under WooCommerce → Settings → Deposit and enter a fixed amount or percentage. Customers then pay only the deposit at checkout.

= Does the plugin charge the remaining balance automatically? =

No. It records the balance due on the order. To collect it, create a new order for the balance under WooCommerce → Orders, set it to Pending payment, and send the customer the "Customer payment page" link, or invoice them another way.

= Can the deposit be a percentage? =

Yes. Choose "Percentage of the order" and enter a number such as 25.

= Can I take a deposit only on some products? =

Choose "Orders with a product from the categories below" and select the categories. If any product in the cart is in one of them, the deposit applies to the whole order.

= How are taxes handled? =

Tax on the products and shipping is charged in full with the deposit, and the balance line has no tax. A deposit cannot be smaller than the tax on the order, because WooCommerce does not let a negative line reduce the total below the tax; in that case the customer pays the tax amount.

= Does it work with the block checkout? =

Yes. The balance line appears in the Cart and Checkout blocks, and the deposit details are saved on orders placed through either checkout.

= Will refunds work? =

Yes. Refunds work as usual against the amount the customer paid, which is the deposit.

= What happened to "Force Cart to Specific Amount"? =

Version 1.0 always reduced the cart to exactly 1 with an "Adjustment" line. Version 2.0 replaces it with configurable deposits and is off until you enable it.

== Changelog ==

= 2.0.0 =
* Complete rewrite as Fixed Deposit for WooCommerce. Version 1.0 always forced the cart total to 1; deposits are now configurable and off until you enable them.
* New: settings under WooCommerce → Settings → Deposit.
* New: fixed or percentage deposits, shipping handling, minimum order value, and category rules.
* New: balance line label and cart and checkout notice.
* New: order value, deposit, and balance due saved on each order and shown on the order screen, order received page, My Account, and emails.
* New: support for the Cart and Checkout blocks.
* Fixed: taxes, shipping, and other fees are now part of the calculation.
* Declares compatibility with WooCommerce High-Performance Order Storage and the block-based cart and checkout.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 2.0.0 =
Complete rewrite. Deposits are off after updating: enable them and set the amount under WooCommerce → Settings → Deposit.

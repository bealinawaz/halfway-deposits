=== Halfway – Deposits & Partial Payments for WooCommerce ===
Contributors: imalinawaz
Donate link: https://www.buymeacoffee.com/bealinawaz
Tags: deposit, partial payment, woocommerce deposits, woocommerce partial payment, down payment
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Take a deposit or partial payment at WooCommerce checkout, then request the balance in one click with a payment link.

== Description ==

Halfway – Deposits & Partial Payments for WooCommerce lets customers pay a deposit (a partial payment or down payment) at checkout instead of the full order total. Choose a fixed amount, such as $50, or a percentage of the order, such as 25%. The rest of the order is shown as a "Balance due later" line in the cart and checkout, and the order records the full order value, the deposit, and the balance due.

It suits made-to-order products, furniture, events, bookings, custom work, and any store that takes a down payment before the rest is settled.

**Collect the balance in one click.** When you are ready for the rest of the money, open the order and click **Request balance payment**. Halfway creates a balance order and emails the customer a link to pay it with any of your payment methods. Customers also get a **Pay balance** button in My Account, and the original order is marked as paid when the balance comes in. The balance is not charged automatically: you decide when to request it, and the customer pays it themselves.

**Features**

* **Fixed or percentage deposit:** take a set amount or a share of the order.
* **Shipping your way:** charge shipping in full with the deposit, or include it in the order value the deposit is taken from.
* **Minimum order value:** only take a deposit on larger orders.
* **Category rules:** take a deposit only when the cart contains a product from selected categories (or their subcategories).
* **Clear for customers:** a "Balance due later" line in the cart and checkout (label editable), and a notice explaining the deposit.
* **One-click balance request:** a Deposit balance box on the order creates a balance order and emails the payment link, using WooCommerce's own "Order details" email. Works as a bulk action on the Orders list too.
* **Pay balance in My Account:** customers pay the balance on WooCommerce's standard payment page, with any payment method. A failed payment can simply be tried again.
* **Edit before sending:** add delivery or other charges to the balance order before you email the link, or copy the link and send it yourself.
* **Balance paid tracking:** the original order shows "Balance paid" once the balance order is paid, and can be marked Completed automatically.
* **Recorded on every order:** Order value, Deposit paid, and Balance due appear on the order screen, the order received page, My Account, and order emails.
* **Block and classic checkout:** works with the Cart and Checkout blocks and the classic shortcode checkout.
* **HPOS compatible:** order data is saved through WooCommerce's order API.

**How the deposit is calculated**

The order value is the products after discounts, plus tax, plus other fees, plus shipping. If shipping is charged in full, the deposit is taken from the products and fees, and shipping is added on top. The plugin adds a negative, non-taxable line so the total to pay equals the deposit. Taxes are charged in full with the deposit and the balance carries no tax. If the order is already at or below the deposit amount, no deposit is taken and the customer pays the full total.

**Custom development**

Need automatic card charging for the balance, scheduled payment plans, per-product deposit rules, or deposits that follow your local tax rules? The plugin is built and maintained by Ali Nawaz, a WordPress and WooCommerce developer. [Get in touch for custom work](https://bealinawaz.com/contact-me/?utm_source=wordpress.org&utm_medium=plugin-readme&utm_campaign=halfway-deposits).

== Installation ==

1. Install and activate WooCommerce, then this plugin.
2. Go to **WooCommerce → Settings → Deposit**.
3. Tick **Enable deposits**, choose a fixed amount or percentage, and enter the amount.
4. Optional: choose how shipping is handled, set a minimum order value, limit deposits to categories, and change the balance label and notice.
5. Place a test order to check the totals.
6. To collect the balance, open the order under WooCommerce → Orders and click **Request balance payment** in the Deposit balance box.

== Frequently Asked Questions ==

= How do I take a deposit in WooCommerce? =

Enable deposits under WooCommerce → Settings → Deposit and enter a fixed amount or percentage. Customers then pay only the deposit at checkout.

= How do I collect the remaining balance? =

Open the order under WooCommerce → Orders and click **Request balance payment** in the Deposit balance box. Halfway creates a balance order for the amount due and emails the customer WooCommerce's "Order details" email with a link to pay it. You can also select several orders and choose **Request balance payment** from the bulk actions. The customer can pay from the email or from the **Pay balance** button in My Account.

= Does the plugin charge the remaining balance automatically? =

No. The balance is never charged without the customer. You request it when you are ready, and the customer pays it on WooCommerce's payment page with any payment method you offer.

= Can I change the balance before sending it, for example to add delivery? =

Yes. Click **Create the balance order without emailing**, edit the balance order (add a fee or product, then click Recalculate), and send the link with **Email payment link** in the Deposit balance box.

= What happens when the balance is paid? =

The balance order is marked as paid like any order, and the original order shows "Balance paid". Under WooCommerce → Settings → Deposit → Balance payment you can also have the original order marked as Completed at that point.

= What if the customer's payment fails? =

The balance order stays unpaid, and the customer can try again from the same link or from My Account. If you cancel the balance order, you can request the balance again from the original order.

= Can the deposit be a percentage? =

Yes. Choose "Percentage of the order" and enter a number such as 25.

= Can I take a deposit only on some products? =

Choose "Orders with a product from the categories below" and select the categories. If any product in the cart is in one of them, the deposit applies to the whole order.

= How are taxes handled? =

Tax on the products and shipping is charged in full with the deposit, and the balance line has no tax. A deposit cannot be smaller than the tax on the order, because WooCommerce does not let a negative line reduce the total below the tax; in that case the customer pays the tax amount.

= Does it work with the block checkout? =

Yes. The balance line appears in the Cart and Checkout blocks, and the deposit details are saved on orders placed through either checkout.

= Can I take a partial payment in WooCommerce without a plugin? =

Not at checkout. WooCommerce has no setting for it, so without a plugin you would create a separate order for each part payment by hand. This plugin takes the deposit at checkout and keeps the order value, deposit, and balance due together on one order.

= Can customers choose between a deposit and paying in full? =

Not in this version. When an order matches your rules (minimum order value and categories), the deposit applies automatically. Use the minimum order value or categories to decide which orders take a deposit.

= Does it support installments or payment plans? =

No. It takes one deposit at checkout and records one balance. Scheduled installments and automatic balance payments need custom development (see below).

= Can I use it for a refundable security deposit, for example on rentals? =

Not as a separate refundable charge. The deposit is part of the order price and the balance is what remains. A security deposit that is returned after a rental needs a different setup.

= Can the customer pay the balance on delivery? =

Yes. The balance is recorded on the order and shown in the order emails. Collect it in cash or by card on delivery, or send a payment link with **Request balance payment**.

= Will refunds work? =

Yes. Refunds work as usual. The deposit is refunded from the original order and the balance from the balance order, because each payment belongs to its own order. If you refund or cancel the balance order, the original order shows the balance as due again.

= What happened to "Force Cart to Specific Amount"? =

Version 1.0 always reduced the cart to exactly 1 with an "Adjustment" line. Version 2.0 replaces it with configurable deposits and is off until you enable it.

= Is the plugin available in my language? =

The plugin is ready for translation, and translations are made by volunteers on WordPress.org. You can help translate it into your language at [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/halfway-deposits/). Once a language is 90% translated, WordPress installs the translation automatically through language packs. A translation template, languages/halfway-deposits.pot, is included for tools such as Poedit and Loco Translate.

== Screenshots ==

1. Deposit settings under WooCommerce → Settings → Deposit: percentage or fixed amount, shipping, minimum order value and categories.
2. Block checkout: the customer pays the deposit today and sees the balance due later in the order summary.
3. Order received page with the order value, the deposit paid and the balance due.
4. Admin order screen showing the order value, deposit due now and balance due.
5. The Deposit balance box: request the balance payment in one click.
6. My Account: the customer pays the balance with a Pay balance button.

== Changelog ==

= 2.2.2 (2026-11-15) =
* Developers: new filters hdwc_cart_qualifies, hdwc_min_total, hdwc_deposit_amount and hdwc_balance_order_settles_deposit, so add-ons can extend the deposit rules and the balance payment. No change to how the plugin works.

= 2.2.1 (2026-11-15) =
* Translations: a translation template (languages/halfway-deposits.pot) is included, and the readme explains how to translate the plugin on translate.wordpress.org.
* Right-to-left languages: the settings support box now uses logical CSS, so it displays correctly in Arabic, Hebrew, Urdu and other RTL languages.

= 2.2.0 (2026-11-15) =
* New: Request balance payment. A Deposit balance box on the order creates a balance order for the amount due and emails the customer a payment link, using WooCommerce's "Order details" email.
* New: bulk action "Request balance payment" on the Orders list.
* New: Pay balance button in My Account and on the order details page.
* New: the original order shows "Balance paid" when the balance order is paid, with an option to mark it Completed.
* New: create the balance order without emailing, so you can edit it first, and copy its payment link.
* Tested with WooCommerce 11.3, with High-Performance Order Storage and with legacy order storage.

= 2.1.1 (2026-11-06) =
* New display name: Halfway – Deposits & Partial Payments for WooCommerce. No change to how the plugin works or to your settings.
* Readme: clearer description and answers about installments, security deposits, and paying the balance on delivery.

= 2.1.0 (2026-11-02) =
* Renamed to Halfway – Deposits for WooCommerce (slug and text domain halfway-deposits).
* Settings and order data now use the hdwc_ prefix. Settings saved by 2.0.0 move across automatically once, and orders placed with 2.0.0 still show their deposit and balance.

= 2.0.0 =
* Complete rewrite as Halfway – Deposits for WooCommerce. Version 1.0 always forced the cart total to 1; deposits are now configurable and off until you enable them.
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

= 2.2.2 =
New developer filters only. Nothing to change.

= 2.2.1 =
Translation and right-to-left language improvements. Nothing to change.

= 2.2.0 =
New: request the balance in one click and let customers pay it from My Account. Nothing changes until you use it.

= 2.1.1 =
Name and readme update only. Nothing to do.

= 2.1.0 =
New name and prefix. Your settings move across automatically; nothing to do.

= 2.0.0 =
Complete rewrite. Deposits are off after updating: enable them and set the amount under WooCommerce → Settings → Deposit.

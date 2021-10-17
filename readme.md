# Post Author Optimization for WooCommerce ![Testing status](https://github.com/devinsays/post-author-optimization-for-woocommerce/actions/workflows/php-tests.yml/badge.svg?branch=main)

* Requires PHP: 7.0
* WP requires at least: 5.7
* WP tested up to: 5.7
* WC requires at least: 5.6.0
* WC tested up to: 5.8.0
* Stable tag: 1.0.0
* License: [GPLv3 or later License](http://www.gnu.org/licenses/gpl-3.0.html)

## Description

This extension uses filters to store the `_customer_user` ID associated with a WooCommerce `shop_order` or `shop_subscription` in the `post_author` column. This allows for more efficent queries to get customer orders or subscriptions.

More information about this performance issue can be found in the [WooCommerce development blog](https://developer.woocommerce.com/2018/01/15/performance-switching-to-post_author-to-store-order-customer-ids/).

Props [@pogla](https://github.com/pogla) who wrote the majority of this code.

### Details

WooCommerce stores order records in the `posts` table as a `shop_order` post type. The majority of data associated with the order, such as the order_total or billing and shipping information is stored in the `postmeta` table.

This works fine in most cases, but once a WooCommerce shop scales past ~1 million orders, queries of postmeta can start to run long. If WordPress needs to get a specific customer's orders (such as in the customer account dashboard), it requires a querying against `_customer_user` key in the postmeta table.

To see how long it takes on your site, you can run a query like this (use an actual customer id). If it returns in less than as second, it's probably not worth optimizing for at this point:

```sql
select * from wp_postmeta where meta_key = '_customer_user' and meta_value = 10;
```

This post on the [WooCommerce developer blog](https://developer.woocommerce.com/2018/01/15/performance-switching-to-post_author-to-store-order-customer-ids/) explains the performance issue well, and also has a proposed solution! Why not use the `post_author` column in the posts table to store the customer ID? Querying against a much smaller table against an indexed column is much faster (as that post describes).

Unfortunately this idea was implemented and then reverted out of WooCommerce core. The reason is that sometimes a shop admin will create an order for a customer, and if the post_author column is used to store the customer ID, then there's no record of who actually created the order.

However, for the sites I work with, orders aren't generally created by admins. In the rare cases they are, an order note will be added. So we'll gladly take the performance improvements of storing the customer ID in the post_author column to make customer order queries more efficient.

In case you'd also like to make the switch, you can use this plugin. Once it's enabled, any future orders or subscriptions that are created or updated will save the `_customer_user` (ID) value to the post_author column in the posts table.

To update historic orders and subscriptions, you'll need to run a script or wp cli command (I'm still working on this).

The `_customer_user` meta will still be saved, so any queries that rely on it will continue to work. But, you can start to swap out those queries where needed to use the `post_author` column in the posts table instead.

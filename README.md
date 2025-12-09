# AFB Off‑Canvas Cart (Slide‑In) 

A WooCommerce extension that adds an off‑canvas cart drawer, a streamlined reorder flow, and a custom Thank You page with mobile/RTL support.

## Features

- Off‑canvas cart UI with live quantity updates and item removal
- Reorder button on My Orders that re‑adds items via AJAX and opens the cart
- Custom Thank You page template modeled for a clean, receipt‑like layout
- Displays item meta and pickup details in order items and customer section
- RTL styling and mobile‑responsive layouts

## Requirements

- WordPress
- WooCommerce

## Installation

1. Place the plugin directory in `wp-content/plugins/`.
2. Activate “AFB Off‑Canvas Cart (Slide‑In) – Final” in WordPress → Plugins.

## Key Files

- Plugin bootstrap: `afb-offcanvas-cart.php`
- Cart logic and AJAX: `includes/class-afb-cart.php`
- WooCommerce AJAX and order review: `includes/afb-wc-ajax.php`
- Reorder button (AJAX flow): `includes/afb-reorder-button.php`
- Thank You page override: `templates/checkout/thankyou.php`
- Order item meta rendering: `includes/afb-order-itemmeta-frontend.php`

## AJAX Endpoints

- `afb_cart_open` → returns cart HTML and totals
- `afb_cart_update_qty` → updates line quantity
- `afb_cart_remove` → removes a cart line
- `afb_add_to_cart` → adds a product/variation to cart
- `afb_get_order_items` → returns order items to drive the reorder flow

Nonces:
- Cart actions use `afb_nonce`
- Reorder fetch validates `reorder_nonce` for `woocommerce-reorder`

## Reorder Flow (My Orders)

- Button link carries `?reorder=<order_id>&reorder_nonce=<nonce>`
- Click handler:
  - Calls `admin-ajax.php?action=afb_get_order_items` to fetch items
  - Sequentially calls `admin-ajax.php?action=afb_add_to_cart` for each line
  - Refreshes current page with `?open_afb_cart=true` to open the cart drawer

## Thank You Page Override

- Template override is registered in `afb-offcanvas-cart.php` via `woocommerce_locate_template`
- Custom template path: `templates/checkout/thankyou.php`
- Renders:
  - Order header, items (image, name, unit price, qty, line total)
  - Summary (subtotal, shipping, total TTC)
  - Footer (order ref, payment method, delivery mode)
  - Customer details via `wc_get_template('order/order-details-customer.php', ['order' => $order])`

Enhancements:
- Item meta under each line via `woocommerce_order_item_meta_end`
- Delivery option mapping (`afb_delivery_option` → Pickup/Multiship label)
- Applies pickup/multiship CSS to hide shipping columns and addresses when relevant

## Mobile & RTL

- Item layout stacks on small screens; columns become a single‑column grid
- Pickup details grid switches to one column on phones
- Adds `afb-rtl` for RTL and flips row directions

## Internationalization

- Text domain: `afb-offcanvas`

## Troubleshooting

- If the cart does not open after reorder, ensure `open_afb_cart=true` is present in the URL and scripts from `afb-offcanvas-cart.php` are enqueued.
- For variable products, `variation_id` must exist in the order line; custom product types may require additional cart item data.

## Security Notes

- All admin‑AJAX handlers validate nonces
- Reorder flow checks order ownership before returning items


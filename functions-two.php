/**
 * Add a custom checkbox field to the product editor.
 */
function add_one_time_purchase_checkbox() {
    woocommerce_wp_checkbox(array(
        'id'            => '_one_time_purchase',
        'label'         => __('One-Time Purchase', 'woocommerce'),
        'description'   => __('Enable this to allow this product to be purchased only once per order.', 'woocommerce'),
    ));
}
add_action('woocommerce_product_options_general_product_data', 'add_one_time_purchase_checkbox');

/**
 * Save the custom checkbox field value.
 */
function save_one_time_purchase_checkbox($post_id) {
    $one_time_purchase = isset($_POST['_one_time_purchase']) ? 'yes' : 'no';
    update_post_meta($post_id, '_one_time_purchase', $one_time_purchase);
}
add_action('woocommerce_process_product_meta', 'save_one_time_purchase_checkbox');

/**
 * Restrict one-time purchase products from being added to the cart multiple times.
 */
function restrict_one_time_purchase_product($passed, $product_id, $quantity) {
    // Check if the product is marked as a one-time purchase.
    $is_one_time_purchase = get_post_meta($product_id, '_one_time_purchase', true) === 'yes';

    if ($is_one_time_purchase) {
        // Check if the product is already in the cart.
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] == $product_id) {
                // If the product is already in the cart, prevent adding it again.
                wc_add_notice(__('This product can only be purchased once per order.', 'woocommerce'), 'error');
                return false;
            }
        }

        // Check if the user has already purchased this product (optional).
        $user_id = get_current_user_id();
        if ($user_id > 0) {
            $customer_orders = wc_get_orders(array(
                'customer_id' => $user_id,
                'status'      => 'completed',
                'limit'       => -1,
            ));

            foreach ($customer_orders as $order) {
                foreach ($order->get_items() as $item) {
                    if ($item->get_product_id() == $product_id) {
                        // If the product has already been purchased, prevent adding it to the cart.
                        wc_add_notice(__('You have already purchased this product and cannot purchase it again.', 'woocommerce'), 'error');
                        return false;
                    }
                }
            }
        }
    }

    return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'restrict_one_time_purchase_product', 10, 3);

// Optional: Hide the "Add to Cart" Button
/**
 * Hide the "Add to Cart" button for one-time purchase products that have already been purchased.
 */
function hide_add_to_cart_button_for_purchased_products() {
    if (is_product()) {
        global $product;
        $product_id = $product->get_id();
        $is_one_time_purchase = get_post_meta($product_id, '_one_time_purchase', true) === 'yes';

        if ($is_one_time_purchase) {
            $user_id = get_current_user_id();
            if ($user_id > 0) {
                $customer_orders = wc_get_orders(array(
                    'customer_id' => $user_id,
                    'status'      => 'completed',
                    'limit'       => -1,
                ));

                foreach ($customer_orders as $order) {
                    foreach ($order->get_items() as $item) {
                        if ($item->get_product_id() == $product_id) {
                            // Hide the "Add to Cart" button.
                            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
                            echo '<p class="purchased-notice">' . __('You have already purchased this product.', 'woocommerce') . '</p>';
                            break 2;
                        }
                    }
                }
            }
        }
    }
}
add_action('wp', 'hide_add_to_cart_button_for_purchased_products');

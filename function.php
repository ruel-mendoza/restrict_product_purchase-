/**
 * Restrict purchase of a specific product to one per user.
 */
function restrict_product_purchase_to_one($passed, $product_id, $quantity, $variation_id = null, $variations = null) {
    // Replace 123 with the ID of the product you want to restrict.
    $restricted_product_id = 123;

    // Check if the product being added to the cart is the restricted product.
    if ($product_id == $restricted_product_id) {
        // Get the current user ID.
        $user_id = get_current_user_id();

        if ($user_id > 0) {
            // Check if the user has already purchased this product.
            $customer_orders = wc_get_orders(array(
                'customer_id' => $user_id,
                'status' => 'completed', // Only check completed orders.
                'limit' => -1,
            ));

            foreach ($customer_orders as $order) {
                foreach ($order->get_items() as $item) {
                    if ($item->get_product_id() == $restricted_product_id) {
                        // If the product is found in any order, prevent adding it to the cart.
                        wc_add_notice(__('You have already purchased this product and cannot purchase it again.', 'woocommerce'), 'error');
                        return false;
                    }
                }
            }
        }
    }

    return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'restrict_product_purchase_to_one', 10, 5);

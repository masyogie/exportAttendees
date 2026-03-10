<?php
/**
 * Tribe, optimized version for adding user meta to the attendees csv export.
 *
 * Defines the columns to be exported.
 * Columns are defined in a key-value pair, where the key is the data field and the value is the column name in the CSV.
 **/

/**
 * Defines the columns to be exported.
 * Columns are defined in a key-value pair, where the key is the data field and the value is the column name in the CSV.
 *
 * @return array
 */
function tribe_get_column_definitions(): array {
    return [
        // Add your column definitions here
        // 'field_name' => 'Column Title',
        'billing_first_name'   => 'Billing First Name',
        'billing_last_name'    => 'Billing Last Name',
        'billing_address_1'    => 'Billing Address 1',
        'billing_city'         => 'Billing City',
        'billing_state'        => 'Billing State',
        'billing_postcode'     => 'Billing Zip',
        'billing_phone'        => 'Phone',
        'billing_email'        => 'Email',
        'order_date'           => 'Order Date',
        'order_total'          => 'Total Cost',
        'payment_method'       => 'Payment Method',
        'payment_method_title' => 'Payment Method Title',
        'coupon_codes'         => 'Coupon Codes',
        'coupon_discounts'     => 'Coupon Discounts'
    ];
}

/**
 * Setup function for customizing the CSV export.
 * Called during the initiation of the CSV export process.
 *
 * @param int $event_id
 * @return void
 */
function tribe_export_custom_set_up(int $event_id): void {
    // Check if we're on the correct admin page
    if (!is_admin()) {
        $screen_base = 'tribe_events_page_tickets-attendees';
    } elseif (function_exists('get_current_screen')) {
        $screen = get_current_screen();
        $screen_base = $screen ? $screen->base : 'tribe_events_page_tickets-attendees';
    } else {
        $screen_base = 'tribe_events_page_tickets-attendees';
    }

    // Add filters for columns and data
    $filter_name = "manage_{$screen_base}_columns";

    add_filter($filter_name, 'tribe_export_custom_add_columns', 100);
    add_filter('tribe_events_tickets_attendees_table_column', 'tribe_export_custom_populate_columns', 10, 3);
}

add_action('tribe_events_tickets_generate_filtered_attendees_list', 'tribe_export_custom_set_up');

/**
 * Function to add custom columns to the export list.
 * These columns will appear in the exported CSV file.
 *
 * @param array $columns
 * @return array
 */
function tribe_export_custom_add_columns(array $columns): array {
    // Merge existing columns with custom columns
    return array_merge($columns, tribe_get_column_definitions());
}

/**
 * Retrieves WooCommerce order data based on order ID.
 * This data is then cached to optimize performance.
 *
 * @param int $order_id
 * @return array
 */
function tribe_get_order_data(int $order_id): array {
    static $orders_cache = [];

    if (isset($orders_cache[$order_id])) {
        return $orders_cache[$order_id];
    }

    $order = wc_get_order($order_id);
    
    if (!$order) {
        $orders_cache[$order_id] = [];
        return [];
    }

    // Pre-compute coupon data with codes and discounts string
    $coupon_data = tribe_get_coupon_data($order);

    $orders_cache[$order_id] = [
        'billing_first_name'   => $order->get_billing_first_name(),
        'billing_last_name'    => $order->get_billing_last_name(),
        'billing_address_1'    => $order->get_billing_address_1(),
        'billing_city'         => $order->get_billing_city(),
        'billing_state'        => $order->get_billing_state(),
        'billing_postcode'     => $order->get_billing_postcode(),
        'billing_phone'        => $order->get_billing_phone(),
        'billing_email'        => $order->get_billing_email(),
        'order_date'           => $order->get_date_created()->date('Y-m-d H:i:s'),
        'order_total'          => $order->get_total(),
        'payment_method'       => $order->get_payment_method(),
        'payment_method_title' => $order->get_payment_method_title(),
        'coupon_codes'         => $coupon_data['codes'],
        'coupon_discounts'     => $coupon_data['discounts']
    ];

    return $orders_cache[$order_id];
}

/**
 * Retrieves coupon data from an order.
 * Returns pre-formatted strings for codes and discounts.
 * Uses static caching to avoid redundant WC_Coupon instantiation.
 *
 * @param WC_Order $order
 * @return array ['codes' => string, 'discounts' => string]
 */
function tribe_get_coupon_data(WC_Order $order): array {
    static $coupon_cache = [];
    
    $cache_key = $order->get_id();
    
    if (isset($coupon_cache[$cache_key])) {
        return $coupon_cache[$cache_key];
    }

    $coupon_codes = $order->get_coupon_codes();
    
    if (empty($coupon_codes)) {
        $coupon_cache[$cache_key] = ['codes' => '', 'discounts' => ''];
        return $coupon_cache[$cache_key];
    }

    $codes = [];
    $discounts = [];

    foreach ($coupon_codes as $coupon_code) {
        $codes[] = $coupon_code;
        
        // Cache coupon object by code to avoid re-instantiation
        static $coupon_objects = [];
        
        if (!isset($coupon_objects[$coupon_code])) {
            $coupon_objects[$coupon_code] = new WC_Coupon($coupon_code);
        }
        
        $discounts[] = $coupon_objects[$coupon_code]->get_amount();
    }

    $coupon_cache[$cache_key] = [
        'codes'     => implode(', ', $codes),
        'discounts' => implode(', ', $discounts)
    ];

    return $coupon_cache[$cache_key];
}

/**
 * Populates the custom columns with relevant data.
 * This function fetches data from the order or other necessary information.
 *
 * @param mixed $value
 * @param array $item
 * @param string $column
 * @return mixed
 */
function tribe_export_custom_populate_columns($value, array $item, string $column) {
    $custom_columns = tribe_get_column_definitions();
    
    // Early return if column is not in our definitions
    if (!isset($custom_columns[$column])) {
        return $value;
    }

    $order_data = tribe_get_order_data((int) $item['order_id']);

    // Return empty string if order not found or data not available
    if (empty($order_data) || !isset($order_data[$column])) {
        return '';
    }

    return $order_data[$column];
}

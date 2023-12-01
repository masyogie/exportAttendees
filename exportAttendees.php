<?php
/**
 * Tribe, optimized version for adding user meta to the attendees csv export.
 **/

function tribe_get_column_definitions() {
    return [
        'billing_first_name' => 'Billing First Name',
        'billing_last_name' => 'Billing Last Name',
        'billing_address_1' => 'Billing Address 1',
        'billing_city' => 'Billing City',
        'billing_state' => 'Billing State',
        'billing_postcode' => 'Billing Zip',
        'billing_phone' => 'Phone',
        'billing_email' => 'Email',
        'order_date' => 'Order Date',
        'order_total' => 'Total Cost',
        'payment_method' => 'Payment Method',
        'payment_method_title' => 'Payment Method Title',
        'coupon_codes' => 'Coupon Codes',
        'coupon_discounts' => 'Coupon Discounts'
    ];
}

function tribe_export_custom_set_up($event_id) {
    if (!is_admin()) {
        $screen_base = 'tribe_events_page_tickets-attendees';
    } else {
        $screen = get_current_screen();
        $screen_base = $screen->base;
    }
    $filter_name = "manage_{$screen_base}_columns";

    add_filter($filter_name, 'tribe_export_custom_add_columns', 100);
    add_filter('tribe_events_tickets_attendees_table_column', 'tribe_export_custom_populate_columns', 10, 3);
}

add_action('tribe_events_tickets_generate_filtered_attendees_list', 'tribe_export_custom_set_up');

function tribe_export_custom_add_columns($columns) {
    $column_definitions = tribe_get_column_definitions();
    return array_merge($columns, $column_definitions);
}

function tribe_get_order_data($order_id) {
    static $orders_cache = [];

    if (!isset($orders_cache[$order_id])) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return [];
        }

        $orders_cache[$order_id] = [
            'order_date' => $order->order_date,
            'billing_first_name' => $order->billing_first_name,
            'billing_last_name' => $order->billing_last_name,
            'billing_address_1' => $order->billing_address_1,
            'billing_city' => $order->billing_city,
            'billing_state' => $order->billing_state,
            'billing_postcode' => $order->billing_postcode,
            'billing_phone' => $order->billing_phone,
            'billing_email' => $order->billing_email,
            'order_total' => $order->order_total,
            'payment_method' => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'coupons' => tribe_get_coupon_details($order)
        ];
    }

    return $orders_cache[$order_id];
}

function tribe_get_coupon_details($order) {
    $coupons = $order->get_used_coupons();
    $coupon_details = [];

    foreach ($coupons as $coupon_code) {
        $coupon = new WC_Coupon($coupon_code);
        $coupon_details[] = [
            'code' => $coupon_code,
            'discount' => $coupon->get_amount()
        ];
    }

    return $coupon_details;
}

function tribe_export_custom_populate_columns($value, $item, $column) {
    $order_data = tribe_get_order_data($item['order_id']);

    if (isset($order_data[$column])) {
        return $order_data[$column];
    }

    // Special handling for coupon columns
    if ($column === 'coupon_codes') {
        $coupon_codes = array_column($order_data['coupons'], 'code');
        return implode(', ', $coupon_codes);
    }
    if ($column === 'coupon_discounts') {
        $coupon_discounts = array_column($order_data['coupons'], 'discount');
        return implode(', ', $coupon_discounts);
    }

    return $value;
}

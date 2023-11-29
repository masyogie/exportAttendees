<?php
/**
 * Tribe, optimized version for adding user meta to the attendees csv export.
 **/
function tribe_export_custom_set_up($event_id)
{
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

function tribe_export_custom_add_columns($columns)
{
    $columns['billing_first_name'] = 'Billing First Name';
    $columns['billing_last_name'] = 'Billing Last Name';
    $columns['billing_address_1'] = 'Billing Address 1';
    $columns['billing_city'] = 'Billing City';
    $columns['billing_state'] = 'Billing State';
    $columns['billing_postcode'] = 'Billing Zip';
    $columns['billing_phone'] = 'Phone';
    $columns['billing_email'] = 'Email';

    $columns['order_date'] = 'Order Date';
    $columns['order_total'] = 'Total Cost';

    $columns['payment_method'] = 'Payment Method';
    $columns['payment_method_title'] = 'Payment Method Title';

    $columns['coupon_codes'] = 'Coupon Codes';
    $columns['coupon_discounts'] = 'Coupon Discounts';
    return $columns;
}

function tribe_export_custom_populate_columns($value, $item, $column)
{
    static $orders_cache = [];

    if (!isset($orders_cache[$item['order_id']])) {
        $order = wc_get_order($item['order_id']);
        if (!$order) {
            return $value;
        }

        $orders_cache[$item['order_id']] = [
            'order_date' => $order->order_date,
            'billing' => [
                'billing_first_name' => $order->billing_first_name,
                'billing_last_name' => $order->billing_last_name,
                'billing_address_1' => $order->billing_address_1,
                'billing_city' => $order->billing_city,
                'billing_state' => $order->billing_state,
                'billing_postcode' => $order->billing_postcode,
                'billing_phone' => $order->billing_phone,
                'billing_email' => $order->billing_email,
            ],
            'order_total' => $order->order_total,
            'payment_method' => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
        ];
    }

    if (!isset($orders_cache[$item['order_id']]['coupons'])) {
        $coupons = $order->get_used_coupons();
        $coupon_details = [];

        foreach ($coupons as $coupon_code) {
            $coupon = new WC_Coupon($coupon_code);
            $coupon_details[] = [
                'code' => $coupon_code,
                'discount' => $coupon->get_amount()
            ];
        }

        $orders_cache[$item['order_id']]['coupons'] = $coupon_details;
    }

    $order_data = $orders_cache[$item['order_id']];

    // Process column value
    switch ($column) {
        case 'order_date':
            $value = $order_data['order_date'];
            break;
        case 'billing_first_name':
            $value = $order_data['billing']['billing_first_name'];
            break;
        case 'billing_last_name':
            $value = $order_data['billing']['billing_last_name'];
            break;
        case 'billing_address_1':
            $value = $order_data['billing']['billing_address_1'];
            break;
        case 'billing_city':
            $value = $order_data['billing']['billing_city'];
            break;
        case 'billing_state':
            $value = $order_data['billing']['billing_state'];
            break;
        case 'billing_postcode':
            $value = $order_data['billing']['billing_postcode'];
            break;
        case 'billing_phone':
            $value = $order_data['billing']['billing_phone'];
            break;
        case 'billing_email':
            $value = $order_data['billing']['billing_email'];
            break;
        case 'order_total':
            $value = $order_data['order_total'];
            break;
        case 'payment_method':
            $value = $order_data['payment_method'];
            break;
        case 'payment_method_title':
            $value = $order_data['payment_method_title'];
            break;
        case 'coupon_codes':
            $coupon_codes = array_column($orders_cache[$item['order_id']]['coupons'], 'code');
            $value = implode(', ', $coupon_codes);
            break;
        case 'coupon_discounts':
            $coupon_discounts = array_column($orders_cache[$item['order_id']]['coupons'], 'discount');
            $value = implode(', ', $coupon_discounts);
            break;
    }


    return $value;
}
<?php
/**
 * Tribe, adding user meta to the attendees csv export
 * Source: https://theeventscalendar.com/support/forums/topic/adding-woocommerce-order-notes-to-attendees-export/
 *
 * Last updates: August 9, 2018
 **/
function tribe_export_custom_set_up( $event_id ) {

    //Add Handler for Community Tickets to Prevent Notices in Exports
    if ( ! is_admin() ) {
        $screen_base = 'tribe_events_page_tickets-attendees';
    }
    else {
        $screen = get_current_screen();
        $screen_base = $screen->base;
    }
    $filter_name = "manage_{$screen_base}_columns";

    add_filter( $filter_name, 'tribe_export_custom_add_columns', 100 );
    add_filter( 'tribe_events_tickets_attendees_table_column', 'tribe_export_custom_populate_columns', 10, 3 );
}

add_action( 'tribe_events_tickets_generate_filtered_attendees_list', 'tribe_export_custom_set_up' );

function tribe_export_custom_add_columns( $columns ) {
    $columns['billing_first_name'] = 'Billing First Name';
    $columns['billing_last_name'] = 'Billing Last Name';
    $columns['billing_company'] = 'Billing Company';
    $columns['billing_address_1'] = 'Billing Address 1';
    $columns['billing_address_2'] = 'Billing Address 2';
    $columns['billing_city'] = 'Billing City';
    $columns['billing_state'] = 'Billing State';
    $columns['billing_postcode'] = 'Billing Zip';
    $columns['billing_phone'] = 'Phone';
    $columns['billing_email'] = 'Email';

    $columns['shipping_first_name'] = 'Shipping First Name';
    $columns['shipping_last_name'] = 'Shipping Last Name';
    $columns['shipping_company'] = 'Shipping Company';
    $columns['shipping_address_1'] = 'Shipping Address 1';
    $columns['shipping_address_2'] = 'Shipping Address 2';
    $columns['shipping_city'] = 'Shipping City';
    $columns['shipping_state'] = 'Shipping State';
    $columns['shipping_postcode'] = 'Shipping Zip';

    $columns['order_comments'] = 'Order Comments';

    $columns['order_date'] = 'Order Date';
    $columns['order_notes'] = 'Order Notes';

    $columns['order_total'] = 'Total Cost';

    $columns['payment_method'] = 'Payment Method';
    $columns['payment_method_title'] = 'Payment Method Title';
    return $columns;
}

function tribe_export_custom_populate_columns( $value, $item, $column ) {

    /**
     * Check if order exists
     * If there is no order, then it's RSVP
     * so we return the original value
     */
    $is_order = wc_get_order( $item['order_id'] );
    if ( ! $is_order ) return $value;

    $order = new WC_Order( $item['order_id'] );

    $date = utf8_decode( $order->order_date );

    $bfirst = utf8_decode( $order->billing_first_name );
    $blast = utf8_decode( $order->billing_last_name );
    $bcompany = utf8_decode( $order->billing_company );
    $badd1 = utf8_decode( $order->billing_address_1 );
    $badd2 = utf8_decode( $order->billing_address_2 );
    $bcity = utf8_decode( $order->billing_city );
    $bstate = utf8_decode( $order->billing_state );
    $bzip = utf8_decode( $order->billing_postcode );
    $phone = utf8_decode( $order->billing_phone );
    $email = utf8_decode( $order->billing_email );

    $sfirst = utf8_decode( $order->shipping_first_name );
    $slast = utf8_decode( $order->shipping_last_name );
    $scompany = utf8_decode( $order->shipping_company );
    $sadd1 = utf8_decode( $order->shipping_address_1 );
    $sadd2 = utf8_decode( $order->shipping_address_2 );
    $scity = utf8_decode( $order->shipping_city );
    $sstate = utf8_decode( $order->shipping_state );
    $szip = utf8_decode( $order->shipping_postcode );

    $custcomments = utf8_decode( $order->order_comments );

    $notes = utf8_decode( $order->get_customer_order_notes );

    $ordertotal = utf8_decode( $order->order_total );

    $payment_method = $order->get_payment_method(); // Mendapatkan ID metode pembayaran
    $payment_method_title = $order->get_payment_method_title(); // Mendapatkan nama metode pembayaran

    if ( isset( $order ) ) {

        switch ( $column ) {

            case 'order_date':
                $value = $date;
                break;

            case 'billing_first_name':
                $value = $bfirst;
                break;

            case 'billing_last_name':
                $value = $blast;
                break;

            case 'billing_company':
                $value = $bcompany;
                break;

            case 'billing_address_1':
                $value = $badd1;
                break;

            case 'billing_address_2':
                $value = $badd2;
                break;

            case 'billing_city':
                $value = $bcity;
                break;

            case 'billing_state':
                $value = $bstate;
                break;

            case 'billing_postcode':
                $value = $bzip;
                break;

            case 'billing_phone':
                $value = $phone;
                break;

            case 'billing_email':
                $value = $email;
                break;


            case 'shipping_first_name':
                $value = $sfirst;
                break;

            case 'shipping_last_name':
                $value = $slast;
                break;

            case 'shipping_company':
                $value = $scompany;
                break;

            case 'shipping_address_1':
                $value = $sadd1;
                break;

            case 'shipping_address_2':
                $value = $sadd2;
                break;

            case 'billing_city':
                $value = $scity;
                break;

            case 'shipping_state':
                $value = $sstate;
                break;

            case 'shipping_postcode':
                $value = $szip;
                break;


            case 'order_comments':
                $value = $custcomments;
                break;

            case 'get_customer_order_notes':
                $value = $notes;
                break;

            case 'order_total':
                $value = $ordertotal;
                break;

            case 'payment_method':
                $value = $payment_method;
                break;

            case 'payment_method_title':
                $value = $payment_method_title;
                break;
        }
    }
    else {
        $value = '-';
    }
    return $value;
}
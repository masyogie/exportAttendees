<?php
/**
 * Tribe, optimized version for adding user meta to the attendees csv export.
 *
 * Columns are defined in a single configuration array (single source of truth).
 * Each column config includes 'label' (header text) and 'getter' (method to retrieve data).
 * Use 'callback' instead of 'getter' for special handling that requires custom logic.
 **/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Defines the column configuration for export.
 * Single source of truth for all custom columns.
 *
 * @return array Column configurations with 'label' and 'getter' or 'callback'
 */
function tribe_get_column_config(): array {
    return [
        'billing_first_name'   => ['label' => 'Billing First Name',   'getter' => 'get_billing_first_name'],
        'billing_last_name'    => ['label' => 'Billing Last Name',    'getter' => 'get_billing_last_name'],
        'billing_address_1'    => ['label' => 'Billing Address 1',    'getter' => 'get_billing_address_1'],
        'billing_city'         => ['label' => 'Billing City',         'getter' => 'get_billing_city'],
        'billing_state'        => ['label' => 'Billing State',        'getter' => 'get_billing_state'],
        'billing_postcode'     => ['label' => 'Billing Zip',          'getter' => 'get_billing_postcode'],
        'billing_phone'        => ['label' => 'Phone',                'getter' => 'get_billing_phone'],
        'billing_email'        => ['label' => 'Email',                'getter' => 'get_billing_email'],
        'order_date'           => ['label' => 'Order Date',           'getter' => 'get_date_created'],
        'order_total'          => ['label' => 'Total Cost',           'getter' => 'get_total'],
        'payment_method'       => ['label' => 'Payment Method',       'getter' => 'get_payment_method'],
        'payment_method_title' => ['label' => 'Payment Method Title', 'getter' => 'get_payment_method_title'],
        'coupon_codes'         => ['label' => 'Coupon Codes',         'callback' => 'tribe_get_coupon_codes_string'],
        'coupon_discounts'     => ['label' => 'Coupon Discounts',     'callback' => 'tribe_get_coupon_discounts_string'],
    ];
}

/**
 * Returns column definitions for headers (key => label mapping).
 *
 * @return array
 */
function tribe_get_column_definitions(): array {
    $config = tribe_get_column_config();
    $definitions = [];
    
    foreach ($config as $key => $column) {
        if (isset($column['label'])) {
            $definitions[$key] = $column['label'];
        }
    }
    
    return $definitions;
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
    return array_merge($columns, tribe_get_column_definitions());
}

/**
 * Safely call a method on WC_Order object.
 *
 * @param WC_Order|false $order
 * @param string $method
 * @param mixed $default
 * @return mixed
 */
function tribe_safe_order_call($order, string $method, $default = '') {
    if (!$order instanceof WC_Order) {
        return $default;
    }
    
    if (!method_exists($order, $method)) {
        return $default;
    }
    
    try {
        $result = $order->$method();
        
        // Handle WC_DateTime objects
        if ($result instanceof WC_DateTime) {
            return $result->date('Y-m-d H:i:s');
        }
        
        // Handle null/empty values
        if ($result === null) {
            return $default;
        }
        
        return $result;
    } catch (Exception $e) {
        // Log error if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Tribe Export Error: ' . $e->getMessage());
        }
        return $default;
    }
}

/**
 * Retrieves WooCommerce order data based on order ID.
 * Uses column configuration to dynamically fetch data.
 *
 * @param int $order_id
 * @return array
 */
function tribe_get_order_data(int $order_id): array {
    static $orders_cache = [];

    if (isset($orders_cache[$order_id])) {
        return $orders_cache[$order_id];
    }

    // Validate order ID
    if ($order_id <= 0) {
        $orders_cache[$order_id] = [];
        return [];
    }

    $order = wc_get_order($order_id);
    
    if (!$order instanceof WC_Order) {
        $orders_cache[$order_id] = [];
        return [];
    }

    $config = tribe_get_column_config();
    $data = [];

    foreach ($config as $key => $column) {
        if (!is_array($column)) {
            $data[$key] = '';
            continue;
        }

        try {
            if (isset($column['callback'])) {
                // Use custom callback for special handling (e.g., coupons)
                if (is_callable($column['callback'])) {
                    $data[$key] = call_user_func($column['callback'], $order);
                } else {
                    $data[$key] = '';
                }
            } elseif (isset($column['getter'])) {
                $data[$key] = tribe_safe_order_call($order, $column['getter'], '');
            } else {
                $data[$key] = '';
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Tribe Export Error for column '{$key}': " . $e->getMessage());
            }
            $data[$key] = '';
        }
    }

    $orders_cache[$order_id] = $data;
    return $data;
}

/**
 * Coupon cache for optimization.
 *
 * @param WC_Order $order
 * @return array ['codes' => array, 'discounts' => array]
 */
function tribe_get_cached_coupon_data(WC_Order $order): array {
    static $coupon_cache = [];
    static $coupon_objects = [];
    
    $cache_key = $order->get_id();
    
    if (isset($coupon_cache[$cache_key])) {
        return $coupon_cache[$cache_key];
    }

    $coupon_codes = [];
    
    try {
        if (method_exists($order, 'get_coupon_codes')) {
            $coupon_codes = $order->get_coupon_codes();
        }
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Tribe Export Error: ' . $e->getMessage());
        }
    }
    
    if (empty($coupon_codes) || !is_array($coupon_codes)) {
        $coupon_cache[$cache_key] = ['codes' => [], 'discounts' => []];
        return $coupon_cache[$cache_key];
    }

    $codes = [];
    $discounts = [];

    foreach ($coupon_codes as $coupon_code) {
        if (empty($coupon_code) || !is_string($coupon_code)) {
            continue;
        }
        
        $codes[] = $coupon_code;
        
        try {
            if (!isset($coupon_objects[$coupon_code])) {
                if (!class_exists('WC_Coupon')) {
                    continue;
                }
                $coupon_objects[$coupon_code] = new WC_Coupon($coupon_code);
            }
            
            $amount = $coupon_objects[$coupon_code]->get_amount();
            $discounts[] = is_numeric($amount) ? $amount : 0;
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Tribe Export Error for coupon '{$coupon_code}': " . $e->getMessage());
            }
            $discounts[] = 0;
        }
    }

    $coupon_cache[$cache_key] = [
        'codes'     => $codes,
        'discounts' => $discounts
    ];

    return $coupon_cache[$cache_key];
}

/**
 * Get formatted coupon codes string.
 *
 * @param WC_Order $order
 * @return string
 */
function tribe_get_coupon_codes_string(WC_Order $order): string {
    try {
        $coupon_data = tribe_get_cached_coupon_data($order);
        return implode(', ', $coupon_data['codes']);
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Get formatted coupon discounts string.
 *
 * @param WC_Order $order
 * @return string
 */
function tribe_get_coupon_discounts_string(WC_Order $order): string {
    try {
        $coupon_data = tribe_get_cached_coupon_data($order);
        return implode(', ', $coupon_data['discounts']);
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Populates the custom columns with relevant data.
 *
 * @param mixed $value
 * @param array $item
 * @param string $column
 * @return mixed
 */
function tribe_export_custom_populate_columns($value, array $item, string $column) {
    try {
        $config = tribe_get_column_config();
        
        // Early return if column is not in our definitions
        if (!isset($config[$column])) {
            return $value;
        }

        // Validate order_id
        if (!isset($item['order_id']) || empty($item['order_id'])) {
            return '';
        }

        $order_data = tribe_get_order_data((int) $item['order_id']);

        return $order_data[$column] ?? '';
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Tribe Export Error in populate_columns: " . $e->getMessage());
        }
        return '';
    }
}

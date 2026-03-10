# Tribe Events Custom CSV Export

This PHP snippet enhances the functionality of The Events Calendar plugin by Tribe, specifically tailored for exporting attendee data into a CSV file with additional custom fields from WooCommerce orders.

## Requirements

- **WordPress** 5.0 or higher
- **The Events Calendar** by Tribe (core plugin)
- **WooCommerce** 3.0 or higher (uses modern CRUD methods)

## Features

- **Custom Column Definitions**: Easily define and manage the columns to be exported.
- **Billing Information**: Export billing first name, last name, address, city, state, postcode, phone, and email.
- **Order Details**: Include order date, total cost, payment method, and payment method title.
- **Coupon Information**: Export coupon codes and discount amounts applied to orders.
- **Efficient Data Handling**: Utilizes caching for order data to optimize performance.
- **Dynamic Data Population**: Automatically populates columns with relevant attendee and order data.
- **Modern WooCommerce Compatibility**: Uses WooCommerce CRUD methods (compatible with WooCommerce 3.0+ and HPOS).
- **Extendable Structure**: Designed for easy customization and addition of new data fields.

## Installation

To use this snippet:

1. Add the PHP code to your theme's `functions.php` file or in a custom plugin.
2. The additional fields will automatically appear in the CSV export file when you export attendee data from The Events Calendar.

## Customization

You can customize the columns that are exported by modifying the `tribe_get_column_definitions()` function in the snippet. Each key in the returned array represents a column in the CSV file, with the key being the column identifier and the value being the column header.

### Default Exported Columns

- `billing_first_name` - Billing First Name
- `billing_last_name` - Billing Last Name
- `billing_address_1` - Billing Address 1
- `billing_city` - Billing City
- `billing_state` - Billing State
- `billing_postcode` - Billing Zip
- `billing_phone` - Phone
- `billing_email` - Email
- `order_date` - Order Date
- `order_total` - Total Cost
- `payment_method` - Payment Method
- `payment_method_title` - Payment Method Title
- `coupon_codes` - Coupon Codes
- `coupon_discounts` - Coupon Discounts

## Usage

After installation, the snippet works automatically when you export attendee data from The Events Calendar. The exported CSV file will include the custom columns defined in the `tribe_get_column_definitions()` function.

## Changelog

### Latest
- Updated to use WooCommerce CRUD methods for compatibility with WooCommerce 3.0+ and HPOS
- Replaced deprecated direct property access (`$order->billing_first_name`) with getter methods (`$order->get_billing_first_name()`)
- Replaced deprecated `$order->get_used_coupons()` with `$order->get_coupon_codes()` (WooCommerce 3.7+)
- Added safety check for `get_current_screen()` function

## Contributing

If you have suggestions for improvements or encounter any issues, please feel free to submit pull requests or open an issue. Contributions to enhance this snippet's functionality are welcome.

## License

This snippet is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

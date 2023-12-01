# Tribe Events Custom CSV Export

This PHP snippet enhances the functionality of The Events Calendar plugin by Tribe, specifically tailored for exporting attendee data into a CSV file with additional custom fields.

## Features

- **Custom Column Definitions**: Easily define and manage the columns to be exported.
- **Efficient Data Handling**: Utilizes caching for order data to optimize performance.
- **Dynamic Data Population**: Automatically populates columns with relevant attendee and order data.
- **Extendable Structure**: Designed for easy customization and addition of new data fields.

## Installation

To use this snippet:

1. Add the PHP code to your theme's `functions.php` file or in a custom plugin.
2. The additional fields will automatically appear in the CSV export file when you export attendee data from The Events Calendar.

## Customization

You can customize the columns that are exported by modifying the `tribe_get_column_definitions()` function in the snippet. Each key in the returned array represents a column in the CSV file, with the key being the column identifier and the value being the column header.

## Usage

After installation, the snippet works automatically when you export attendee data from The Events Calendar. The exported CSV file will include the custom columns defined in the `tribe_get_column_definitions()` function.

## Contributing

If you have suggestions for improvements or encounter any issues, please feel free to submit pull requests or open an issue. Contributions to enhance this snippet's functionality are welcome.

## License

This snippet is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

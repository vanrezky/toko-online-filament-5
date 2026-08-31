<?php

return [
    'title' => 'Import Products',
    'actions' => ['import' => 'Import Products', 'download_template' => 'Download Template', 'validate' => 'Validate File', 'submit' => 'Submit Products'],
    'sections' => ['upload' => 'Upload Template', 'preview' => 'Validation Review'],
    'upload_description' => 'Upload an Excel file, then validate it before products are created. Validation results expire after 1 hour.',
    'fields' => ['file' => 'Excel File', 'name' => 'Name', 'code' => 'Code', 'category' => 'Category', 'product_type' => 'Product Type', 'price' => 'Price', 'sale_price' => 'Discount Price', 'stock' => 'Stock', 'weight' => 'Weight', 'warehouse' => 'Warehouse', 'image_url' => 'Image URL'],
    'types' => ['physical' => 'Physical Product', 'digital' => 'Digital Product'],
    'table' => ['row' => 'Row', 'column' => 'Column', 'error' => 'Error'],
    'preview_expires' => 'Preview expires at :expires_at (maximum 1 hour).',
    'preview_count' => ':count products are ready for review and submission.',
    'confirmation' => ['heading' => 'Submit products?', 'description' => 'Products will be created from validated data. The token can only be used once.'],
    'notifications' => [
        'file_required' => 'Please upload an Excel file first.', 'invalid_file' => 'The file could not be read. Use the correct Excel template.',
        'validation_failed' => 'Validation failed', 'validation_failed_body' => ':count errors found. Fix the file and validate again.',
        'validation_success' => 'Validation succeeded', 'validation_success_body' => ':count products are ready for review.',
        'invalid_token' => 'The import token is invalid or expired.', 'busy' => 'The import is being processed. Please try again.',
        'submit_failed' => 'Products could not be created. The preview can be retried if it has not expired.',
        'submit_success' => 'Product import succeeded', 'submit_success_body' => ':count products were created.',
    ],
    'validation' => [
        'required' => 'This field is required.', 'required_physical' => 'Required for physical products.', 'numeric' => 'Must be a number greater than or equal to zero.', 'integer' => 'Must be a non-negative integer.',
        'sale_price' => 'Discount price must be lower than the normal price.', 'product_type' => 'Product type must be Physical or Digital.',
        'reference_not_found' => 'Name was not found or is not unique in the reference data.', 'url' => 'Must be a valid URL.',
        'max_name' => 'Product name may not exceed 255 characters.', 'too_many_rows' => 'The file exceeds the maximum of :max rows.',
    ],
];

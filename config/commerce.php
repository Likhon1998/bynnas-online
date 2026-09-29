<?php

return [

    /*
    | A storefront cart with items and no activity for this many minutes is
    | shown as abandoned and can be followed up by staff.
    */
    'abandoned_cart_minutes' => (int) env('ABANDONED_CART_MINUTES', 60),

    /*
    | Shipped orders not delivered after this many days raise a delivery issue
    | notification for admins.
    */
    'delivery_issue_days' => (int) env('DELIVERY_ISSUE_DAYS', 5),

    /*
    | Customer segment rules. Spend is net of discounts and excludes delivery
    | fees and cancelled / returned / refunded orders.
    */
    'segments' => [
        'new_days' => (int) env('SEGMENT_NEW_DAYS', 30),
        'vip_spend' => (float) env('SEGMENT_VIP_SPEND', 50000),
        'high_value_spend' => (float) env('SEGMENT_HIGH_VALUE_SPEND', 15000),
        'frequent_orders' => (int) env('SEGMENT_FREQUENT_ORDERS', 5),
        'inactive_days' => (int) env('SEGMENT_INACTIVE_DAYS', 90),
    ],

];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Yearly Stock Closing Date
    |--------------------------------------------------------------------------
    |
    | Stock on warehouse_stocks page is calculated as of this date.
    | Transactions after this date do not affect displayed qty or value.
    |
    */
    'yearly_closing_date' => env('STOCK_CLOSING_DATE', '2026-06-21'),
];

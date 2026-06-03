<?php

if (! function_exists('formatRupiah')) {
    function formatRupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}

if (! function_exists('formatQty')) {
    function formatQty(float $qty): string
    {
        if ($qty == (int) $qty) {
            return number_format((int) $qty, 0, ',', '.');
        }

        return rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',');
    }
}

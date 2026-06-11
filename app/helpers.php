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

if (! function_exists('terbilang')) {
    function terbilang($angka)
    {
        $angka = abs((float) $angka);
        $baca = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        $temp = '';
        if ($angka < 12) {
            $temp = ' '.$baca[(int) $angka];
        } elseif ($angka < 20) {
            $temp = terbilang($angka - 10).' belas';
        } elseif ($angka < 100) {
            $temp = terbilang((int) ($angka / 10)).' puluh'.terbilang($angka % 10);
        } elseif ($angka < 200) {
            $temp = ' seratus'.terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $temp = terbilang((int) ($angka / 100)).' ratus'.terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $temp = ' seribu'.terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $temp = terbilang((int) ($angka / 1000)).' ribu'.terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $temp = terbilang((int) ($angka / 1000000)).' juta'.terbilang($angka % 1000000);
        } elseif ($angka < 1000000000000) {
            $temp = terbilang((int) ($angka / 1000000000)).' milyar'.terbilang(fmod($angka, 1000000000));
        }

        return $temp;
    }
}

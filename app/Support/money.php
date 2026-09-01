<?php

if (! function_exists('mad')) {
    /**
     * Format integer centimes as a Moroccan dirham string: 64500 -> "645 MAD".
     * Matches the prototype's Intl.NumberFormat('fr-FR') output.
     */
    function mad(int $cents): string
    {
        return number_format($cents / 100, 0, ',', ' ').' MAD';
    }
}

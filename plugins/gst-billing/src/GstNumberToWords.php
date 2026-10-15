<?php

namespace Plugins\GstBilling\Src;

class GstNumberToWords
{
    public static function convert(float $amount): string
    {
        $amount = number_format($amount, 2, '.', '');
        $parts = explode('.', $amount);
        $rupees = (int) $parts[0];
        $paise = (int) $parts[1];

        $rupeesText = self::convertToWords($rupees);
        $paiseText = $paise > 0 ? ' and ' . self::convertToWords($paise) . ' Paise' : '';

        if (empty($rupeesText)) {
            $rupeesText = 'Zero';
        }

        $text = 'Rupees ' . $rupeesText . $paiseText . ' Only';
        return preg_replace('/\s+/', ' ', trim($text));
    }

    private static function convertToWords(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        $words = [];
        $crores = (int) ($number / 10000000);
        $number %= 10000000;

        $lakhs = (int) ($number / 100000);
        $number %= 100000;

        $thousands = (int) ($number / 1000);
        $number %= 1000;

        $hundreds = (int) ($number / 100);
        $number %= 100;

        if ($crores > 0) {
            $words[] = self::getBelowHundred($crores) . ' Crore';
        }

        if ($lakhs > 0) {
            $words[] = self::getBelowHundred($lakhs) . ' Lakh';
        }

        if ($thousands > 0) {
            $words[] = self::getBelowHundred($thousands) . ' Thousand';
        }

        if ($hundreds > 0) {
            $words[] = self::getBelowHundred($hundreds) . ' Hundred';
        }

        if ($number > 0) {
            $words[] = self::getBelowHundred($number);
        }

        return implode(' ', $words);
    }

    private static function getBelowHundred(int $number): string
    {
        $ones = [
            1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
        ];

        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
        ];

        if ($number < 20) {
            return $ones[$number] ?? '';
        }

        $tenPart = (int) ($number / 10);
        $onePart = $number % 10;

        $text = $tens[$tenPart] ?? '';
        if ($onePart > 0) {
            $text .= ' ' . ($ones[$onePart] ?? '');
        }

        return trim($text);
    }
}

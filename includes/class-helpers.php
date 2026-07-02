<?php
/**
 * Moaveze Plus - Shared Formatting Helpers
 *
 * - Gregorian -> Jalali (Shamsi) date conversion, since WordPress core
 *   dates are always stored/returned in Gregorian and there is no
 *   built-in conversion.
 * - Short price formatting (میلیون / میلیارد) so large Toman values
 *   like 45,555,676,789 are shown as "۴۵.۶ میلیارد تومان" instead of a
 *   long string of digits.
 *
 * These are used everywhere a listing price or date is rendered:
 * single listing page, archive/listing cards, admin dashboard/matches/
 * offers/consultants/monetization views, and the frontend My Offers /
 * notifications lists.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Helpers {

    /**
     * Persian digit map for converting Latin 0-9 to ۰-۹ in final output.
     */
    private static $persian_digits = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');

    /**
     * Convert a Gregorian date (any strtotime()-parsable string, or a
     * Unix timestamp) to a Jalali (Shamsi) date string.
     *
     * @param string|int $date   MySQL datetime string, date string, or timestamp.
     * @param string     $format 'Y/m/d', 'Y/m/d H:i', or 'd F Y' (F = Persian month name).
     * @param bool       $persian_digits Whether to render the numbers using Persian digits (۱۲۳).
     * @return string
     */
    public static function jalali_date($date, $format = 'Y/m/d', $persian_digits = true) {
        $timestamp = is_numeric($date) ? (int) $date : strtotime($date);
        if (!$timestamp) return '';

        // Apply the site's configured timezone offset so displayed dates
        // match what wp_date()/get_the_date() would show, not raw UTC.
        $timestamp += (get_option('gmt_offset') * 3600);

        list($gy, $gm, $gd) = array(
            (int) gmdate('Y', $timestamp),
            (int) gmdate('n', $timestamp),
            (int) gmdate('j', $timestamp),
        );

        list($jy, $jm, $jd) = self::gregorian_to_jalali($gy, $gm, $gd);

        $month_names = array(
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
            5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
            9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
        );

        $replacements = array(
            'Y' => $jy,
            'y' => substr((string) $jy, -2),
            'm' => sprintf('%02d', $jm),
            'n' => $jm,
            'd' => sprintf('%02d', $jd),
            'j' => $jd,
            'F' => $month_names[$jm],
            'H' => gmdate('H', $timestamp),
            'i' => gmdate('i', $timestamp),
        );

        $output = strtr($format, $replacements);

        return $persian_digits ? self::to_persian_digits($output) : $output;
    }

    /**
     * Jalali equivalent of WordPress's human_time_diff() - e.g.
     * "۳ ساعت پیش", "۲ روز پیش". Falls back to a short Jalali date for
     * anything older than ~30 days so it doesn't say "۴۵ روز پیش".
     */
    public static function jalali_time_diff($date) {
        $timestamp = is_numeric($date) ? (int) $date : strtotime($date);
        if (!$timestamp) return '';

        $diff = time() - $timestamp;
        if ($diff < 0) $diff = 0;

        $units = array(
            array(60, 'لحظاتی'),
            array(3600, 'دقیقه', 60),
            array(86400, 'ساعت', 3600),
            array(2592000, 'روز', 86400),
        );

        if ($diff < 60) return 'لحظاتی پیش';

        foreach (array(
            array(2592000, 86400, 'روز'),
            array(86400, 3600, 'ساعت'),
            array(3600, 60, 'دقیقه'),
        ) as $u) {
            list($max, $div, $label) = $u;
            if ($diff < $max) {
                $count = max(1, floor($diff / $div));
                return self::to_persian_digits((string) $count) . ' ' . $label . ' پیش';
            }
        }

        // Older than 30 days: show the actual Jalali date instead of a
        // vague "X روز پیش".
        return self::jalali_date($timestamp, 'Y/m/d');
    }

    /**
     * Convert a string of Latin digits to Persian digits for display.
     */
    public static function to_persian_digits($string) {
        return strtr((string) $string, array(
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ));
    }

    /**
     * Core Gregorian -> Jalali algorithm (jdf.scr.ir public-domain
     * implementation, widely used in Iranian WordPress plugins).
     */
    private static function gregorian_to_jalali($gy, $gm, $gd) {
        $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + intdiv(($gy2 + 3), 4) - intdiv(($gy2 + 99), 100)
            + intdiv(($gy2 + 399), 400) + $gd + $g_d_m[$gm - 1];

        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv(($days - 1), 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv(($days - 186), 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return array($jy, $jm, $jd);
    }

    /**
     * Short price formatter: renders large Toman amounts using
     * میلیون/میلیارد units instead of long digit strings, e.g.:
     *   14,000,000,000  -> "۱۴ میلیارد تومان"
     *   45,555,676,789  -> "۴۵.۶ میلیارد تومان"
     *   6,500,000       -> "۶.۵ میلیون تومان"
     *   850,000         -> "۸۵۰,۰۰۰ تومان"
     *
     * @param int|float $amount
     * @param bool      $with_unit   Append " تومان" at the end.
     * @param bool      $persian_digits
     * @return string
     */
    public static function short_price($amount, $with_unit = true, $persian_digits = true) {
        $amount = (float) $amount;
        $suffix = $with_unit ? ' تومان' : '';

        if ($amount <= 0) {
            return self::maybe_persian('0' . $suffix, $persian_digits);
        }

        if ($amount >= 1000000000000) { // 1 trillion+
            $formatted = self::trim_decimal($amount / 1000000000000) . ' هزار میلیارد' . $suffix;
        } elseif ($amount >= 1000000000) { // 1 billion+ (میلیارد)
            $formatted = self::trim_decimal($amount / 1000000000) . ' میلیارد' . $suffix;
        } elseif ($amount >= 1000000) { // 1 million+ (میلیون)
            $formatted = self::trim_decimal($amount / 1000000) . ' میلیون' . $suffix;
        } else {
            $formatted = number_format($amount) . $suffix;
        }

        return self::maybe_persian($formatted, $persian_digits);
    }

    /**
     * Round to at most 1 decimal place and drop a trailing ".0".
     */
    private static function trim_decimal($num) {
        $rounded = round($num, 1);
        if ($rounded == (int) $rounded) {
            return (string) (int) $rounded;
        }
        return rtrim(rtrim(number_format($rounded, 1), '0'), '.');
    }

    /**
     * Apply Persian digit conversion only to the numeric portions of a
     * string, leaving the Persian words (میلیارد, تومان...) untouched -
     * strtr on the whole string works fine here since it only replaces
     * ASCII 0-9 characters.
     */
    private static function maybe_persian($string, $persian_digits) {
        return $persian_digits ? self::to_persian_digits($string) : $string;
    }
}

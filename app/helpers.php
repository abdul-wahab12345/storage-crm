<?php

if (! function_exists('ordinal')) {
    function ordinal(int $number): string
    {
        $suffixes = ['th', 'st', 'nd', 'rd'];
        $mod = $number % 100;
        $suffix = ($mod >= 11 && $mod <= 13) ? 'th' : ($suffixes[$mod % 10] ?? 'th');
        return $number . $suffix;
    }
}

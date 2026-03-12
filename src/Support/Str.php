<?php

declare(strict_types=1);

namespace Velolia\Support;

class Str
{
    /**
     * Generate a random string of a given length.
     *
     * @param  int  $length
     * @return string
     */
    public static function random(int $length = 16): string
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    /**
     * Generate slug from string.
     */
    public static function slug(string $string): string
    {
        return strtolower(trim(preg_replace('/[^0-9a-z]+/i', '-', html_entity_decode(preg_replace('/[\s_\-]+/', ' ', $string), ENT_QUOTES, 'UTF-8')), '- '));
    }
}

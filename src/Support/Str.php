<?php

declare(strict_types=1);

namespace Velolia\Support;

class Str
{
    public static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }

    public static function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }

    public static function snake(string $value): string
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $value));
        }
        
        return $value;
    }
    
    public static function random(int $length): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';

        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        return $string;
    }

    public static function slug($string, $separator = '-')
    {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        $slug = strtolower($string);
        $slug = preg_replace('/[^a-z0-9-]+/u', $separator, $slug);
        $slug = preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, $slug);
        $slug = trim($slug, $separator);
        return $slug;
    }

    /**
     * Convert a value to studly caps case (PascalCase).
     *
     * @param string $value
     * @return string
     */
    public static function studly(string $value): string
    {
        $words = explode(' ', str_replace(['-', '_'], ' ', $value));
        
        $studlyWords = array_map(function ($word) {
            return ucfirst(strtolower($word));
        }, $words);
        
        return implode('', $studlyWords);
    }

    public static function limit(string $value, int $limit = 100, string $end = ' ...'): string
    {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit, 'UTF-8')) . $end;
    }
}
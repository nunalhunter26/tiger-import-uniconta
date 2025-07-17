<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Helper;

class FileHelper
{
    public static function filterFileName(string $fileName, bool $beautify = true): string
    {
        $fileName = preg_replace(
            '~[<>:"/\\\|?*]|[\x00-\x1F]|[\x7F\xA0\xAD]|[#\[\]@!$&\'()+,;=]|[{}^\~`]~x',
            '-', $fileName);

        $fileName = ltrim($fileName, '.-');
        if ($beautify) $fileName = self::beautifyFileName($fileName);
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        return mb_strcut(pathinfo($fileName, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($fileName)) . ($ext ? '.' . $ext : '');
    }

    private static function beautifyFileName(string $fileName): String
    {
        $fileName = preg_replace([
            '/ +/',
            '/_+/',
            '/-+/'
        ], '-', $fileName);

        $fileName = preg_replace([
            '/-*\.-*/',
            '/\.{2,}/'
        ], '.', $fileName);

        $fileName = mb_strtolower($fileName, mb_detect_encoding($fileName));
        return trim($fileName, '.-');
    }
}
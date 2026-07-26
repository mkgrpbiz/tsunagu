<?php

namespace App\Services;

class AppIconGenerator
{
    private const SIZES = [
        16 => 'favicon-16x16.png',
        32 => 'favicon-32x32.png',
        180 => 'apple-touch-icon.png',
        192 => 'icon-192.png',
        512 => 'icon-512.png',
    ];

    /**
     * アップロードされた元画像をそのままリサイズしてfavicon/PWAアイコン一式をpublic/直下に書き出す。
     * クロップ等の加工は行わない（ユーザー提供の画像は無加工で使う方針）。
     */
    public function generate(string $sourceAbsolutePath): void
    {
        $source = imagecreatefromstring(file_get_contents($sourceAbsolutePath));

        foreach (self::SIZES as $size => $filename) {
            $dst = imagecreatetruecolor($size, $size);
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
            imagecopyresampled(
                $dst, $source,
                0, 0, 0, 0,
                $size, $size,
                imagesx($source), imagesy($source),
            );
            imagepng($dst, public_path($filename));
            imagedestroy($dst);
        }

        imagedestroy($source);
    }
}

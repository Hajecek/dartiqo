<?php

declare(strict_types=1);

namespace App\Services;

final class AvatarStorage
{
    private const DIR = 'uploads/avatars';
    private const SIZE = 256;
    private const MAX_BYTES = 2 * 1024 * 1024;

    /**
     * Uloží fotku z data URL (výstup canvasu) a vrátí relativní cestu.
     * Obrázek se vždy znovu zakóduje, takže se do úložiště nedostane nic jiného než čistý JPEG.
     */
    public function storeDataUrl(string $dataUrl): string
    {
        if (!preg_match('#^data:image/(jpeg|png|webp);base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) {
            throw new \InvalidArgumentException('Profilová fotka má neplatný formát.');
        }
        $binary = base64_decode($m[2], true);
        if ($binary === false || strlen($binary) > self::MAX_BYTES) {
            throw new \InvalidArgumentException('Profilová fotka může mít nejvýše 2 MB.');
        }
        $info = @getimagesizefromstring($binary);
        $source = $info !== false ? @imagecreatefromstring($binary) : false;
        if ($source === false) {
            throw new \InvalidArgumentException('Soubor není platný obrázek.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);
        $target = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            (int) (($width - $side) / 2),
            (int) (($height - $side) / 2),
            self::SIZE,
            self::SIZE,
            $side,
            $side
        );

        $dir = dirname(__DIR__, 2) . '/' . self::DIR;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Nelze vytvořit složku pro profilové fotky.');
        }
        $relative = self::DIR . '/' . bin2hex(random_bytes(16)) . '.jpg';
        if (!imagejpeg($target, dirname(__DIR__, 2) . '/' . $relative, 85)) {
            throw new \RuntimeException('Profilovou fotku se nepodařilo uložit.');
        }

        return $relative;
    }

    public function delete(?string $relative): void
    {
        if ($relative === null || !str_starts_with($relative, self::DIR . '/')) {
            return;
        }
        $file = dirname(__DIR__, 2) . '/' . $relative;
        if (is_file($file)) {
            unlink($file);
        }
    }
}

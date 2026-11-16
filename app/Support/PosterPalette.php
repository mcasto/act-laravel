<?php

namespace App\Support;

/**
 * Picks a readable color palette from a show's poster, for the plain
 * ("Build without AI") audition page — resources/views/auditions/notice.blade.php.
 *
 * The poster is shrunk to a thumbnail and its pixels grouped into color
 * buckets. From those:
 *  - accent: the most prominent vivid color (saturation weighted by area)
 *  - dark:   the most common dark color, else the accent darkened
 * Every color is then adjusted until it meets WCAG contrast against what it
 * sits on (4.5:1 for text), so a pastel or low-contrast poster still gives a
 * readable page. Without a usable poster the original black/red palette is
 * used.
 */
class PosterPalette
{
    /** The flyer's original colors — used when there's no poster or no vivid color. */
    public const DEFAULT = [
        'dark' => '#000000',        // banner background, headings, borders, dark button
        'on_dark' => '#ffffff',     // text on the banner
        'on_dark_accent' => '#ff4d5a', // kicker line on the banner
        'accent' => '#b0000e',      // times, buttons, links, role borders (readable on white)
        'on_accent' => '#ffffff',   // button text
        'tint' => '#f7eeee',        // contact box background
    ];

    private const SAMPLE_WIDTH = 80;

    public static function fromFile(?string $path): array
    {
        if (! $path || ! is_file($path)) {
            return self::DEFAULT;
        }

        try {
            $buckets = self::buckets($path);
        } catch (\Throwable $e) {
            logger()->warning("PosterPalette: couldn't read {$path}: {$e->getMessage()}");
            return self::DEFAULT;
        }

        if (! $buckets) {
            return self::DEFAULT;
        }

        $total = array_sum(array_column($buckets, 'count'));

        // Highlight: the poster's most prominent vivid color, any hue — used on the dark banner.
        // Accent: the same, but not yellow — yellow only gets readable on white by darkening,
        // which turns it olive — so a yellow poster's accent comes from its next color.
        [$highlight] = self::mostVivid($buckets, $total);
        [$accent, $best] = self::mostVivid($buckets, $total, skipYellow: true);

        // Too little of the poster is vivid to call it the poster's color.
        if (! $accent || $best < 0.004) {
            return self::DEFAULT;
        }

        // Dark: the most common genuinely dark color covering a real share of the poster.
        $dark = null;
        foreach ($buckets as $b) {
            [, , $l] = self::toHsl($b['rgb']);
            if ($l <= 0.22 && $b['count'] / $total >= 0.03 && (! $dark || $b['count'] > $dark['count'])) {
                $dark = $b;
            }
        }
        $dark = $dark['rgb'] ?? self::withLightness($accent, 0.14);

        // Headings/borders/banner must be dark enough for white text and to read on white.
        $dark = self::darkenUntil($dark, [255, 255, 255], 7.0);

        $accentOnWhite = self::darkenUntil($accent, [255, 255, 255], 4.5);

        // An accent that ends up looking like the dark color (a poster mostly
        // in one deep color) would make buttons vanish against the banner, so
        // take the best color of a different hue instead, if the poster has one.
        if (self::distance($accentOnWhite, $dark) < 60) {
            [$other] = self::mostVivid($buckets, $total, skipYellow: true, avoidHue: self::toHsl($accent)[0]);
            if ($other) {
                $accent = $other;
                $accentOnWhite = self::darkenUntil($accent, [255, 255, 255], 4.5);
            }
        }
        $onDarkAccent = self::lightenUntil($highlight, $dark, 4.5);
        [$h, $s] = self::toHsl($accent);

        return [
            'dark' => self::hex($dark),
            'on_dark' => '#ffffff',
            'on_dark_accent' => self::contrast($onDarkAccent, $dark) >= 4.5 ? self::hex($onDarkAccent) : '#ffffff',
            'accent' => self::hex($accentOnWhite),
            'on_accent' => self::contrast([255, 255, 255], $accentOnWhite) >= 4.5 ? '#ffffff' : '#000000',
            'tint' => self::hex(self::fromHsl($h, min($s, 0.6), 0.95)),
        ];
    }

    /**
     * The bucket with the best vividness score (saturation, closeness to mid
     * lightness, share of the poster).
     *
     * @return array{0: ?int[], 1: float} [rgb, score]
     */
    private static function mostVivid(array $buckets, int $total, bool $skipYellow = false, ?float $avoidHue = null): array
    {
        $pick = null;
        $best = 0;

        foreach ($buckets as $b) {
            [$h, $s, $l] = self::toHsl($b['rgb']);
            if ($s < 0.35 || $l < 0.15 || $l > 0.85 || ($skipYellow && $h >= 45 && $h <= 70)) {
                continue;
            }
            if ($avoidHue !== null && min(abs($h - $avoidHue), 360 - abs($h - $avoidHue)) < 40) {
                continue;
            }
            $score = ($b['count'] / $total) * $s * (1 - abs($l - 0.5));
            if ($score > $best) {
                $best = $score;
                $pick = $b['rgb'];
            }
        }

        return [$pick, $best];
    }

    /**
     * Pixels of a thumbnail grouped by 4 bits per channel, each bucket's color
     * being the average of its pixels; near-white/near-grey paper isn't skipped
     * here — the accent/dark filters above decide what counts.
     *
     * @return array<int, array{rgb: int[], count: int}>
     */
    private static function buckets(string $path): array
    {
        $src = @imagecreatefromstring(file_get_contents($path));
        if (! $src) {
            return [];
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $tw = min(self::SAMPLE_WIDTH, $w);
        $th = max(1, (int) round($h * $tw / $w));

        $img = imagecreatetruecolor($tw, $th);
        imagecopyresampled($img, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
        imagedestroy($src);

        $sums = [];
        for ($y = 0; $y < $th; $y++) {
            for ($x = 0; $x < $tw; $x++) {
                $c = imagecolorat($img, $x, $y);
                $r = ($c >> 16) & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = $c & 0xFF;
                $key = (($r >> 4) << 8) | (($g >> 4) << 4) | ($b >> 4);
                $sums[$key] ??= [0, 0, 0, 0];
                $sums[$key][0] += $r;
                $sums[$key][1] += $g;
                $sums[$key][2] += $b;
                $sums[$key][3]++;
            }
        }
        imagedestroy($img);

        return array_values(array_map(fn ($s) => [
            'rgb' => [intdiv($s[0], $s[3]), intdiv($s[1], $s[3]), intdiv($s[2], $s[3])],
            'count' => $s[3],
        ], $sums));
    }

    /** Lower the lightness until $rgb reaches $ratio against $bg (or hits black). */
    private static function darkenUntil(array $rgb, array $bg, float $ratio): array
    {
        [$h, $s, $l] = self::toHsl($rgb);
        while (self::contrast($rgb, $bg) < $ratio && $l > 0) {
            $l = max(0, $l - 0.02);
            $rgb = self::fromHsl($h, $s, $l);
        }
        return $rgb;
    }

    /** Raise the lightness until $rgb reaches $ratio against $bg (or hits white). */
    private static function lightenUntil(array $rgb, array $bg, float $ratio): array
    {
        [$h, $s, $l] = self::toHsl($rgb);
        while (self::contrast($rgb, $bg) < $ratio && $l < 1) {
            $l = min(1, $l + 0.02);
            $rgb = self::fromHsl($h, $s, $l);
        }
        return $rgb;
    }

    private static function withLightness(array $rgb, float $l): array
    {
        [$h, $s] = self::toHsl($rgb);
        return self::fromHsl($h, $s, $l);
    }

    /** Straight-line distance between two colors in RGB space (0–441). */
    private static function distance(array $a, array $b): float
    {
        return sqrt(($a[0] - $b[0]) ** 2 + ($a[1] - $b[1]) ** 2 + ($a[2] - $b[2]) ** 2);
    }

    /** WCAG contrast ratio, 1–21. */
    public static function contrast(array $a, array $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);
        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    private static function luminance(array $rgb): float
    {
        $c = array_map(function ($v) {
            $v /= 255;
            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, $rgb);
        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    /** @return float[] [h 0–360, s 0–1, l 0–1] */
    private static function toHsl(array $rgb): array
    {
        [$r, $g, $b] = array_map(fn ($v) => $v / 255, $rgb);
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;

        if ($d == 0) {
            return [0, 0, $l];
        }

        $s = $d / (1 - abs(2 * $l - 1));
        $h = match ($max) {
            $r => 60 * fmod(($g - $b) / $d, 6),
            $g => 60 * (($b - $r) / $d + 2),
            default => 60 * (($r - $g) / $d + 4),
        };

        return [$h < 0 ? $h + 360 : $h, $s, $l];
    }

    /** @return int[] */
    private static function fromHsl(float $h, float $s, float $l): array
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (true) {
            $h < 60 => [$c, $x, 0],
            $h < 120 => [$x, $c, 0],
            $h < 180 => [0, $c, $x],
            $h < 240 => [0, $x, $c],
            $h < 300 => [$x, 0, $c],
            default => [$c, 0, $x],
        };

        return array_map(fn ($v) => (int) round(max(0, min(1, $v + $m)) * 255), [$r, $g, $b]);
    }

    private static function hex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x', ...$rgb);
    }
}

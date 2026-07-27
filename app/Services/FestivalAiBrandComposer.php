<?php

namespace App\Services;

use App\Models\StorageSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FestivalAiBrandComposer
{
    public function compose(string $imageBinary, array $business, array $chrome): string
    {
        if (
            ($chrome['overlay_enabled'] ?? true) === false
            || $business === []
            || !function_exists('imagecreatefromstring')
        ) {
            return $imageBinary;
        }

        $canvas = @imagecreatefromstring($imageBinary);
        if ($canvas === false) {
            return $imageBinary;
        }

        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $width = imagesx($canvas);
        $height = imagesy($canvas);
        if ($width < 120 || $height < 120) {
            imagedestroy($canvas);
            return $imageBinary;
        }

        $logo = $this->logoImage((string) ($business['logo_path'] ?? ''));
        $businessName = trim((string) ($business['name'] ?? ''));
        $contacts = $this->contacts(
            $business,
            $this->boundedInteger($chrome['max_contact_items'] ?? 4, 0, 8)
        );

        $hasHeader = $logo !== null || $businessName !== '';
        $hasFooter = $contacts !== [];
        if (!$hasHeader && !$hasFooter) {
            imagedestroy($canvas);
            return $imageBinary;
        }

        $headerHeight = $hasHeader
            ? (int) round($height * $this->boundedInteger($chrome['header_height_percent'] ?? 12, 6, 20) / 100)
            : 0;
        $footerHeight = $hasFooter
            ? (int) round($height * $this->boundedInteger($chrome['footer_height_percent'] ?? 10, 6, 20) / 100)
            : 0;
        $configuredPanelStyle = (string) ($chrome['panel_style'] ?? 'adaptive');
        $panelStyle = in_array($configuredPanelStyle, ['adaptive', 'light', 'dark', 'none'], true)
            ? $configuredPanelStyle
            : 'adaptive';
        $configuredTextTone = (string) ($chrome['text_tone'] ?? 'auto');
        $textTone = in_array($configuredTextTone, ['auto', 'light', 'dark'], true)
            ? $configuredTextTone
            : 'auto';

        $headerPalette = $this->paletteForRegion($canvas, 0, max(1, $headerHeight), $panelStyle, $textTone);
        $footerPalette = $this->paletteForRegion(
            $canvas,
            max(0, $height - $footerHeight),
            max(1, $footerHeight),
            $panelStyle,
            $textTone
        );

        if ($hasHeader) {
            $this->drawPanel($canvas, 0, $headerHeight, $headerPalette);
            $this->drawHeader(
                $canvas,
                $logo,
                $businessName,
                $headerHeight,
                $headerPalette['text'],
                ($chrome['logo_position'] ?? 'left') === 'right'
            );
        }

        if ($hasFooter) {
            $top = $height - $footerHeight;
            $this->drawPanel($canvas, $top, $height, $footerPalette);
            $this->drawFooter($canvas, $contacts, $top, $footerHeight, $footerPalette['text']);
        }

        if ($logo !== null) {
            imagedestroy($logo);
        }

        ob_start();
        imagepng($canvas, null, 6);
        $composed = ob_get_clean();
        imagedestroy($canvas);

        return is_string($composed) && $composed !== '' ? $composed : $imageBinary;
    }

    private function drawHeader($canvas, $logo, string $businessName, int $height, array $textColor, bool $logoOnRight): void
    {
        $width = imagesx($canvas);
        $padding = max(12, (int) round($width * 0.025));
        $logoWidth = 0;
        $logoGap = max(8, (int) round($width * 0.015));

        if ($logo !== null) {
            $sourceWidth = imagesx($logo);
            $sourceHeight = imagesy($logo);
            // Mobile previews often scale a 1024px poster down to ~270px.
            // Give wide wordmarks enough height to remain recognisable there.
            $maxLogoHeight = max(28, (int) round($height * 0.78));
            $maxLogoWidth = max(48, (int) round($width * 0.29));
            $scale = min($maxLogoWidth / max(1, $sourceWidth), $maxLogoHeight / max(1, $sourceHeight), 1);
            $logoWidth = max(1, (int) round($sourceWidth * $scale));
            $logoHeight = max(1, (int) round($sourceHeight * $scale));
            $logoX = $logoOnRight ? $width - $padding - $logoWidth : $padding;
            $logoY = max(0, (int) round(($height - $logoHeight) / 2));
            imagecopyresampled(
                $canvas,
                $logo,
                $logoX,
                $logoY,
                0,
                0,
                $logoWidth,
                $logoHeight,
                $sourceWidth,
                $sourceHeight
            );
        }

        if ($businessName === '') {
            return;
        }

        $fontSize = max(14, min(44, (int) round($height * 0.36)));
        $availableWidth = $width - ($padding * 2) - ($logoWidth > 0 ? $logoWidth + $logoGap : 0);
        $businessName = $this->fitText($businessName, $fontSize, max(40, $availableWidth));
        $textX = $logoOnRight
            ? $padding
            : $padding + ($logoWidth > 0 ? $logoWidth + $logoGap : 0);
        $this->drawText(
            $canvas,
            $businessName,
            $fontSize,
            $textX,
            (int) round($height * 0.58),
            $textColor
        );
    }

    private function drawFooter($canvas, array $contacts, int $top, int $height, array $textColor): void
    {
        $width = imagesx($canvas);
        $padding = max(12, (int) round($width * 0.025));
        // Phone-only footers normally fit on one line. Use a materially
        // larger size for that common mobile case, then fall back to two
        // compact lines only when email/website data genuinely requires it.
        $fontSize = max(15, min(48, (int) round($height * 0.46)));
        $lines = $this->contactLines($contacts, $fontSize, $width - ($padding * 2));
        if (count($lines) > 1) {
            $fontSize = max(12, min(34, (int) round($height * 0.32)));
            $lines = $this->contactLines($contacts, $fontSize, $width - ($padding * 2));
        }
        $lineHeight = max(17, (int) round($fontSize * 1.4));
        $blockHeight = count($lines) * $lineHeight;
        $baseline = $top + max($lineHeight, (int) round(($height - $blockHeight) / 2) + $fontSize);

        foreach ($lines as $index => $line) {
            $this->drawText(
                $canvas,
                $line,
                $fontSize,
                $padding,
                $baseline + ($index * $lineHeight),
                $textColor
            );
        }
    }

    private function drawPanel($canvas, int $top, int $bottom, array $palette): void
    {
        if ($palette['panel'] === null || $bottom <= $top) {
            return;
        }

        [$red, $green, $blue, $alpha] = $palette['panel'];
        $color = imagecolorallocatealpha($canvas, $red, $green, $blue, $alpha);
        imagefilledrectangle($canvas, 0, $top, imagesx($canvas), $bottom, $color);
    }

    private function paletteForRegion($canvas, int $top, int $height, string $panelStyle, string $textTone): array
    {
        $isLight = $this->averageLuminance($canvas, $top, $height) >= 145;
        $resolvedPanel = $panelStyle === 'adaptive' ? ($isLight ? 'light' : 'dark') : $panelStyle;
        $resolvedText = $textTone === 'auto'
            ? ($resolvedPanel === 'none' ? ($isLight ? 'dark' : 'light') : ($resolvedPanel === 'light' ? 'dark' : 'light'))
            : $textTone;

        return [
            'panel' => match ($resolvedPanel) {
                // The provider can still place artwork a few pixels inside a
                // requested safe zone. These near-opaque adaptive panels make
                // the deterministic logo and contact details readable and
                // prevent that artwork from showing through the brand area.
                'light' => [255, 255, 255, 14],
                'dark' => [10, 16, 28, 14],
                default => null,
            },
            'text' => $resolvedText === 'dark' ? [22, 28, 45] : [255, 255, 255],
        ];
    }

    private function averageLuminance($canvas, int $top, int $regionHeight): float
    {
        $width = imagesx($canvas);
        $height = imagesy($canvas);
        $bottom = min($height, max($top + 1, $top + $regionHeight));
        $stepX = max(1, (int) floor($width / 20));
        $stepY = max(1, (int) floor(($bottom - $top) / 8));
        $total = 0.0;
        $samples = 0;

        for ($y = max(0, $top); $y < $bottom; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                $rgb = imagecolorat($canvas, $x, $y);
                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;
                $total += (0.2126 * $red) + (0.7152 * $green) + (0.0722 * $blue);
                $samples++;
            }
        }

        return $samples > 0 ? $total / $samples : 128.0;
    }

    private function contacts(array $business, int $limit): array
    {
        if ($limit === 0) {
            return [];
        }

        $contacts = [];
        foreach (['phones', 'emails', 'websites', 'addresses'] as $key) {
            foreach ((array) ($business[$key] ?? []) as $value) {
                if (is_string($value) && trim($value) !== '') {
                    $contacts[] = trim($value);
                }
            }
        }

        return array_slice(array_values(array_unique($contacts)), 0, $limit);
    }

    private function contactLines(array $contacts, int $fontSize, int $maxWidth): array
    {
        $lines = [];
        $current = '';

        foreach ($contacts as $contact) {
            $candidate = $current === '' ? $contact : $current . ' | ' . $contact;
            if ($current !== '' && $this->textWidth($candidate, $fontSize) > $maxWidth && count($lines) < 1) {
                $lines[] = $this->fitText($current, $fontSize, $maxWidth);
                $current = $contact;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $this->fitText($current, $fontSize, $maxWidth);
        }

        return array_slice($lines, 0, 2);
    }

    private function drawText($canvas, string $text, int $fontSize, int $x, int $baseline, array $rgb): void
    {
        $color = imagecolorallocate($canvas, $rgb[0], $rgb[1], $rgb[2]);
        $font = $this->fontPath();

        if ($font !== null && function_exists('imagettftext')) {
            imagettftext($canvas, $fontSize, 0, $x, $baseline, $color, $font, $text);
            return;
        }

        imagestring($canvas, 5, $x, max(0, $baseline - 15), $text, $color);
    }

    private function textWidth(string $text, int $fontSize): int
    {
        $font = $this->fontPath();
        if ($font !== null && function_exists('imagettfbbox')) {
            $box = imagettfbbox($fontSize, 0, $font, $text);
            if (is_array($box)) {
                return (int) abs($box[2] - $box[0]);
            }
        }

        return strlen($text) * 9;
    }

    private function fitText(string $text, int $fontSize, int $maxWidth): string
    {
        if ($this->textWidth($text, $fontSize) <= $maxWidth) {
            return $text;
        }

        $suffix = '...';
        while ($text !== '' && $this->textWidth($text . $suffix, $fontSize) > $maxWidth) {
            $text = function_exists('mb_substr') ? mb_substr($text, 0, -1) : substr($text, 0, -1);
        }

        return rtrim($text) . $suffix;
    }

    private function logoImage(string $path)
    {
        $contents = $this->assetContents($path);
        if ($contents === null) {
            return null;
        }

        $image = @imagecreatefromstring($contents);
        if ($image !== false) {
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        return $image === false ? null : $image;
    }

    private function assetContents(string $asset): ?string
    {
        $asset = trim($asset);
        if ($asset === '') {
            return null;
        }

        if (filter_var($asset, FILTER_VALIDATE_URL)) {
            try {
                $response = Http::timeout(15)->get($asset);
                return $response->successful() ? $response->body() : null;
            } catch (\Throwable) {
                return null;
            }
        }

        // Remove only a real leading uploads/ prefix. Never alter directory
        // names such as festival_ai_uploads in the middle of a valid path.
        $relative = preg_replace('#^/?uploads[\\\\/]#i', '', str_replace('\\', '/', $asset)) ?? $asset;
        $relative = ltrim($relative, '/');

        foreach ([public_path('uploads/' . $relative), base_path('uploads/' . $relative)] as $candidate) {
            if (is_file($candidate)) {
                $contents = file_get_contents($candidate);
                return $contents === false ? null : $contents;
            }
        }

        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
            $remotePath = 'uploads/' . $relative;
            if (Storage::disk('spaces')->exists($remotePath)) {
                return Storage::disk('spaces')->get($remotePath);
            }
        }

        return null;
    }

    private function fontPath(): ?string
    {
        static $font;
        static $resolved = false;
        if ($resolved) {
            return $font;
        }

        $resolved = true;
        foreach ([
            public_path('fonts/Poppins-Regular.ttf'),
            resource_path('fonts/Poppins-Regular.ttf'),
            'C:\\Windows\\Fonts\\arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ] as $candidate) {
            if (is_file($candidate)) {
                $font = $candidate;
                return $font;
            }
        }

        $font = null;
        return null;
    }

    private function boundedInteger($value, int $minimum, int $maximum): int
    {
        return max($minimum, min($maximum, (int) $value));
    }
}

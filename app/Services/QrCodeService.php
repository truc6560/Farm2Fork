<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    /**
     * Generate an ISO/IEC 18004 compliant SVG QR code with standard quiet zone.
     *
     * @param string $text Content/URL to encode
     * @param int $size Width/height in pixels
     * @param int $margin Quiet zone in modules (minimum 4 required by ISO/IEC 18004)
     * @return string Valid SVG markup ready for inline HTML embedding
     */
    public static function svg(string $text, int $size = 200, int $margin = 4): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $svg = $writer->writeString($text);

        // Strip standalone XML declaration for clean inline Blade embedding
        $svg = preg_replace('/<\?xml[^>]*\?>\s*/i', '', $svg);

        // Add responsive styling class and crisp rendering
        if (str_starts_with(trim($svg), '<svg')) {
            $svg = preg_replace(
                '/<svg /i',
                '<svg class="qr-svg mx-auto block max-w-full h-auto" shape-rendering="crispEdges" ',
                $svg,
                1
            );
        }

        return $svg;
    }

    /**
     * Generate an ISO/IEC 18004 compliant base64 PNG data-uri for <img> tags.
     *
     * @param string $text Content/URL to encode
     * @param int $size Width/height in pixels
     * @param int $margin Quiet zone in modules (minimum 4 required by ISO/IEC 18004)
     * @return string base64 data-uri
     */
    public static function pngDataUri(string $text, int $size = 200, int $margin = 4): string
    {
        $qrCode = Encoder::encode($text, ErrorCorrectionLevel::M());
        $matrix = $qrCode->getMatrix();
        $matrixWidth = $matrix->getWidth();
        $matrixHeight = $matrix->getHeight();

        $totalWidth = $matrixWidth + ($margin * 2);
        $totalHeight = $matrixHeight + ($margin * 2);
        $scale = max(1, (int)floor($size / $totalWidth));
        $imgWidth = $totalWidth * $scale;
        $imgHeight = $totalHeight * $scale;

        $im = imagecreatetruecolor($imgWidth, $imgHeight);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 15, 23, 42);

        imagefilledrectangle($im, 0, 0, $imgWidth, $imgHeight, $white);

        for ($y = 0; $y < $matrixHeight; $y++) {
            for ($x = 0; $x < $matrixWidth; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $x1 = ($x + $margin) * $scale;
                    $y1 = ($y + $margin) * $scale;
                    $x2 = $x1 + $scale - 1;
                    $y2 = $y1 + $scale - 1;
                    imagefilledrectangle($im, $x1, $y1, $x2, $y2, $black);
                }
            }
        }

        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);

        return 'data:image/png;base64,' . base64_encode($data);
    }
}

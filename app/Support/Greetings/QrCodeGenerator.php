<?php

namespace App\Support\Greetings;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR Code link ucapan: SVG bertema untuk tampilan, PNG resolusi tinggi untuk dicetak di kado fisik.
 * Koreksi galat level H agar tetap terbaca walau dicetak kecil atau sedikit rusak.
 */
class QrCodeGenerator
{
    /**
     * Semua jenis modul gelap, agar seluruh pola memakai satu warna.
     */
    private const DARK_MODULES = [
        QRMatrix::M_DARKMODULE, QRMatrix::M_DATA_DARK, QRMatrix::M_FINDER_DARK, QRMatrix::M_ALIGNMENT_DARK,
        QRMatrix::M_TIMING_DARK, QRMatrix::M_FORMAT_DARK, QRMatrix::M_VERSION_DARK, QRMatrix::M_FINDER_DOT,
        QRMatrix::M_SEPARATOR_DARK, QRMatrix::M_QUIETZONE_DARK, QRMatrix::M_LOGO_DARK,
    ];

    public function svg(string $url, string $color = '#281526'): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'eccLevel' => EccLevel::H,
            'quietzoneSize' => 2,
            'drawLightModules' => false,
            'drawCircularModules' => true,
            'circleRadius' => 0.42,
            'keepAsSquare' => [QRMatrix::M_FINDER_DARK, QRMatrix::M_FINDER_DOT, QRMatrix::M_ALIGNMENT_DARK],
            'connectPaths' => true,
            'moduleValues' => array_fill_keys(self::DARK_MODULES, $color),
        ]);

        return (new QRCode($options))->render($url);
    }

    public function png(string $url, int $scale = 16): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => false,
            'eccLevel' => EccLevel::H,
            'scale' => $scale,
            'quietzoneSize' => 4,
            'bgColor' => [255, 255, 255],
            'moduleValues' => array_fill_keys(self::DARK_MODULES, [40, 21, 38]),
        ]);

        return (new QRCode($options))->render($url);
    }
}

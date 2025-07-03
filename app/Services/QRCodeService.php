<?php

namespace App\Services;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

class QRCodeService
{
    public function generateQRCode(string $model, string $uuid): string
    {
        $validModels = ['student', 'parent', 'teacher', 'assistant'];
        if (!in_array($model, $validModels)) {
            throw new \InvalidArgumentException("Invalid model type: {$model}");
        }

        $url = route("{$model}.account.qr.scan", ['uuid' => $uuid]);
        $builder = new Builder(
            writer: new PngWriter(),
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 200,
            margin: 10,
            logoPath: file_exists($logoPath = public_path('assets/img/brand/navbar.png')) ? $logoPath : null,
            logoResizeToWidth: 50,
        );

        $result = $builder->build();

        return $result->getDataUri();
    }
}
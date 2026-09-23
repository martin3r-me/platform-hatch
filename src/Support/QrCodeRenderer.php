<?php

namespace Platform\Hatch\Support;

use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Erzeugt QR-Codes für öffentliche Intake-Links (Vorschau + Download).
 * SVG ist verlustfrei skalierbar (Druck), PNG für Office/Messenger.
 */
class QrCodeRenderer
{
    public const FORMATS = ['svg', 'png'];

    public function svg(string $data): string
    {
        return $this->render($data, QROutputInterface::MARKUP_SVG);
    }

    public function png(string $data): string
    {
        return $this->render($data, QROutputInterface::GDIMAGE_PNG);
    }

    private function render(string $data, string $outputType): string
    {
        $options = new QROptions([
            'outputType' => $outputType,
            'outputBase64' => false,
            'scale' => 20,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render($data);
    }
}

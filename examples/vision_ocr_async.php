<?php
// Async OCR for multi-page PDF documents

use JazzMax\YandexAi\Enums\OcrModel;
use JazzMax\YandexAi\Facades\YandexAi;

$result = YandexAi::vision()->recognizeDocument(
    pdfData:        file_get_contents('document.pdf'),
    model:          OcrModel::Page,
    timeoutSeconds: 120,  // Max wait for async processing
);

echo "Pages: {$result->pages}\n";
echo "Price: {$result->priceRub()} RUB\n";
echo $result->text();

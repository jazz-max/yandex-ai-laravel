<?php
// Vision OCR — image text recognition

use JazzMax\YandexAi\Enums\OcrModel;
use JazzMax\YandexAi\Facades\YandexAi;

$vision = YandexAi::vision();

// Simple text recognition
$result = $vision->recognizeText(
    imageData: file_get_contents('photo.jpg'),
    model:     OcrModel::Page,
);

echo $result->text();      // Recognized text
echo $result->priceRub();  // 0.132 RUB

// Passport recognition (returns structured entities)
$result = $vision->recognizeText(
    imageData: file_get_contents('passport.jpg'),
    model:     OcrModel::Passport,
);

echo $result->text();
// Фамилия: Иванов
// Имя: Иван
// Дата рождения: 01.01.1990
// ...

// Access raw entities
foreach ($result->entities as $entity) {
    echo "{$entity['name']}: {$entity['text']}\n";
}

// Table recognition
$result = $vision->recognizeText(
    imageData: file_get_contents('table.jpg'),
    model:     OcrModel::Table,
);

// Handwritten text
$result = $vision->recognizeText(
    imageData: file_get_contents('handwritten.jpg'),
    model:     OcrModel::Handwritten,
);

// Math formulas (returns LaTeX markdown)
$result = $vision->recognizeText(
    imageData: file_get_contents('formula.jpg'),
    model:     OcrModel::MathMarkdown,
);
echo $result->markdown; // LaTeX formula

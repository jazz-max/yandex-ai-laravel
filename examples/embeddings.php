<?php
// Text embeddings for semantic search

use JazzMax\YandexAi\Embeddings\EmbeddingsClient;
use JazzMax\YandexAi\Facades\YandexAi;

$client = YandexAi::embeddings();

// Index documents
$docs = [
    'Laravel is a PHP web framework.',
    'React is a JavaScript UI library.',
    'PostgreSQL is a relational database.',
];

$docVectors = [];
foreach ($docs as $doc) {
    $docVectors[] = $client->embedDocument($doc);
}

// Search
$queryVector = $client->embedQuery('What framework should I use for PHP?');

// Find most similar document
$bestScore = -1;
$bestIndex = 0;
foreach ($docVectors as $i => $docVector) {
    $score = EmbeddingsClient::cosineSimilarity($queryVector, $docVector);
    echo "Doc {$i}: similarity = {$score}\n";
    if ($score > $bestScore) {
        $bestScore = $score;
        $bestIndex = $i;
    }
}

echo "\nBest match: {$docs[$bestIndex]} (score: {$bestScore})\n";

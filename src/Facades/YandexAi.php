<?php

namespace JazzMax\YandexAi\Facades;

use Illuminate\Support\Facades\Facade;
use JazzMax\YandexAi\Embeddings\EmbeddingsClient;
use JazzMax\YandexAi\Responses\ResponsesClient;
use JazzMax\YandexAi\Vision\VisionClient;

/**
 * @method static ResponsesClient responses()
 * @method static VisionClient vision()
 * @method static EmbeddingsClient embeddings()
 *
 * @see \JazzMax\YandexAi\YandexAi
 */
class YandexAi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JazzMax\YandexAi\YandexAi::class;
    }
}

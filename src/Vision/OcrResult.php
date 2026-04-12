<?php

namespace JazzMax\YandexAi\Vision;

use JazzMax\YandexAi\Enums\OcrModel;

class OcrResult
{
    /**
     * Russian labels for entity fields returned by template models.
     */
    public const ENTITY_LABELS = [
        // Passport
        'name'                => 'Имя',
        'surname'             => 'Фамилия',
        'middle_name'         => 'Отчество',
        'gender'              => 'Пол',
        'citizenship'         => 'Гражданство',
        'birth_date'          => 'Дата рождения',
        'birth_place'         => 'Место рождения',
        'number'              => 'Номер',
        'issued_by'           => 'Кем выдан',
        'issue_date'          => 'Дата выдачи',
        'subdivision'         => 'Код подразделения',
        'expiration_date'     => 'Срок действия',
        // Driver License
        'experience_from'     => 'Стаж с',
        'prev_number'         => 'Предыдущий номер',
        // Vehicle Registration
        'registration_number' => 'Рег. номер',
        'vin'                 => 'VIN',
        'brand'               => 'Марка',
        'model'               => 'Модель',
        'year'                => 'Год выпуска',
        'chassis_number'      => 'Номер шасси',
        'body_number'         => 'Номер кузова',
        'color'               => 'Цвет',
        'owner'               => 'Владелец',
    ];

    public function __construct(
        public readonly string   $fullText,
        public readonly ?string  $markdown,
        public readonly array    $entities,
        public readonly OcrModel $model,
        public readonly int      $pages,
    ) {}

    /**
     * Get the best text representation for this result.
     */
    public function text(): string
    {
        // Template models: return formatted entities
        if ($this->model->isTemplate() && !empty($this->entities)) {
            return $this->formatEntities();
        }

        // Markdown models: return markdown
        if (in_array($this->model, [OcrModel::Markdown, OcrModel::MathMarkdown]) && $this->markdown) {
            return $this->markdown;
        }

        return $this->fullText;
    }

    /**
     * Format entities with Russian labels.
     */
    public function formatEntities(): string
    {
        $lines = [];
        foreach ($this->entities as $entity) {
            $name  = $entity['name'] ?? '';
            $text  = $entity['text'] ?? '';
            $label = self::ENTITY_LABELS[$name] ?? $name;
            $lines[] = "{$label}: {$text}";
        }

        return implode("\n", $lines);
    }

    /**
     * Price in RUB for this OCR operation.
     */
    public function priceRub(): float
    {
        return $this->model->priceRub() * max(1, $this->pages);
    }
}

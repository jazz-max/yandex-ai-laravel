<?php

namespace JazzMax\YandexAi\Enums;

enum OcrModel: string
{
    case Page                    = 'page';
    case PageColumnSort          = 'page-column-sort';
    case Handwritten             = 'handwritten';
    case Table                   = 'table';
    case Markdown                = 'markdown';
    case MathMarkdown            = 'math-markdown';
    case Passport                = 'passport';
    case DriverLicenseFront      = 'driver-license-front';
    case DriverLicenseBack       = 'driver-license-back';
    case VehicleRegistrationFront = 'vehicle-registration-front';
    case VehicleRegistrationBack  = 'vehicle-registration-back';
    case LicensePlates           = 'license-plates';

    /**
     * Template models return structured entities instead of raw text.
     */
    public function isTemplate(): bool
    {
        return in_array($this, [
            self::Passport,
            self::DriverLicenseFront,
            self::DriverLicenseBack,
            self::VehicleRegistrationFront,
            self::VehicleRegistrationBack,
            self::LicensePlates,
        ]);
    }

    /**
     * These models only support ru/en languages.
     * Markdown and MathMarkdown also reject ['*'] wildcard.
     */
    public function hasLimitedLanguages(): bool
    {
        return in_array($this, [self::Handwritten, self::Table, self::Markdown, self::MathMarkdown]);
    }

    public function languages(): array
    {
        return $this->hasLimitedLanguages() ? ['ru', 'en'] : ['*'];
    }

    public function priceRub(): float
    {
        return config('yandex-ai.ocr_pricing.' . $this->value, 0.132);
    }
}

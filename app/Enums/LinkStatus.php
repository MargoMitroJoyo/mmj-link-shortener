<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LinkStatus: int implements HasColor, HasIcon, HasLabel 
{
    case Active = 1;

    case Inactive = 0;

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Tidak Aktif',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Active => 'heroicon-m-check-circle',
            self::Inactive => 'heroicon-m-x-circle',
        };
    }
}

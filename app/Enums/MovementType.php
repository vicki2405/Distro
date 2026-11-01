<?php

namespace App\Enums;

enum MovementType: string
{
    case IN = 'in';
    case OUT = 'out';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::IN => 'Barang Masuk',
            self::OUT => 'Barang Keluar',
            self::ADJUSTMENT => 'Penyesuaian Opname',
        };
    }
}

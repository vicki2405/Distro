<?php

namespace App\Enums;

enum UserRole: string
{
    case OWNER = 'owner';
    case STAFF = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::STAFF => 'Pegawai Gudang',
        };
    }
}

<?php

namespace App\Enums;

enum CareerCategory: string
{
    case Light = 'light';
    case Educational = 'educational';
    case Administrative = 'administrative';
    case Leadership = 'leadership';

    public function label(): string
    {
        return match ($this) {
            self::Light => 'خفيفة',
            self::Educational => 'تعليمية',
            self::Administrative => 'إدارية',
            self::Leadership => 'قيادية',
        };
    }
}

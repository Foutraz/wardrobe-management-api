<?php

namespace Functional\Users\Enums;

enum Locale: string
{
    case French = 'fr';
    case English = 'en';

    /**
     * Get the locale used when an account expresses no preference.
     */
    public static function default(): self
    {
        return self::French;
    }
}

<?php

namespace Functional\Styling\Enums;

enum Season: string
{
    case Spring = 'spring';
    case Summer = 'summer';
    case Autumn = 'autumn';
    case Winter = 'winter';
    case AllYear = 'all_year';

    /**
     * Determine whether an outfit filed under this season suits the given month.
     */
    public function coversMonth(int $month): bool
    {
        return match ($this) {
            self::AllYear => true,
            self::Spring => in_array($month, [3, 4, 5], true),
            self::Summer => in_array($month, [6, 7, 8], true),
            self::Autumn => in_array($month, [9, 10, 11], true),
            self::Winter => in_array($month, [12, 1, 2], true),
        };
    }

    /**
     * Get the translated label for this season.
     */
    public function label(): string
    {
        return __('styling::season.'.$this->value);
    }
}

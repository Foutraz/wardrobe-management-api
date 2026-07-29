<?php

namespace Functional\Users\Enums;

enum UserRole: string
{
    case Member = 'member';
    case Moderator = 'moderator';

    /**
     * Get the abilities this role grants.
     *
     * @return list<Ability>
     */
    public function abilities(): array
    {
        return match ($this) {
            self::Member => Ability::forMembers(),
            self::Moderator => Ability::cases(),
        };
    }
}

<?php

namespace Functional\Resale\Values;

final readonly class HandoffInstructions
{
    /**
     * @param  list<string>  $photoUrls
     */
    public function __construct(
        public string $publisher,
        public string $target,
        public array $photoUrls,
        public string $guidance,
    ) {}
}

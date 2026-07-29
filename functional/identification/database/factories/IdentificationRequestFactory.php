<?php

namespace Functional\Identification\Database\Factories;

use Functional\Identification\Enums\IdentificationKind;
use Functional\Identification\Enums\IdentificationStatus;
use Functional\Identification\Models\IdentificationRequest;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IdentificationRequest>
 */
class IdentificationRequestFactory extends Factory
{
    protected $model = IdentificationRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'kind' => IdentificationKind::Barcode,
            'status' => IdentificationStatus::Pending,
            'barcode' => (string) faker()->number(1000000000000, 9999999999999),
            'extracted_attributes' => [],
        ];
    }

    /**
     * Present a request that has finished and is waiting for the owner to confirm it.
     */
    public function succeeded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => IdentificationStatus::Succeeded,
            'extracted_attributes' => ['size_label' => 'M', 'material_composition' => '100% Cotton'],
            'confidence' => 80,
            'provider' => 'catalogue-barcode',
        ]);
    }

    /**
     * Present a request that could not identify anything.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => IdentificationStatus::Failed,
            'failure_reason' => 'No catalogue entry carries this barcode yet.',
        ]);
    }
}

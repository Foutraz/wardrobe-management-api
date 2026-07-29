<?php

namespace Technical\AiGateway\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Technical\AiGateway\Enums\AiOperationKind;
use Technical\AiGateway\Enums\AiOperationStatus;

/**
 * @property string $id
 * @property string|null $user_id
 * @property AiOperationKind $kind
 * @property string $provider
 * @property AiOperationStatus $status
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property int $cost_cents
 * @property int|null $latency_ms
 * @property string|null $failure_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AiOperation extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'kind',
        'provider',
        'status',
        'subject_type',
        'subject_id',
        'cost_cents',
        'latency_ms',
        'failure_reason',
    ];

    /**
     * The subject is deliberately polymorphic and keeps no foreign key, so the audit trail
     * outlives whatever it describes.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => AiOperationKind::class,
            'status' => AiOperationStatus::class,
            'cost_cents' => 'integer',
            'latency_ms' => 'integer',
        ];
    }
}

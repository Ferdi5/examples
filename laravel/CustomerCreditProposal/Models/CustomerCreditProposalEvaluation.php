<?php

declare(strict_types=1);

namespace App\Domains\CustomerCreditProposal\Models;

/**
 * @property int                  $id
 * @property int                  $customer_credit_proposal_id
 * @property int                  $user_id
 * @property string|null          $status
 * @property string|null          $message
 * @property bool                 $owner
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
final class CustomerCreditProposalEvaluation extends Model
{
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_CONDITIONAL = 'CONDITIONAL';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_RESET = 'RESET';
    public const STATUS_REMOVED = 'REMOVED';
    const STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_CONDITIONAL,
        self::STATUS_REJECTED,
        self::STATUS_RESET,
        self::STATUS_REMOVED,
    ];
    /** @var string */
    protected $table = 'customer_credit_proposal_evaluation';
    /** @var string[] */
    protected $fillable = [
        'customer_credit_proposal_id',
        'user_id',
        'status',
        'message',
        'owner',
    ];

    public function creditProposal(): BelongsTo
    {
        return $this->belongsTo(CustomerCreditProposal::class, 'customer_credit_proposal_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CustomerCreditProposalEvaluationComment::class);
    }

    public function revision(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revision');
    }
}

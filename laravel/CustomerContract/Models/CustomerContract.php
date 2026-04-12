<?php

declare(strict_types=1);

namespace App\Domains\CustomerContract\Models;

/**
 * @property int                  $id
 * @property int                  $sales_status_id
 * @property int|null             $operating_company_id
 * @property int|null             $funder_id
 * @property int|null             $compartment_id
 * @property string               $type
 * @property string|null          $sign_location
 * @property string|null          $sign_date
 * @property bool|null            $signed
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
final class CustomerContract extends Model
{
    public const FACTORING = 'FACTORING';
    public const TRANSACTION_FINANCING = 'TRANSACTION_FINANCING';
    const TYPES = [
        self::FACTORING,
        self::TRANSACTION_FINANCING,
    ];
    /** @var string */
    protected $table = 'customer_contract';
    /** @var string[] */
    protected $fillable = [
        'sales_status_id',
        'operating_company_id',
        'funder_id',
        'compartment_id',
        'type',
        'sign_location',
        'sign_date',
        'signed',
    ];
    protected $casts = [
        'signed' => 'boolean',
    ];

    public function salesStatus(): BelongsTo
    {
        return $this->belongsTo(SalesStatus::class);
    }

    public function customerAnnexes(): HasMany
    {
        return $this->hasMany(CustomerContractAnnex::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(
            Company::class,
            'customer_contract_companies_rel',
            'customer_contract_id',
            'company_id'
        )
            ->withPivot('id', 'iban_id', 'signer_ids', 'client_number_id')
            ->orderBy('pivot_id')
            ->withTimestamps();
    }
}

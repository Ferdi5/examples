<?php

declare(strict_types=1);

namespace App\Domains\Integrations;

/**
 * @property int                  $id
 * @property string               $name
 * @property array|object|null    $meta
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
final class Integration extends Model
{
    /** @var string */
    protected $table = 'integrations';

    /** @var string[] */
    protected $fillable = [
        'name',
        'meta',
    ];

    /** @var string[] */
    protected $casts = [
        'meta' => 'array',
    ];
}

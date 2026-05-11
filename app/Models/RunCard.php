<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RunCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property int $id
 * @property int $activity_id
 * @property string $rarity
 * @property array<int, string> $badges
 * @property string $special_move
 * @property string|null $share_image_path
 * @property-read Activity $activity
 */
#[Fillable([
    'activity_id',
    'rarity',
    'badges',
    'special_move',
    'share_image_path',
])]
class RunCard extends Model
{
    /** @use HasFactory<RunCardFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return ['badges' => 'array'];
    }
}

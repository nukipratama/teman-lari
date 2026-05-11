<?php

declare(strict_types=1);

namespace App\Models;

use Override;
use Database\Factories\ActivityStreamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Raw second-by-second Strava streams blob. Deferred-loaded — never join
 * this in a list query. Read only when re-computing summaries.
 *
 * @property int $id
 * @property int $activity_id
 * @property array<string, mixed> $data
 * @property-read Activity $activity
 */
#[Fillable(['activity_id', 'data'])]
class ActivityStream extends Model
{
    /** @use HasFactory<ActivityStreamFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * `data` is stored as longText (not JSON column type) because the blob
     * is large and MySQL's JSON validation overhead isn't worth the cost.
     * Cast handles the encode/decode.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}

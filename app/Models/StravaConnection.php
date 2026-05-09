<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StravaConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property int $id
 * @property int $user_id
 * @property int $strava_athlete_id
 * @property string $access_token
 * @property string $refresh_token
 * @property \Illuminate\Support\Carbon $token_expires_at
 * @property string $scopes
 * @property-read \App\Models\User $user
 */
#[Fillable([
    'user_id',
    'strava_athlete_id',
    'access_token',
    'refresh_token',
    'token_expires_at',
    'scopes',
])]
#[Hidden(['access_token', 'refresh_token'])]
class StravaConnection extends Model
{
    /** @use HasFactory<StravaConnectionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'strava_athlete_id' => 'integer',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }
}

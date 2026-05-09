<?php

declare(strict_types=1);

namespace App\Services\Strava\Exceptions;

use RuntimeException;

class StravaRateLimitedException extends RuntimeException
{
}

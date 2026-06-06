<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Models\User;
use App\Models\UserUnlock;
use Illuminate\Database\Eloquent\Collection;

/**
 * Resolves which accessories a user has equipped into the shape the Temari
 * mascot renders ({@see resources/js/components/temari/TemariProto.tsx}). One
 * source of truth shared by the Aksesori page and the global Inertia prop, so
 * the equipped look stays consistent everywhere the mascot appears.
 */
class EquippedAccessories
{
    /**
     * The ordered list of valid equipment slots.
     *
     * @return list<string>
     */
    public function slots(): array
    {
        return ['medal', 'ikat_kepala', 'pita', 'kaus', 'celana', 'sepatu', 'aura'];
    }

    /**
     * @return array{medal: ?string, ikat_kepala: ?string, pita: ?string, kaus: ?string, celana: ?string, sepatu: ?string, aura: ?string}
     */
    public function forUser(?User $user): array
    {
        if ($user === null) {
            return $this->empty();
        }

        return $this->resolve(
            UserUnlock::query()->where('user_id', $user->id)->get(),
        );
    }

    /**
     * @param  Collection<int, UserUnlock>  $unlocks
     * @return array{medal: string|null, ikat_kepala: string|null, pita: string|null, kaus: string|null, celana: string|null, sepatu: string|null, aura: string|null}
     */
    public function resolve(Collection $unlocks): array
    {
        $equipped = $unlocks->filter(fn (UserUnlock $u): bool => (bool) $u->equipped);

        return [
            'medal' => $this->equippedKeyForSlot($equipped, 'medal'),
            'ikat_kepala' => $this->equippedKeyForSlot($equipped, 'ikat_kepala'),
            'pita' => $this->equippedKeyForSlot($equipped, 'pita'),
            'kaus' => $this->equippedKeyForSlot($equipped, 'kaus'),
            'celana' => $this->equippedKeyForSlot($equipped, 'celana'),
            'sepatu' => $this->equippedKeyForSlot($equipped, 'sepatu'),
            'aura' => $this->equippedKeyForSlot($equipped, 'aura'),
        ];
    }

    /** @param  Collection<int, UserUnlock>  $equipped */
    private function equippedKeyForSlot(Collection $equipped, string $slot): ?string
    {
        $catalog = $this->catalogLookup();
        $item = $equipped->first(function (UserUnlock $u) use ($catalog, $slot): bool {
            $meta = $catalog[$u->unlock_key] ?? null;

            return isset($meta['slot']) && $meta['slot'] === $slot;
        });

        return $item?->unlock_key;
    }

    public function slotFor(string $key): ?string
    {
        $catalog = $this->catalogLookup();
        $meta = $catalog[$key] ?? null;

        if (isset($meta['slot'])) {
            return $meta['slot'];
        }

        return null;
    }

    /** @return array<string, array{slot?: string}> */
    private function catalogLookup(): array
    {
        return (array) config('temari_unlocks', []);
    }

    /**
     * @return array{medal: null, ikat_kepala: null, pita: null, kaus: null, celana: null, sepatu: null, aura: null}
     */
    private function empty(): array
    {
        return [
            'medal' => null,
            'ikat_kepala' => null,
            'pita' => null,
            'kaus' => null,
            'celana' => null,
            'sepatu' => null,
            'aura' => null,
        ];
    }
}

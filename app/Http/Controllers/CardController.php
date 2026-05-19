<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\RunCard;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CardController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $rarity = $request->query('rarity');
        $rarity = is_string($rarity) && $rarity !== '' ? $rarity : null;

        $page = RunCard::query()
            ->whereHas('activity', fn ($q) => $q->where('user_id', $user->id))
            ->with(['activity.detail'])
            ->when($rarity, fn ($q) => $q->where('rarity', $rarity))
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Cards/Index', [
            'cards' => $page,
            'selectedRarity' => $rarity,
        ]);
    }
}

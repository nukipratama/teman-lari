<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * Shared style/voice rules for all Temari narrators. Pulled out so future
 * voice tweaks happen in one place — and so the positive-framed dash rule
 * (LLMs respond better to positive-then-negative than pure negation) can be
 * appended consistently by {@see StructuredChatCaller}.
 *
 * Per-narrator persona, domain vocabulary, and tone-mapping still live in
 * each narrator's SYSTEM_PROMPT — those vary meaningfully and resist a
 * one-size DRY without risking voice drift.
 */
final class TemariPersona
{
    public const string STYLE_RULES = <<<'RULES'

Aturan gaya tulisan (WAJIB):
- Untuk jeda dalam kalimat, pakai koma, titik, atau kata sambung biasa. JANGAN pakai em dash (—) atau en dash (–) di output.
RULES;
}

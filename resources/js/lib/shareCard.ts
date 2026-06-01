import { DAYBREAK, hrZone } from '@/lib/chartTokens';
import { projectPolyline } from '@/lib/route';
import { RARITY_LABELS, RARITY_SYMBOL } from '@/lib/runcard';
import { MOOD_FACE, MOOD_LABEL, moodSigilColor } from '@/lib/mood';
import type { CardEdition, Mood, Rarity, ZonePct } from '@/types/inertia';

const HR_ZONES = ['Z1', 'Z2', 'Z3', 'Z4', 'Z5'] as const;

/**
 * Deterministic, device-independent share-card renderer.
 *
 * Everything is drawn at a FIXED internal resolution (1080×1920 story /
 * 1080×1080 feed) regardless of the on-screen size, so the exported PNG is
 * pixel-identical on every device and the live <canvas> preview IS the export.
 * No html-to-image, no DOM capture, no font-fallback drift.
 */

/**
 * Flat, render-ready data a single card contributes to a share image. Lives
 * here (next to the canvas engine that consumes it) rather than in the modal
 * component, so the lib has no component dependency.
 */
export interface ShareKartuData {
    id: number;
    name: string;
    rarity: Rarity;
    /** The run's Temari mood, used as the card's "element/type". */
    mood: Mood;
    subtitle: string | null;
    date: string | null;
    km: string;
    durasi: string;
    pace: string | null;
    trimp: string;
    hr: string | null;
    /** Avg cadence label, e.g. "178 spm". */
    cadence: string | null;
    /** Fastest single km pace, e.g. "5:41/km". */
    fastestKm: string | null;
    /** HR zone distribution (Z1..Z5 %) for the effort bar. Null hides it. */
    zonePct: ZonePct | null;
    location: string | null;
    weather: string | null;
    tags: string[];
    /** Badge emoji emblems, parallel to tags, for the hero ability pips. */
    tagEmojis: string[];
    quote: string | null;
    /** Encoded route polyline for the card / route templates (optional). */
    polyline?: string | null;
    /** Collector number within the rarity (optional). */
    edition?: CardEdition | null;
}

export type Theme = 'Dawn' | 'Sky' | 'Cream' | 'Inverted';
export type Format = 'story' | 'feed';
export type Layout = 'kartu' | 'rute' | 'struk';

export interface ShareCardConfig {
    kartu: ShareKartuData;
    theme: Theme;
    layout: Layout;
    format: Format;
    showStats: boolean;
    showQuote: boolean;
    /**
     * The full Temari mascot (with user accessories) pre-rendered to an image
     * from the live DOM. When provided, `drawHero` draws this instead of the
     * fallback flat bunny glyph.
     */
    temariImg?: HTMLImageElement | null;
}

const DIMS: Record<Format, { w: number; h: number }> = {
    story: { w: 1080, h: 1920 },
    feed: { w: 1080, h: 1080 },
};

const PAD = 92;

// Daybreak palette as literal hex (canvas can't read CSS vars). Brand hues
// reference the shared DAYBREAK bridge so they can't drift; the rest are
// canvas-only shades that mirror the @theme block in app.css.
const C = {
    horizon: DAYBREAK.horizon,
    horizonDeep: DAYBREAK.horizonDeep,
    ink: DAYBREAK.ink,
    ink2: '#3d362a',
    ink3: '#6e6452',
    cream: '#f6f1e8',
    creamDeep: '#eee7d6',
    sky: DAYBREAK.sky,
    skyDeep: DAYBREAK.skyDeep,
    surfaceCard: '#f6f1e8',
    surfaceSunken: '#efe8da',
    line: '#e3dccd',
    inkOnSky: '#b8ad97',
    // Vivid loot-ladder rarity — mirrors RARITY_HEX / the --color-rarity-* tokens.
    rarity: {
        common: '#7d8694',
        uncommon: '#2fb350',
        rare: '#2f81f7',
        epic: '#a855f7',
        legendary: '#f5a623',
    } as Record<string, string>,
};

interface Palette {
    isDark: boolean;
    text: string;
    name: string;
    meta: string;
    divider: string;
    quote: string;
}

function palette(theme: Theme): Palette {
    const isDark = theme !== 'Cream';
    return {
        isDark,
        text: isDark ? C.cream : C.ink,
        name: isDark ? C.horizon : C.ink,
        meta: isDark ? 'rgba(246,241,232,0.72)' : C.ink3,
        divider: isDark ? 'rgba(246,241,232,0.18)' : 'rgba(31,39,71,0.10)',
        quote: isDark ? 'rgba(246,241,232,0.88)' : C.ink2,
    };
}

function paintBackground(ctx: CanvasRenderingContext2D, theme: Theme, w: number, h: number): void {
    if (theme === 'Cream') {
        ctx.fillStyle = C.creamDeep;
    } else if (theme === 'Sky') {
        ctx.fillStyle = C.sky;
    } else if (theme === 'Inverted') {
        ctx.fillStyle = C.skyDeep;
    } else {
        const g = ctx.createLinearGradient(w * 0.18, 0, w * 0.82, h);
        g.addColorStop(0, C.skyDeep);
        g.addColorStop(0.5, C.sky);
        g.addColorStop(0.88, '#95573c');
        g.addColorStop(1, C.horizonDeep);
        ctx.fillStyle = g;
    }
    ctx.fillRect(0, 0, w, h);
}

function paintGlow(ctx: CanvasRenderingContext2D, cx: number, cy: number, r: number): void {
    const g = ctx.createRadialGradient(cx, cy, 0, cx, cy, r);
    g.addColorStop(0, 'rgba(232,160,118,0.34)');
    g.addColorStop(0.66, 'rgba(232,160,118,0)');
    ctx.fillStyle = g;
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.fill();
}

function roundRectPath(ctx: CanvasRenderingContext2D, x: number, y: number, w: number, h: number, r: number): void {
    const radius = Math.min(r, w / 2, h / 2);
    ctx.beginPath();
    ctx.moveTo(x + radius, y);
    ctx.arcTo(x + w, y, x + w, y + h, radius);
    ctx.arcTo(x + w, y + h, x, y + h, radius);
    ctx.arcTo(x, y + h, x, y, radius);
    ctx.arcTo(x, y, x + w, y, radius);
    ctx.closePath();
}

/**
 * Stroke a route polyline inside a box (x, y, w, h) using the shared
 * `projectPolyline` geometry (same normalization as the on-card RouteGlyph).
 * Returns false (drew nothing) when there's no drawable route, so callers fall back.
 */
function drawRoute(
    ctx: CanvasRenderingContext2D,
    polyline: string | null | undefined,
    box: { x: number; y: number; w: number; h: number },
    stroke: string,
    lineWidth: number,
): boolean {
    const projected = projectPolyline(polyline, box.w, box.h, lineWidth * 1.5, 240);
    if (projected === null) {
        return false;
    }

    ctx.save();
    ctx.beginPath();
    projected.points.forEach(([px, py], i) => {
        const x = box.x + px;
        const y = box.y + py;
        if (i === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
    });
    ctx.strokeStyle = stroke;
    ctx.lineWidth = lineWidth;
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    ctx.stroke();
    ctx.restore();
    return true;
}

function wrapText(ctx: CanvasRenderingContext2D, text: string, maxWidth: number): string[] {
    const words = text.split(/\s+/);
    const lines: string[] = [];
    let line = '';
    for (const word of words) {
        const candidate = line ? `${line} ${word}` : word;
        if (ctx.measureText(candidate).width > maxWidth && line) {
            lines.push(line);
            line = word;
        } else {
            line = candidate;
        }
    }
    if (line) {
        lines.push(line);
    }
    return lines;
}

// Fonts are device-stable, so the load only needs to happen once; cache the
// promise so interactive preview repaints don't re-await it every knob change.
let fontsReady: Promise<void> | null = null;

function ensureFonts(): Promise<void> {
    if (!fontsReady) {
        const specs = [
            'italic 600 120px "Fraunces"',
            'italic 500 120px "Fraunces"',
            '700 120px "JetBrains Mono"',
            '500 120px "JetBrains Mono"',
            '600 120px "Plus Jakarta Sans"',
            '700 120px "Plus Jakarta Sans"',
            '600 120px "Oswald"',
            '700 120px "Oswald"',
        ];
        fontsReady = Promise.all(specs.map((s) => document.fonts.load(s)))
            .then(() => document.fonts.ready)
            .then(() => undefined)
            .catch(() => {
                /* fonts best-effort; canvas falls back gracefully */
            });
    }
    return fontsReady;
}

// Flat, canvas-safe port of BunnyGlyph in components/BrandMark.tsx (no
// gradients/highlights). Keep the core geometry in sync with that source.
function bunnySvg(tone: 'ink' | 'cream', bandHex: string = C.horizon): string {
    const isInk = tone === 'ink';
    const face = isInk ? C.ink : C.cream;
    const blush = isInk ? C.horizon : C.horizonDeep;
    const features = isInk ? C.cream : C.ink;
    return `<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><defs><clipPath id="b"><circle cx="50" cy="58" r="40"/></clipPath></defs><ellipse cx="32" cy="8" rx="9" ry="16" fill="${face}" transform="rotate(-12 32 8)"/><ellipse cx="68" cy="8" rx="9" ry="16" fill="${face}" transform="rotate(12 68 8)"/><ellipse cx="32" cy="10" rx="4" ry="9" fill="${blush}" transform="rotate(-12 32 10)"/><ellipse cx="68" cy="10" rx="4" ry="9" fill="${blush}" transform="rotate(12 68 10)"/><circle cx="50" cy="58" r="40" fill="${face}"/><g clip-path="url(#b)"><rect x="10" y="40" width="80" height="14" fill="${bandHex}"/></g><circle cx="38" cy="68" r="4.5" fill="${features}"/><circle cx="62" cy="68" r="4.5" fill="${features}"/><path d="M 44 80 Q 50 85 56 80" fill="none" stroke="${features}" stroke-width="2.4" stroke-linecap="round"/></svg>`;
}

function loadImage(src: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}

// Few tone/band combinations exist and each glyph never changes; cache every
// decoded image (keyed by tone + headband hex) so repeated repaints reuse it
// instead of re-encoding and re-decoding the SVG.
const bunnyCache: Record<string, HTMLImageElement> = {};

async function loadBunny(tone: 'ink' | 'cream', bandHex: string = C.horizon): Promise<HTMLImageElement | null> {
    const key = `${tone}:${bandHex}`;
    if (bunnyCache[key]) {
        return bunnyCache[key];
    }
    try {
        const img = await loadImage(`data:image/svg+xml;utf8,${encodeURIComponent(bunnySvg(tone, bandHex))}`);
        bunnyCache[key] = img;
        return img;
    } catch {
        return null;
    }
}

/** Star + rarity word pill. Returns its height so callers can flow below it. */
function drawRarityFlag(ctx: CanvasRenderingContext2D, x: number, y: number, rarity: string): number {
    const label = `★ ${(RARITY_LABELS[rarity as keyof typeof RARITY_LABELS] ?? rarity).toUpperCase()}`;
    ctx.font = '700 30px "JetBrains Mono"';
    ctx.letterSpacing = '3px';
    const tw = ctx.measureText(label).width;
    const padX = 28;
    const h = 56;
    const w = tw + padX * 2;
    roundRectPath(ctx, x, y, w, h, h / 2);
    ctx.fillStyle = C.horizon;
    ctx.fill();
    ctx.fillStyle = C.skyDeep;
    ctx.textBaseline = 'middle';
    ctx.textAlign = 'left';
    ctx.fillText(label, x + padX, y + h / 2 + 1);
    ctx.letterSpacing = '0px';
    return h;
}

/** Brand lockup (bunny + wordmark) right-aligned to `rightX`. */
function drawBrand(ctx: CanvasRenderingContext2D, rightX: number, y: number, isDark: boolean, bunny: HTMLImageElement | null): void {
    const size = 52;
    const gap = 14;
    ctx.font = '700 38px "JetBrains Mono"';
    ctx.textBaseline = 'middle';
    ctx.textAlign = 'left';
    const word = 'TemanLari';
    const wordW = ctx.measureText(word).width;
    const totalW = size + gap + wordW;
    const startX = rightX - totalW;
    if (bunny) {
        ctx.drawImage(bunny, startX, y, size, size);
    }
    ctx.fillStyle = isDark ? C.cream : C.ink;
    ctx.fillText(word, startX + size + gap, y + size / 2 + 1);
}

interface DrawCtx {
    ctx: CanvasRenderingContext2D;
    w: number;
    h: number;
    cfg: ShareCardConfig;
    pal: Palette;
    bunny: HTMLImageElement | null;
    /** Temari glyph with its headband tinted to the card's mood. */
    moodBunny: HTMLImageElement | null;
    statItems: Array<{ v: string; l: string }>;
}

/** Bottom-left mono date stamp, shared by the poster and angka templates. */
function drawDateFooter(d: DrawCtx): void {
    const { ctx, h, cfg, pal } = d;
    if (!cfg.kartu.date) {
        return;
    }
    ctx.font = '500 30px "JetBrains Mono"';
    ctx.letterSpacing = '2px';
    ctx.fillStyle = pal.meta;
    ctx.textAlign = 'left';
    ctx.textBaseline = 'alphabetic';
    ctx.fillText(cfg.kartu.date.replace('\n', ' · '), PAD, h - PAD);
    ctx.letterSpacing = '0px';
}

/** Route-map hero: the run's route as big poster art with name + KM + edition. */
function drawRute(d: DrawCtx): void {
    const { ctx, w, h, cfg, pal, bunny } = d;
    const k = cfg.kartu;
    paintGlow(ctx, w / 2, h * 0.38, w * 0.5);
    drawBrand(ctx, w - PAD, PAD, pal.isDark, bunny);
    drawRarityFlag(ctx, PAD, PAD, k.rarity);

    const box = { x: PAD, y: PAD + 110, w: w - PAD * 2, h: h * (cfg.format === 'story' ? 0.42 : 0.4) };
    drawRoute(ctx, k.polyline, box, pal.isDark ? C.horizon : C.horizonDeep, 12);

    let y = box.y + box.h + 90;
    ctx.textAlign = 'left';
    ctx.textBaseline = 'alphabetic';
    ctx.font = `italic 600 ${cfg.format === 'story' ? 88 : 72}px "Fraunces"`;
    ctx.fillStyle = pal.name;
    wrapText(ctx, `${k.name}.`, w - PAD * 2)
        .slice(0, 2)
        .forEach((ln) => {
            ctx.fillText(ln, PAD, y);
            y += cfg.format === 'story' ? 92 : 78;
        });

    y += 24;
    const kmSize = cfg.format === 'story' ? 200 : 150;
    ctx.font = `700 ${kmSize}px "Oswald"`;
    ctx.fillStyle = pal.name;
    ctx.fillText(k.km, PAD, y + kmSize * 0.8);
    const kmW = ctx.measureText(k.km).width;
    ctx.font = '700 40px "JetBrains Mono"';
    ctx.letterSpacing = '3px';
    ctx.fillStyle = pal.meta;
    ctx.fillText('KM', PAD + kmW + 20, y + kmSize * 0.5);
    ctx.letterSpacing = '0px';
    if (k.edition) {
        ctx.font = '600 48px "Oswald"';
        ctx.fillStyle = pal.meta;
        ctx.textAlign = 'right';
        ctx.fillText(`#${k.edition.index}/${k.edition.total}`, w - PAD, y + kmSize * 0.5);
    }

    drawDateFooter(d);
}

function drawHeroShimmer(
    ctx: CanvasRenderingContext2D,
    cx: number, artY: number, cw: number, artH: number,
    rarity: Rarity, rarityCol: string,
): void {
    if (rarity !== 'epic' && rarity !== 'legendary') return;
    const g = ctx.createLinearGradient(cx, artY, cx + cw, artY + artH);
    if (rarity === 'legendary') {
        g.addColorStop(0, 'rgba(255,80,80,0.07)');
        g.addColorStop(0.2, 'rgba(255,200,80,0.07)');
        g.addColorStop(0.4, 'rgba(80,255,80,0.07)');
        g.addColorStop(0.6, 'rgba(80,180,255,0.07)');
        g.addColorStop(0.8, 'rgba(180,80,255,0.07)');
        g.addColorStop(1, 'rgba(255,80,80,0.07)');
    } else {
        g.addColorStop(0, 'transparent');
        g.addColorStop(0.45, rarityCol + '30');
        g.addColorStop(0.5, 'rgba(255,255,255,0.18)');
        g.addColorStop(0.55, rarityCol + '30');
        g.addColorStop(1, 'transparent');
    }
    ctx.fillStyle = g;
    ctx.globalCompositeOperation = 'soft-light';
    ctx.fillRect(cx, artY, cw, artH);
    ctx.globalCompositeOperation = 'source-over';
}

/** Floating edition (L) + TRIMP-power (R) pills over the bright art window. */
function drawHeroArtBadges(
    ctx: CanvasRenderingContext2D,
    k: ShareKartuData,
    box: { x: number; y: number; w: number },
    moodCol: string,
): void {
    const badgePad = 18;
    const badgeH = 48;
    const top = box.y + 18;
    const mid = top + badgeH / 2;

    if (k.edition) {
        ctx.font = '600 28px "JetBrains Mono"';
        const edText = '#' + String(k.edition.index) + '/' + String(k.edition.total);
        const edW = ctx.measureText(edText).width + badgePad * 2;
        roundRectPath(ctx, box.x + 18, top, edW, badgeH, badgeH / 2);
        ctx.fillStyle = 'rgba(22,27,51,0.82)';
        ctx.fill();
        ctx.fillStyle = C.cream;
        ctx.textBaseline = 'middle';
        ctx.textAlign = 'left';
        ctx.fillText(edText, box.x + 18 + badgePad, mid + 1);
    }

    ctx.font = '700 28px "JetBrains Mono"';
    const trimpText = String(k.trimp);
    const trimpW = ctx.measureText(trimpText).width + badgePad * 2 + 30;
    const bx = box.x + box.w - 18 - trimpW;
    roundRectPath(ctx, bx, top, trimpW, badgeH, badgeH / 2);
    ctx.fillStyle = 'rgba(22,27,51,0.82)';
    ctx.fill();
    ctx.beginPath();
    ctx.arc(bx + badgePad + 9, mid, 9, 0, Math.PI * 2);
    ctx.fillStyle = moodCol;
    ctx.fill();
    ctx.fillStyle = C.cream;
    ctx.textBaseline = 'middle';
    ctx.textAlign = 'left';
    ctx.fillText(trimpText, bx + badgePad + 26, mid + 1);
}

/** The bright art window: cream wash, route hero, corner bunny, floating badges. */
function drawHeroArtWindow(
    ctx: CanvasRenderingContext2D,
    k: ShareKartuData,
    mascot: HTMLImageElement | null,
    box: { x: number; y: number; w: number; h: number },
    rarityCol: string,
    moodCol: string,
    story: boolean,
): void {
    const r = 24;
    ctx.save();
    roundRectPath(ctx, box.x, box.y, box.w, box.h, r);
    ctx.clip();

    // Cream base + a soft mood/rarity wash so it isn't flat.
    ctx.fillStyle = C.cream;
    ctx.fillRect(box.x, box.y, box.w, box.h);
    const wash = ctx.createRadialGradient(
        box.x + box.w / 2, box.y + box.h * 0.62, 0,
        box.x + box.w / 2, box.y + box.h * 0.62, box.h * 0.9,
    );
    wash.addColorStop(0, moodCol + '22');
    wash.addColorStop(0.55, rarityCol + '14');
    wash.addColorStop(1, 'rgba(0,0,0,0)');
    ctx.fillStyle = wash;
    ctx.fillRect(box.x, box.y, box.w, box.h);

    // Route hero.
    drawRoute(ctx, k.polyline, box, rarityCol, story ? 16 : 13);
    drawHeroShimmer(ctx, box.x, box.y, box.w, box.h, k.rarity, rarityCol);

    // Corner bunny companion.
    if (mascot) {
        const ms = Math.round(box.h * 0.34);
        ctx.drawImage(mascot, box.x + box.w - ms - 14, box.y + box.h - ms - 8, ms, ms);
    }
    ctx.restore();

    drawHeroArtBadges(ctx, k, box, moodCol);
}

/**
 * The dark stat block, mirroring the live Kartu full tier: rarity ribbon, name,
 * subtitle, KM hero, a labeled PACE · HR · CADENCE · DURASI · BEST grid, a Z1..Z5
 * HR-zone effort bar, badges, and (story) a flavor quote.
 *
 * Returns the total height it consumed from `box.y`. Pass `draw=false` to
 * measure without painting — `drawHero` uses that to size the art window to the
 * remaining space, so the block never reserves dead navy below its content.
 */
interface HeroBlock {
    ctx: CanvasRenderingContext2D;
    k: ShareKartuData;
    box: { x: number; y: number; w: number; h: number };
    rarityCol: string;
    story: boolean;
    showStats: boolean;
    showQuote: boolean;
    /** false = measure only (advance the cursor without painting). */
    draw: boolean;
}

function drawHeroBlock(s: HeroBlock): number {
    let y = s.box.y;
    y = heroRibbonRow(s, y);
    y = heroNameRow(s, y);
    y = heroSubtitleRow(s, y);
    y = heroKmRow(s, y);
    y = heroStatGridRow(s, y);
    y = heroZoneBarRow(s, y);
    y = heroBadgeRow(s, y);
    y = heroQuoteRow(s, y);
    return y - s.box.y;
}

/** Rarity ribbon (left) + mood (right). */
function heroRibbonRow(s: HeroBlock, y: number): number {
    const { ctx, k, box, rarityCol, story, draw } = s;
    y += story ? 28 : 24;
    if (draw) {
        ctx.font = `700 ${story ? 26 : 22}px "JetBrains Mono"`;
        ctx.letterSpacing = '3px';
        ctx.fillStyle = rarityCol;
        ctx.textAlign = 'left';
        ctx.textBaseline = 'alphabetic';
        ctx.fillText(RARITY_SYMBOL[k.rarity] + '  ' + RARITY_LABELS[k.rarity].toUpperCase(), box.x, y);
        ctx.textAlign = 'right';
        ctx.fillStyle = C.inkOnSky;
        ctx.fillText(MOOD_FACE[k.mood] + '  ' + MOOD_LABEL[k.mood].toUpperCase(), box.x + box.w, y);
        ctx.letterSpacing = '0px';
    }
    return y;
}

/** Special-move name (wrap counts identically in measure + draw). */
function heroNameRow(s: HeroBlock, y: number): number {
    const { ctx, k, box, story, draw } = s;
    const nameSize = story ? box.w * 0.088 : box.w * 0.072;
    ctx.font = `700 ${nameSize}px "Oswald"`;
    ctx.textAlign = 'left';
    wrapText(ctx, k.name.toUpperCase(), box.w).slice(0, 2).forEach((ln) => {
        y += nameSize * 1.04;
        if (draw) {
            ctx.fillStyle = C.cream;
            ctx.fillText(ln, box.x, y);
        }
    });
    return y;
}

function heroSubtitleRow(s: HeroBlock, y: number): number {
    const { ctx, k, box, story, draw } = s;
    if (!k.subtitle) {
        return y;
    }
    y += story ? 40 : 34;
    if (draw) {
        ctx.font = `500 ${story ? 26 : 22}px "JetBrains Mono"`;
        ctx.fillStyle = C.inkOnSky;
        ctx.fillText(k.subtitle, box.x, y);
    }
    return y;
}

/** KM hero number + "KM" suffix. */
function heroKmRow(s: HeroBlock, y: number): number {
    const { ctx, k, box, story, draw } = s;
    const kmSize = story ? box.w * 0.15 : box.w * 0.125;
    y += kmSize * 0.92;
    if (draw) {
        ctx.font = `700 ${kmSize}px "Oswald"`;
        ctx.fillStyle = C.horizon;
        ctx.textAlign = 'left';
        ctx.textBaseline = 'alphabetic';
        ctx.fillText(k.km, box.x, y);
        const kmW = ctx.measureText(k.km).width;
        ctx.font = `700 ${story ? 28 : 24}px "JetBrains Mono"`;
        ctx.fillStyle = C.inkOnSky;
        ctx.fillText('KM', box.x + kmW + 14, y);
    }
    return y;
}

function heroStatGridRow(s: HeroBlock, y: number): number {
    const { ctx, k, box, story, showStats, draw } = s;
    if (!showStats) {
        return y;
    }
    const cells = heroStatCells(k);
    if (cells.length === 0) {
        return y;
    }
    y += story ? 26 : 20;
    if (draw) {
        drawHeroStatGrid(ctx, cells, box.x, y, box.w, story);
    }
    return y + Math.ceil(cells.length / 3) * (story ? 70 : 58);
}

function heroZoneBarRow(s: HeroBlock, y: number): number {
    const { ctx, k, box, story, draw } = s;
    if (!k.zonePct) {
        return y;
    }
    const barH = story ? 16 : 12;
    y += story ? 24 : 18;
    if (draw) {
        drawZoneBar(ctx, k.zonePct, box.x, y, box.w, barH);
    }
    return y + barH + (story ? 30 : 24);
}

function heroBadgeRow(s: HeroBlock, y: number): number {
    const { ctx, k, box, story, draw } = s;
    if (k.tags.length === 0) {
        return y;
    }
    y += story ? 52 : 44;
    if (draw) {
        drawBadgePips(ctx, k, box.x, y, story);
    }
    return y;
}

/** Flavor quote — story format only, when enabled. */
function heroQuoteRow(s: HeroBlock, y: number): number {
    const { ctx, k, box, story, showQuote, draw } = s;
    if (!(story && showQuote && k.quote)) {
        return y;
    }
    y += 52;
    ctx.font = 'italic 500 34px "Fraunces"';
    wrapText(ctx, '"' + k.quote + '"', box.w).slice(0, 2).forEach((ln) => {
        if (draw) {
            ctx.fillStyle = C.inkOnSky;
            ctx.textAlign = 'left';
            ctx.fillText(ln, box.x, y);
        }
        y += 44;
    });
    return y;
}

/** PACE · HR · CADENCE · DURASI · BEST cells, present-only (mirrors live StatGrid). */
function heroStatCells(k: ShareKartuData): Array<{ label: string; value: string }> {
    const raw: Array<{ label: string; value: string | null }> = [
        { label: 'PACE', value: k.pace ? k.pace + '/km' : null },
        { label: 'HR', value: k.hr },
        { label: 'CADENCE', value: k.cadence },
        { label: 'DURASI', value: k.durasi },
        { label: 'BEST', value: k.fastestKm },
    ];
    return raw.filter((c): c is { label: string; value: string } => c.value != null && c.value !== '' && c.value !== '—');
}

/** A 3-column label-over-value stat grid starting at top `y`. */
function drawHeroStatGrid(
    ctx: CanvasRenderingContext2D,
    cells: Array<{ label: string; value: string }>,
    left: number,
    y: number,
    w: number,
    story: boolean,
): void {
    const colW = w / 3;
    const rowH = story ? 70 : 58;
    const labelSize = story ? 22 : 18;
    const valueSize = story ? 36 : 30;
    cells.forEach((cell, i) => {
        const cx = left + (i % 3) * colW;
        const cy = y + Math.floor(i / 3) * rowH;
        ctx.textAlign = 'left';
        ctx.textBaseline = 'alphabetic';
        ctx.font = `700 ${labelSize}px "JetBrains Mono"`;
        ctx.letterSpacing = '2px';
        ctx.fillStyle = C.inkOnSky;
        ctx.fillText(cell.label, cx, cy + labelSize);
        ctx.letterSpacing = '0px';
        ctx.font = `700 ${valueSize}px "JetBrains Mono"`;
        ctx.fillStyle = C.cream;
        ctx.fillText(cell.value, cx, cy + labelSize + valueSize + 4);
    });
}

/** Stacked Z1..Z5 effort bar with tiny labels, using the shared HR-zone hexes. */
function drawZoneBar(
    ctx: CanvasRenderingContext2D,
    zonePct: NonNullable<ShareKartuData['zonePct']>,
    left: number,
    y: number,
    w: number,
    barH: number,
): void {
    const total = HR_ZONES.reduce((sum, z) => sum + (zonePct[z] ?? 0), 0);
    if (total <= 0) {
        return;
    }
    let x = left;
    HR_ZONES.forEach((zone) => {
        const pct = zonePct[zone] ?? 0;
        if (pct <= 0) {
            return;
        }
        const segW = (pct / total) * w;
        ctx.fillStyle = hrZone[zone];
        ctx.fillRect(x, y, segW, barH);
        x += segW;
    });
    // Tiny Z labels under the bar.
    const labelY = y + barH + 22;
    ctx.font = '700 20px "JetBrains Mono"';
    ctx.textBaseline = 'alphabetic';
    ctx.letterSpacing = '1px';
    HR_ZONES.forEach((zone, i) => {
        ctx.fillStyle = hrZone[zone];
        ctx.textAlign = i === HR_ZONES.length - 1 ? 'right' : 'left';
        const lx = i === HR_ZONES.length - 1 ? left + w : left + (w / HR_ZONES.length) * i;
        ctx.fillText(zone, lx, labelY);
    });
    ctx.letterSpacing = '0px';
    ctx.textAlign = 'left';
}

/** A left-aligned row of badge emblem pips at baseline `y`. */
function drawBadgePips(
    ctx: CanvasRenderingContext2D,
    k: ShareKartuData,
    left: number,
    y: number,
    story: boolean,
): void {
    ctx.font = `500 ${story ? 26 : 22}px "JetBrains Mono"`;
    let bx = left;
    k.tags.slice(0, 4).forEach((tag, i) => {
        const label = (k.tagEmojis[i] ?? '✦') + ' ' + tag;
        const lw = ctx.measureText(label).width + 24;
        roundRectPath(ctx, bx, y - 30, lw, 40, 20);
        ctx.fillStyle = 'rgba(246,241,232,0.10)';
        ctx.fill();
        ctx.fillStyle = 'rgba(246,241,232,0.85)';
        ctx.textBaseline = 'middle';
        ctx.textAlign = 'left';
        ctx.fillText(label, bx + 12, y - 9);
        bx += lw + 12;
    });
    ctx.textBaseline = 'alphabetic';
}

/**
 * Dark-frame TCG hero: a dark navy card with a vivid rarity border, a bright
 * art window up top (route hero + corner bunny + floating edition/TRIMP pills),
 * and a dark stat block below (rarity ribbon, name, subtitle, KM + stats,
 * badges). Mirrors the React Kartu component.
 */
function drawHero(d: DrawCtx): void {
    const { ctx, w, h, cfg, moodBunny } = d;
    const k = cfg.kartu;
    const story = cfg.format === 'story';
    const rarityCol = C.rarity[k.rarity] ?? C.line;
    const moodCol = moodSigilColor(k.mood);

    paintGlow(ctx, w / 2, h * 0.36, w * 0.5);

    const margin = PAD * 0.5;
    const cx = margin;
    const cy = margin;
    const cw = w - margin * 2;
    const ch = h - margin * 2;
    const border = 9;
    const radius = 44;
    const framePad = border + 18;

    // Dark card body + vivid rarity border.
    roundRectPath(ctx, cx, cy, cw, ch, radius);
    ctx.fillStyle = C.skyDeep;
    ctx.fill();
    ctx.lineWidth = border;
    ctx.strokeStyle = rarityCol;
    roundRectPath(ctx, cx + border / 2, cy + border / 2, cw - border, ch - border, radius - border / 2);
    ctx.stroke();

    // Inner content frame.
    const innerX = cx + framePad;
    const innerW = cw - framePad * 2;
    const innerTop = cy + framePad;
    const innerH = ch - framePad * 2;
    const blockGap = 22;

    // Size the block to its actual content (measure pass), then give the art
    // window all the remaining space — so the route grows and there's no dead
    // navy below the stats. Clamp so the art window keeps a healthy minimum.
    const makeBlock = (y: number, draw: boolean): HeroBlock => ({
        ctx,
        k,
        box: { x: innerX, y, w: innerW, h: 0 },
        rarityCol,
        story,
        showStats: cfg.showStats,
        showQuote: cfg.showQuote,
        draw,
    });
    const measuredBlockH = drawHeroBlock(makeBlock(innerTop, false)) + (story ? 20 : 14);
    const maxBlockH = innerH - Math.round(innerH * 0.4) - blockGap;
    const blockH = Math.min(measuredBlockH, maxBlockH);
    const artH = innerH - blockH - blockGap;

    drawHeroArtWindow(ctx, k, cfg.temariImg ?? moodBunny, { x: innerX, y: innerTop, w: innerW, h: artH }, rarityCol, moodCol, story);
    drawHeroBlock(makeBlock(innerTop + artH + blockGap, true));
}

function drawStruk(d: DrawCtx): void {
    const { ctx, w, h, cfg, pal, statItems } = d;
    let y = PAD;
    const dash = (): void => {
        ctx.strokeStyle = pal.divider;
        ctx.lineWidth = 3;
        ctx.setLineDash([10, 10]);
        ctx.beginPath();
        ctx.moveTo(PAD, y);
        ctx.lineTo(w - PAD, y);
        ctx.stroke();
        ctx.setLineDash([]);
        y += 36;
    };

    ctx.textAlign = 'center';
    ctx.textBaseline = 'alphabetic';
    ctx.font = '500 30px "JetBrains Mono"';
    ctx.letterSpacing = '6px';
    ctx.fillStyle = pal.meta;
    ctx.fillText('TEMANLARI', w / 2, y + 28);
    ctx.letterSpacing = '0px';
    y += 60;
    dash();

    ctx.textAlign = 'left';
    ctx.font = '700 30px "JetBrains Mono"';
    ctx.letterSpacing = '3px';
    ctx.fillStyle = C.horizon;
    ctx.fillText(`★ ${(RARITY_LABELS[cfg.kartu.rarity] ?? cfg.kartu.rarity).toUpperCase()}`, PAD, y + 26);
    ctx.letterSpacing = '0px';
    y += 56;
    ctx.font = 'italic 600 64px "Fraunces"';
    ctx.fillStyle = pal.name;
    const nameLines = wrapText(ctx, `${cfg.kartu.name}.`, w - PAD * 2);
    nameLines.forEach((ln) => {
        ctx.fillText(ln, PAD, y + 56);
        y += 70;
    });
    y += 16;
    dash();

    if (cfg.showStats) {
        const items = cfg.format === 'story' ? statItems : statItems.slice(0, 3);
        ctx.font = '700 34px "JetBrains Mono"';
        items.forEach((item) => {
            ctx.textAlign = 'left';
            ctx.letterSpacing = '1px';
            ctx.fillStyle = pal.meta;
            ctx.fillText(item.l.toUpperCase(), PAD, y + 30);
            ctx.letterSpacing = '0px';
            ctx.textAlign = 'right';
            ctx.fillStyle = pal.text;
            ctx.fillText(item.v, w - PAD, y + 30);
            y += 56;
        });
        y += 12;
    }

    const quote = cfg.format === 'story' && cfg.showQuote ? cfg.kartu.quote : null;
    if (quote) {
        dash();
        ctx.font = 'italic 500 38px "Fraunces"';
        ctx.fillStyle = pal.quote;
        ctx.textAlign = 'left';
        const lines = wrapText(ctx, `“${quote}”`, w - PAD * 2);
        lines.forEach((ln) => {
            ctx.fillText(ln, PAD, y + 38);
            y += 50;
        });
    }

    // Footer pinned to the bottom.
    y = h - PAD - 36;
    dash();
    ctx.font = '500 30px "JetBrains Mono"';
    ctx.fillStyle = pal.meta;
    ctx.textAlign = 'left';
    ctx.fillText(cfg.kartu.date ? cfg.kartu.date.replace('\n', ' · ') : 'TemanLari', PAD, y + 24);
    ctx.textAlign = 'right';
    ctx.fillText('teman-lari', w - PAD, y + 24);
}

const TEMPLATES: Record<Layout, (d: DrawCtx) => void> = {
    kartu: drawHero,
    rute: drawRute,
    struk: drawStruk,
};

function statItemsFor(kartu: ShareKartuData): Array<{ v: string; l: string }> {
    return [
        { v: kartu.km, l: 'KM' },
        { v: kartu.durasi, l: 'Durasi' },
        ...(kartu.pace ? [{ v: `${kartu.pace}/km`, l: 'Pace' }] : []),
        { v: String(kartu.trimp), l: 'TRIMP' },
        ...(kartu.hr ? [{ v: kartu.hr, l: 'HR' }] : []),
        ...(kartu.weather ? [{ v: kartu.weather, l: 'Cuaca' }] : []),
        ...(kartu.location ? [{ v: kartu.location.split(',')[0].trim(), l: 'Lokasi' }] : []),
    ];
}

/**
 * Draw the configured share card onto `canvas` at its fixed internal resolution.
 * Idempotent: safe to call on every config change to refresh the preview.
 */
export async function drawShareCard(canvas: HTMLCanvasElement, cfg: ShareCardConfig): Promise<void> {
    const { w, h } = DIMS[cfg.format];
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return;
    }

    await ensureFonts();
    const pal = palette(cfg.theme);
    const bunny = await loadBunny(pal.isDark ? 'cream' : 'ink');
    const moodBunny = await loadBunny('ink', moodSigilColor(cfg.kartu.mood));

    ctx.clearRect(0, 0, w, h);
    paintBackground(ctx, cfg.theme, w, h);

    const d: DrawCtx = { ctx, w, h, cfg, pal, bunny, moodBunny, statItems: statItemsFor(cfg.kartu) };
    TEMPLATES[cfg.layout](d);
}

/** Render the card and return it as a PNG blob (full internal resolution). */
export async function shareCardBlob(cfg: ShareCardConfig): Promise<Blob> {
    const canvas = document.createElement('canvas');
    await drawShareCard(canvas, cfg);
    return new Promise((resolve, reject) => {
        canvas.toBlob((blob) => (blob ? resolve(blob) : reject(new Error('toBlob failed'))), 'image/png');
    });
}

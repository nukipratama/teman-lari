/**
 * Contrast backing for the iOS status bar in the installed app.
 *
 * `apple-mobile-web-app-status-bar-style: black-translucent` (app.blade.php)
 * extends the web view up under the status bar and forces the clock, battery
 * and signal glyphs to render **white**, with no way to request dark ones. So
 * something dark has to sit under them on every screen, or the clock vanishes
 * against the cream app.
 *
 * A *gradient* rather than a solid strip, because a solid one is the bug this
 * whole thread started from: a hard dark edge above cream content reads as a
 * band stuck to the top of the app. Fading sky → transparent over a little more
 * than the inset gives the glyphs an opaque ground exactly where they sit and
 * dissolves before it meets page content, so there is no edge to notice. It is
 * also why the mobile top bar could go back to cream — the contrast comes from
 * here now, not from the bar.
 *
 * Sits above the modal layer (z-50/51) on purpose: every modal is
 * `fixed inset-0` and paints its own scrim over this region, and only some of
 * those are dark. Rather than auditing each one, this outranks them all.
 *
 * `env(safe-area-inset-top)` collapses to 0 in a browser tab and on desktop, so
 * the whole thing is a zero-height no-op outside the installed app.
 */
export default function StatusBarScrim() {
    return (
        <div
            aria-hidden
            data-testid="status-bar-scrim"
            className="pointer-events-none fixed inset-x-0 top-0 z-[70] h-[calc(env(safe-area-inset-top)+14px)] bg-gradient-to-b from-sky via-sky/80 to-transparent lg:hidden"
        />
    );
}

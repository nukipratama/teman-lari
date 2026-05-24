import { Link, usePage } from '@inertiajs/react';
import BrandMark from '@/components/BrandMark';
import { cn } from '@/lib/cn';
import { formatRelativeId } from '@/lib/pace';
import type { SharedProps, StravaSync } from '@/types/inertia';

/**
 * Compact header for `< lg` viewports. The desktop TopNav already covers brand
 * mark + sync status + avatar; on mobile that bar is hidden so this one carries
 * the same identity at the top while MobileBottomNav handles tab switching.
 */
export default function MobileTopBar() {
    const { props } = usePage<SharedProps>();
    const user = props.auth.user;
    const stravaSync = props.stravaSync ?? null;

    return (
        <header className="flex items-center justify-between gap-3 border-b border-cream-deep bg-cream px-5 py-3 lg:hidden">
            <Link href="/" aria-label="Beranda">
                <BrandMark size="compact" />
            </Link>
            <div className="flex items-center gap-2">
                <SyncDot sync={stravaSync} />
                {user && <Avatar name={user.name} avatarUrl={user.avatar_url} />}
            </div>
        </header>
    );
}

function SyncDot({ sync }: Readonly<{ sync: StravaSync | null }>) {
    const connected = sync !== null && sync.connected;
    const relative = connected && sync.last_synced_at ? formatRelativeId(sync.last_synced_at) : null;
    const syncedLabel = relative ? `Strava synced ${relative}` : 'Strava synced';
    const ariaLabel = connected ? syncedLabel : 'Strava belum nyambung';

    return (
        <span
            aria-label={ariaLabel}
            className="inline-flex items-center gap-1.5 rounded-full bg-sky/[0.06] px-2.5 py-1.5 font-mono text-[10px] uppercase tracking-[0.1em] text-ink-3"
        >
            <span aria-hidden className={cn('h-1.5 w-1.5 rounded-full', connected ? 'bg-leaf' : 'bg-ink-3/40')} />
            {connected ? (relative ?? 'Synced') : 'Strava'}
        </span>
    );
}

function Avatar({ name, avatarUrl }: Readonly<{ name: string; avatarUrl: string | null }>) {
    if (avatarUrl) {
        return (
            <img
                src={avatarUrl}
                alt=""
                className="h-8 w-8 rounded-full object-cover ring-2 ring-cream-deep"
            />
        );
    }

    return (
        <span
            aria-hidden
            className="flex h-8 w-8 items-center justify-center rounded-full bg-horizon font-display text-[15px] font-semibold italic text-sky ring-2 ring-cream-deep"
        >
            {name.charAt(0).toUpperCase()}
        </span>
    );
}

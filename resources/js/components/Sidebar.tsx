import { Link, usePage } from '@inertiajs/react';
import { Icon } from '@iconify/react';
import BrandMark from '@/components/BrandMark';
import { useSidebar } from '@/contexts/SidebarContext';
import { cn } from '@/lib/cn';
import type { AuthUser, SharedProps } from '@/types/inertia';

interface NavLink {
    route: string;
    href: string;
    icon: string;
    label: string;
}

const PRIMARY_LINKS: ReadonlyArray<NavLink> = [
    { route: 'dashboard', href: '/', icon: 'mdi:home-outline', label: 'Beranda' },
    { route: 'aktivitas.index', href: '/aktivitas', icon: 'mdi:run-fast', label: 'Aktivitas' },
    { route: 'kalender', href: '/kalender', icon: 'mdi:calendar-month-outline', label: 'Kalender' },
    { route: 'kartu.index', href: '/kartu', icon: 'mdi:cards-outline', label: 'Kartu' },
    { route: 'rekor', href: '/rekor', icon: 'mdi:trophy-variant-outline', label: 'Rekor' },
];

const SECONDARY_LINKS: ReadonlyArray<NavLink> = [
    { route: 'profil', href: '/profil', icon: 'mdi:account-outline', label: 'Profil' },
    { route: 'pengaturan', href: '/pengaturan', icon: 'mdi:cog-outline', label: 'Pengaturan' },
];

export default function Sidebar() {
    const { dialogRef, close } = useSidebar();
    const { props, url } = usePage<SharedProps>();
    const user = props.auth.user;

    return (
        <>
            <aside
                aria-label="Main navigation"
                className="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-20 lg:flex lg:w-64 lg:flex-col lg:border-r lg:border-line lg:bg-surface-elev"
            >
                <SidebarContent url={url} user={user} />
            </aside>

            <dialog
                ref={dialogRef}
                aria-label="Main navigation"
                className="sidebar-drawer m-0 max-h-screen w-72 max-w-[85vw] bg-surface-elev p-0 text-ink shadow-xl lg:hidden"
                onClose={close}
            >
                <SidebarContent url={url} user={user} onNavigate={close} />
            </dialog>
        </>
    );
}

function SidebarContent({
    url,
    user,
    onNavigate,
}: Readonly<{ url: string; user: AuthUser | null; onNavigate?: () => void }>) {
    return (
        <div className="flex h-full flex-col">
            <div className="border-b border-line px-5 py-5">
                <Link href="/" onClick={onNavigate} aria-label="Beranda">
                    <BrandMark size="compact" />
                </Link>
            </div>

            <nav className="flex flex-1 flex-col px-3 py-4">
                <NavList links={PRIMARY_LINKS} url={url} onNavigate={onNavigate} />
                <NavList
                    links={SECONDARY_LINKS}
                    url={url}
                    onNavigate={onNavigate}
                    className="mt-auto border-t border-line pt-3"
                />
            </nav>

            {user !== null && <UserChip user={user} />}
        </div>
    );
}

function NavList({
    links,
    url,
    onNavigate,
    className,
}: Readonly<{ links: ReadonlyArray<NavLink>; url: string; onNavigate?: () => void; className?: string }>) {
    return (
        <ul className={cn('space-y-1', className)}>
            {links.map((link) => {
                const active = url === link.href || url.startsWith(`${link.href}/`);
                return (
                    <li key={link.route}>
                        <Link
                            href={link.href}
                            onClick={onNavigate}
                            className={cn(
                                'flex items-center gap-3 rounded-lg border-l-4 px-3 py-2.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-leaf',
                                active
                                    ? 'border-leaf bg-leaf/10 text-leaf-deep'
                                    : 'border-transparent text-ink-2 hover:bg-line/40 hover:text-ink',
                            )}
                        >
                            <Icon icon={link.icon} width={18} height={18} aria-hidden />
                            <span>{link.label}</span>
                        </Link>
                    </li>
                );
            })}
        </ul>
    );
}

function UserChip({ user }: Readonly<{ user: AuthUser }>) {
    return (
        <div className="flex items-center gap-3 border-t border-line p-4">
            {user.avatar_url === null ? (
                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-leaf/15 text-sm font-semibold text-leaf-deep">
                    {user.name.charAt(0).toUpperCase()}
                </div>
            ) : (
                <img
                    src={user.avatar_url}
                    alt={user.name}
                    className="h-10 w-10 rounded-full ring-2 ring-line"
                />
            )}
            <div className="min-w-0 flex-1">
                <div className="truncate text-sm font-medium text-ink">{user.name}</div>
            </div>
        </div>
    );
}

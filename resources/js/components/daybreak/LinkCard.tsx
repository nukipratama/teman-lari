import { Link } from '@inertiajs/react';
import { type MouseEventHandler, type ReactNode } from 'react';
import { cn } from '@/lib/cn';
import { type CardPadding, type CardTone } from './Card';

interface LinkCardProps {
    href: string;
    /** Default 'cream'. */
    tone?: CardTone;
    /** Default 'md' — px-5 py-5. */
    padding?: CardPadding;
    onClick?: MouseEventHandler<Element>;
    className?: string;
    children: ReactNode;
}

const TONE_CLASS: Record<CardTone, string> = {
    cream: 'rounded-2xl border border-cream-deep bg-cream',
    'cream-deep': 'rounded-2xl bg-cream-deep',
    'sky-glass': 'rounded-2xl border border-cream/[0.12] bg-cream/[0.06] backdrop-blur',
    empty: 'rounded-2xl border-2 border-dashed border-cream-deep bg-cream/40',
};

const PADDING_CLASS: Record<CardPadding, string> = {
    none: '',
    sm: 'px-4 py-3.5',
    md: 'px-5 py-5',
    lg: 'px-6 py-6',
};

const HOVER = 'block transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-leaf focus-visible:ring-offset-2 focus-visible:ring-offset-cream';

export default function LinkCard({
    href,
    tone = 'cream',
    padding = 'md',
    onClick,
    className,
    children,
}: Readonly<LinkCardProps>) {
    return (
        <Link
            href={href}
            onClick={onClick}
            className={cn(TONE_CLASS[tone], PADDING_CLASS[padding], HOVER, className)}
        >
            {children}
        </Link>
    );
}

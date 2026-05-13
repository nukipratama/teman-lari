import { Icon } from '@iconify/react';

export default function DegradedChip() {
    return (
        <span
            role="status"
            aria-live="polite"
            className="inline-flex items-center gap-1 rounded-full bg-mood-cooked/15 px-2 py-0.5 text-[10px] font-semibold text-mood-cooked"
        >
            <Icon icon="mdi:tools" width={12} height={12} aria-hidden />
            mode darurat
        </span>
    );
}

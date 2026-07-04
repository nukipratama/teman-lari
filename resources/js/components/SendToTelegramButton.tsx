import { Icon } from '@iconify/react';
import PillButton from '@/components/ui/PillButton';
import DemoBlockedModal from '@/components/DemoBlockedModal';
import { usePendingPost } from '@/hooks/usePendingPost';
import { useDemoGuard } from '@/hooks/useDemoGuard';
import { cooldownAriaLabel, useCooldownCountdown } from '@/hooks/useCooldownCountdown';
import { formatDurationHMS } from '@/lib/pace';

/**
 * The manual "Kirim ke Telegram" pill shared by run/weekly/monthly recap
 * surfaces: force-pushes the Done narration at `url` and shows a spinner while
 * in flight. Callers gate rendering on `telegramConnected`. When the server
 * reports a `retryAfterSeconds` cooldown the button disables and shows a bare
 * countdown next to the paper-plane icon, so a re-send can't spam Telegram.
 */
export default function SendToTelegramButton({
    url,
    retryAfterSeconds,
}: Readonly<{ url: string; retryAfterSeconds?: number | null }>) {
    const [sending, send] = usePendingPost(url, { preserveScroll: true });
    const { open, setOpen, guard } = useDemoGuard();
    const cooldownRemaining = useCooldownCountdown(retryAfterSeconds);
    const cooling = cooldownRemaining > 0;

    let label = 'Kirim ke Telegram';
    if (cooling) {
        label = formatDurationHMS(cooldownRemaining);
    } else if (sending) {
        label = 'Lagi ngirim…';
    }

    return (
        <>
            <PillButton
                tone="outline"
                size="sm"
                disabled={sending || cooling}
                className="disabled:opacity-60 disabled:cursor-not-allowed"
                onClick={() => guard(send)}
                aria-label={cooldownAriaLabel(cooldownRemaining, 'kirim ke Telegram')}
            >
                <Icon
                    icon={sending ? 'mdi:loading' : 'mdi:send'}
                    width={15}
                    height={15}
                    className={sending ? 'animate-spin' : undefined}
                    aria-hidden
                />
                {label}
            </PillButton>
            <DemoBlockedModal open={open} onClose={() => setOpen(false)} />
        </>
    );
}

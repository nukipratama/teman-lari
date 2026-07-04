import { AnimatePresence, motion } from 'framer-motion';
import { router, usePage } from '@inertiajs/react';
import { useRef } from 'react';
import { Icon } from '@iconify/react';
import { useDismissable } from '@/hooks/useDismissable';
import { useFocusTrap } from '@/hooks/useFocusTrap';
import PillButton from '@/components/ui/PillButton';
import { iconButtonVariants } from '@/lib/variants';
import TemariProto, { type TemariEquipped } from '@/components/temari/TemariProto';
import { serverToEquipped } from '@/lib/equippedAccessories';
import type { SharedProps } from '@/types/inertia';

interface DemoBlockedModalProps {
    open: boolean;
    onClose: () => void;
}

/**
 * Friendly front door for a demo visitor hitting a blocked Telegram action.
 * The `block-demo-telegram` middleware is the real guard; this is the soft
 * upsell shown instead of a silent 403/redirect. Reuses the ShareCardModal
 * shell (not the celebration shell) since this is a calm nudge, not a win.
 */
export default function DemoBlockedModal({ open, onClose }: Readonly<DemoBlockedModalProps>) {
    const panelRef = useRef<HTMLDivElement>(null);

    // Read the shared equip state defensively (mirrors ShareCardModal) so this
    // still renders a bare bunny when there's no Inertia page context (e.g. unit tests).
    let equipped: TemariEquipped | null = null;
    try {
        const acc = usePage<SharedProps>().props.equippedAccessories;
        if (acc) {
            equipped = serverToEquipped(acc);
        }
    } catch {
        equipped = null;
    }

    useDismissable(open, panelRef, onClose);
    useFocusTrap(open, panelRef);

    if (!open) return null;

    const handleConnect = () => {
        router.post('/logout');
    };

    return (
        <AnimatePresence>
            <motion.div
                key="demo-blocked-backdrop"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                className="fixed inset-0 z-[51] flex items-center justify-center p-4"
                style={{ background: 'rgba(0,0,0,0.5)', backdropFilter: 'blur(6px)' }}
            >
                <motion.div
                    key="demo-blocked-panel"
                    ref={panelRef}
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="demo-blocked-title"
                    initial={{ opacity: 0, scale: 0.96, y: 8 }}
                    animate={{ opacity: 1, scale: 1, y: 0 }}
                    exit={{ opacity: 0, scale: 0.96, y: 8 }}
                    transition={{ duration: 0.3, ease: [0.22, 1, 0.36, 1] }}
                    className="flex w-full max-w-sm flex-col overflow-hidden rounded-3xl bg-cream shadow-2xl"
                >
                    <div className="flex justify-start px-3 pt-3">
                        <button
                            type="button"
                            onClick={onClose}
                            aria-label="Tutup"
                            className={iconButtonVariants({ size: 'sm' })}
                        >
                            <Icon icon="mdi:close" width={16} height={16} />
                        </button>
                    </div>

                    <div className="flex flex-col items-center gap-4 px-6 pb-6 pt-1 text-center">
                        <TemariProto pose="observational" size={120} equipped={equipped} animate />
                        <h2 id="demo-blocked-title" className="font-display text-2xl tracking-tight text-ink">
                            Telegram-nya lagi istirahat dulu
                        </h2>
                        <p className="font-sans text-sm leading-relaxed text-ink-2">
                            Ini kan masih demo, jadi Telegram aku matiin biar bot bareng-bareng ini gak kepencet
                            orang lain. Sambungin Strava-mu sendiri, nanti notif beneran masuk ke HP kamu.
                        </p>
                    </div>

                    <div className="flex flex-col gap-2 border-t border-cream-deep bg-cream px-5 py-4">
                        <PillButton
                            tone="horizon"
                            onClick={handleConnect}
                            className="w-full justify-center py-3.5 font-semibold"
                        >
                            Masuk pakai Strava
                        </PillButton>
                        <PillButton tone="ghost" onClick={onClose} className="w-full justify-center">
                            Nanti aja
                        </PillButton>
                    </div>
                </motion.div>
            </motion.div>
        </AnimatePresence>
    );
}

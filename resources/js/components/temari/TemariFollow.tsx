import { useEffect, useState, type RefObject } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import TemariMascot from './TemariMascot';
import type { Mood } from '@/types/inertia';

interface TemariFollowProps {
    sentinelRef: RefObject<HTMLElement | null>;
    mood: Mood;
}

export default function TemariFollow({ sentinelRef, mood }: Readonly<TemariFollowProps>) {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const el = sentinelRef.current;
        if (el === null) return;
        if (typeof IntersectionObserver === 'undefined') return;

        const observer = new IntersectionObserver(
            ([entry]) => setVisible(!entry.isIntersecting),
            { threshold: 0, rootMargin: '-80px 0px 0px 0px' },
        );
        observer.observe(el);
        return () => observer.disconnect();
    }, [sentinelRef]);

    const scrollToTop = () => {
        globalThis.scrollTo({ top: 0, behavior: 'smooth' });
    };

    return (
        <AnimatePresence>
            {visible && (
                <motion.button
                    type="button"
                    onClick={scrollToTop}
                    aria-label="Kembali ke briefing Temari"
                    initial={{ opacity: 0, scale: 0.7, x: 24 }}
                    animate={{ opacity: 1, scale: 1, x: 0 }}
                    exit={{ opacity: 0, scale: 0.7, x: 24 }}
                    transition={{ duration: 0.25, ease: 'easeOut' }}
                    className="fixed bottom-6 right-6 z-30 rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500"
                >
                    <TemariMascot
                        mood={mood}
                        sizeClass="h-20 w-20"
                        idle="breath"
                    />
                </motion.button>
            )}
        </AnimatePresence>
    );
}

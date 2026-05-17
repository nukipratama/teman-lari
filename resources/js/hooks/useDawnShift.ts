import { useEffect, useState } from 'react';

export type TimeOfDay = 'dawn' | 'morning' | 'day' | 'dusk' | 'night';

export function timeOfDayFor(date: Date): TimeOfDay {
    const hour = date.getHours();
    if (hour >= 4 && hour < 7) {
        return 'dawn';
    }
    if (hour >= 7 && hour < 10) {
        return 'morning';
    }
    if (hour >= 10 && hour < 17) {
        return 'day';
    }
    if (hour >= 17 && hour < 20) {
        return 'dusk';
    }
    return 'night';
}

// Sets `data-time-of-day` on <body> so CSS surface-tint rules apply.
// Re-evaluates every 5 minutes so a long-open tab eventually drifts
// across boundaries instead of staying stuck at first-render bucket.
export function useDawnShift(): TimeOfDay {
    const [tod, setTod] = useState<TimeOfDay>(() => timeOfDayFor(new Date()));

    useEffect(() => {
        const interval = window.setInterval(() => {
            setTod((prev) => {
                const next = timeOfDayFor(new Date());
                return prev === next ? prev : next;
            });
        }, 5 * 60 * 1000);
        return () => window.clearInterval(interval);
    }, []);

    // Write the attribute on every change; only clear it on unmount so
    // long-lived sessions don't briefly drop the attribute on each tick.
    useEffect(() => {
        document.body.dataset.timeOfDay = tod;
    }, [tod]);

    useEffect(() => {
        return () => {
            delete document.body.dataset.timeOfDay;
        };
    }, []);

    return tod;
}

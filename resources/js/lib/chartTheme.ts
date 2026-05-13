import { useEffect, useState } from 'react';
import type { ChartOptions } from 'chart.js';

// Chart.js canvas can't read CSS variables — colors resolved per scheme via matchMedia.
export interface ChartTheme {
    isDark: boolean;
    tick: string;
    grid: string;
    legend: string;
    tooltip: {
        backgroundColor: string;
        titleColor: string;
        bodyColor: string;
        borderColor: string;
        borderWidth: number;
    };
}

const LIGHT: ChartTheme = {
    isDark: false,
    tick: '#6f6358', // ink-meta
    grid: 'rgba(31, 27, 22, 0.08)', // ink @ 8%
    legend: '#1f1b16', // ink
    tooltip: {
        backgroundColor: '#ffffff',
        titleColor: '#1f1b16',
        bodyColor: '#1f1b16',
        borderColor: '#e5ded3',
        borderWidth: 1,
    },
};

const DARK: ChartTheme = {
    isDark: true,
    tick: '#d0c6b5', // ink-soft-dark — readable on surface-dark-elev
    grid: 'rgba(240, 235, 226, 0.12)', // ink-dark @ 12%
    legend: '#f0ebe2', // ink-dark
    tooltip: {
        backgroundColor: '#1f1c16',
        titleColor: '#f0ebe2',
        bodyColor: '#f0ebe2',
        borderColor: '#3d372e',
        borderWidth: 1,
    },
};

export function useChartTheme(): ChartTheme {
    const [isDark, setIsDark] = useState(() => prefersDark());

    useEffect(() => {
        const mq = typeof globalThis.matchMedia === 'function'
            ? globalThis.matchMedia('(prefers-color-scheme: dark)')
            : null;
        if (mq === null) return;
        /* v8 ignore next 3 — change event fires when the OS / browser
           flips colour scheme; not deterministically reproducible in
           jsdom. Verified by manual smoke. */
        const handler = (e: MediaQueryListEvent) => setIsDark(e.matches);
        mq.addEventListener('change', handler);
        return () => mq.removeEventListener('change', handler);
    }, []);

    return isDark ? DARK : LIGHT;
}

function prefersDark(): boolean {
    if (typeof globalThis.matchMedia !== 'function') return false;
    return globalThis.matchMedia('(prefers-color-scheme: dark)').matches;
}

export function tooltipFromTheme(theme: ChartTheme): NonNullable<NonNullable<ChartOptions['plugins']>['tooltip']> {
    return {
        enabled: true,
        backgroundColor: theme.tooltip.backgroundColor,
        titleColor: theme.tooltip.titleColor,
        bodyColor: theme.tooltip.bodyColor,
        borderColor: theme.tooltip.borderColor,
        borderWidth: theme.tooltip.borderWidth,
        padding: 10,
        titleFont: { size: 12, weight: 'bold' },
        bodyFont: { size: 12 },
        boxPadding: 6,
        usePointStyle: true,
    };
}

export function formatNumericTooltip(label: string, y: number | null, unit = ''): string {
    if (y === null) return `${label}: —`;
    const suffix = unit === '' ? '' : ` ${unit}`;
    return `${label}: ${y.toFixed(1)}${suffix}`;
}

export function kmAxisTick(value: string | number): string {
    return `${value} km`;
}

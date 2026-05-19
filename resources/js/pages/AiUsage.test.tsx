import { describe, expect, it, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import AiUsage from './AiUsage';

const routerGet = vi.fn();

vi.mock('@inertiajs/react', () => ({
    Head: ({ children }: { children?: React.ReactNode }) => <>{children}</>,
    router: {
        get: (...args: unknown[]) => routerGet(...args),
    },
}));

const baseProps = {
    from: '2026-05-01',
    to: '2026-05-19',
    totals: { prompt: 600, completion: 280, total: 880, calls: 3 },
    byKind: [
        { kind: 'run-insight', prompt: 300, completion: 150, total: 450, calls: 1 },
        { kind: 'briefing', prompt: 300, completion: 130, total: 430, calls: 2 },
    ],
};

describe('AiUsage page', () => {
    it('shows totals + active date range + breakdown rows', () => {
        render(<AiUsage {...baseProps} />);

        // Heading
        expect(screen.getByText('AI Usage')).toBeInTheDocument();
        // Date range badge
        expect(screen.getByText('2026-05-01')).toBeInTheDocument();
        expect(screen.getByText('2026-05-19')).toBeInTheDocument();
        // Total tokens KPI
        expect(screen.getByText('880')).toBeInTheDocument();
        // Per-kind rows
        expect(screen.getByText('run-insight')).toBeInTheDocument();
        expect(screen.getByText('briefing')).toBeInTheDocument();
        // Prompt-share label
        expect(screen.getByText(/68% dari total/)).toBeInTheDocument();
    });

    it('renders an empty state when byKind is empty', () => {
        render(
            <AiUsage
                {...baseProps}
                totals={{ prompt: 0, completion: 0, total: 0, calls: 0 }}
                byKind={[]}
            />,
        );

        expect(screen.getByText('Belum ada token tercatat di rentang ini.')).toBeInTheDocument();
    });

    it('navigates with form submit', () => {
        routerGet.mockClear();
        render(<AiUsage {...baseProps} />);

        fireEvent.click(screen.getByRole('button', { name: /terapkan/i }));

        expect(routerGet).toHaveBeenCalledWith(
            '/ai-usage',
            { from: '2026-05-01', to: '2026-05-19' },
            { preserveState: true, preserveScroll: true },
        );
    });

    it('navigates via preset buttons', () => {
        routerGet.mockClear();
        render(<AiUsage {...baseProps} />);

        fireEvent.click(screen.getByRole('button', { name: /hari ini/i }));

        expect(routerGet).toHaveBeenCalledTimes(1);
        const [path, params] = routerGet.mock.calls[0];
        expect(path).toBe('/ai-usage');
        expect(params).toMatchObject({ from: expect.any(String), to: expect.any(String) });
    });
});

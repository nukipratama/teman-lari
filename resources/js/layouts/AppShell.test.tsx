import { render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';
import AppShell from './AppShell';
import { setMockPage } from '@/test/setup';

describe('AppShell', () => {
    afterEach(() => {
        delete document.body.dataset.timeOfDay;
    });

    it('sets a data-time-of-day attribute on body via useDawnShift', () => {
        setMockPage({
            auth: { user: { id: 1, name: 'A', first_name: 'A', avatar_url: null } },
            flash: {},
            demoLoginEnabled: false,
        });
        render(
            <AppShell>
                <p>x</p>
            </AppShell>,
        );
        expect(document.body.dataset.timeOfDay).toMatch(/^(dawn|morning|day|dusk|night)$/);
    });

    it('renders header + children by default', () => {
        setMockPage({
            auth: { user: { id: 1, name: 'A', first_name: 'A', avatar_url: null } },
            flash: {},
            demoLoginEnabled: false,
        });
        render(
            <AppShell>
                <p>child content</p>
            </AppShell>,
        );
        expect(screen.getByText('child content')).toBeInTheDocument();
        // BrandMark renders in both the persistent sidebar (hidden on `<lg`)
        // and the mobile topbar — getAllByText handles both being in DOM.
        expect(screen.getAllByText('TemanLari').length).toBeGreaterThan(0);
    });

    it('omits sidebar when showSidebar is false', () => {
        setMockPage({ auth: { user: null }, flash: {}, demoLoginEnabled: false });
        render(
            <AppShell showSidebar={false}>
                <p>only child</p>
            </AppShell>,
        );
        expect(screen.queryByText('TemanLari')).not.toBeInTheDocument();
        expect(screen.getByText('only child')).toBeInTheDocument();
    });
});

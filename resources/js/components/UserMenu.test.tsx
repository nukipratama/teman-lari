import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/react';
import UserMenu from './UserMenu';

beforeEach(() => {
    vi.mocked(router.post).mockReset();
});

describe('UserMenu', () => {
    it('opens the dropdown on click and shows the user name + logout', () => {
        render(<UserMenu name="Ada Lovelace" avatarUrl={null} />);
        fireEvent.click(screen.getByLabelText(/Buka menu Ada Lovelace/));
        expect(screen.getByText('Masuk sebagai')).toBeInTheDocument();
        expect(screen.getByText('Ada Lovelace')).toBeInTheDocument();
        expect(screen.getByText('Keluar')).toBeInTheDocument();
    });

    it('posts to /logout when the Keluar button is clicked', () => {
        render(<UserMenu name="Ada Lovelace" avatarUrl={null} />);
        fireEvent.click(screen.getByLabelText(/Buka menu Ada Lovelace/));
        fireEvent.click(screen.getByText('Keluar'));
        expect(router.post).toHaveBeenCalledWith('/logout');
    });

    it('closes the dropdown when Escape is pressed', () => {
        render(<UserMenu name="Ada Lovelace" avatarUrl={null} />);
        fireEvent.click(screen.getByLabelText(/Buka menu Ada Lovelace/));
        expect(screen.getByText('Keluar')).toBeInTheDocument();
        fireEvent.keyDown(document, { key: 'Escape' });
        expect(screen.queryByText('Keluar')).not.toBeInTheDocument();
    });

    it('returns focus to the trigger button when Escape is pressed', () => {
        render(<UserMenu name="Ada Lovelace" avatarUrl={null} />);
        const trigger = screen.getByLabelText(/Buka menu Ada Lovelace/);
        trigger.focus();
        fireEvent.click(trigger);
        fireEvent.keyDown(document, { key: 'Escape' });
        expect(document.activeElement).toBe(trigger);
    });

    it('does not adopt ARIA menu semantics (disclosure popover, not a menu)', () => {
        render(<UserMenu name="Ada Lovelace" avatarUrl={null} />);
        fireEvent.click(screen.getByLabelText(/Buka menu Ada Lovelace/));
        expect(screen.queryByRole('menu')).not.toBeInTheDocument();
        expect(screen.queryByRole('menuitem')).not.toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Keluar' })).toBeInTheDocument();
    });

    it('renders the avatar image when avatar_url is provided', () => {
        render(<UserMenu name="Ada Lovelace" avatarUrl="https://example.com/a.jpg" />);
        const avatarButton = screen.getByLabelText(/Buka menu Ada/);
        expect(avatarButton.querySelector('img')).not.toBeNull();
    });
});

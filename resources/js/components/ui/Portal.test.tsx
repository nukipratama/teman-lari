import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import Portal from './Portal';

describe('Portal', () => {
    it('renders children directly into document.body, escaping the render container', () => {
        const { container } = render(
            <Portal>
                <span data-testid="portaled">hi</span>
            </Portal>,
        );

        const el = screen.getByTestId('portaled');
        expect(el).toBeInTheDocument();
        // Escaped the render container and mounted straight onto <body>.
        expect(container).not.toContainElement(el);
        expect(el.parentElement).toBe(document.body);
    });
});

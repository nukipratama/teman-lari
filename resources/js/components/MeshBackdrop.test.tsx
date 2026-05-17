import { render } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import MeshBackdrop from './MeshBackdrop';

describe('MeshBackdrop', () => {
    it('renders three blob divs by default (dawn variant)', () => {
        const { container } = render(<MeshBackdrop />);
        // Outer wrapper + 3 blobs = 4 divs total
        const divs = container.querySelectorAll('div');
        expect(divs).toHaveLength(4);
    });

    it.each(['dawn', 'night', 'ember'] as const)('renders %s variant', (variant) => {
        const { container } = render(<MeshBackdrop variant={variant} />);
        expect(container.querySelectorAll('div')).toHaveLength(4);
    });

    it('is aria-hidden so it never reaches the accessibility tree', () => {
        const { container } = render(<MeshBackdrop />);
        const wrapper = container.firstElementChild;
        expect(wrapper).toHaveAttribute('aria-hidden');
    });

    it('passes additional className through to the wrapper', () => {
        const { container } = render(<MeshBackdrop className="rounded-3xl" />);
        expect(container.firstElementChild).toHaveClass('rounded-3xl');
    });
});

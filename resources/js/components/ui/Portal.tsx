import { type ReactNode } from 'react';
import { createPortal } from 'react-dom';

/**
 * Renders children into <body>, escaping any ancestor stacking context.
 *
 * Page content is wrapped in an enter-animation `motion.div` whose animated
 * opacity/transform creates a stacking context — fixed-position descendants
 * (overlays, tooltips) get trapped below sibling chrome like the bottom nav,
 * regardless of their z-index. Any overlay mounted INSIDE page content (rather
 * than at the AppShell root, where modals/toasts already live) must render
 * through this Portal so it lands above the chrome.
 */
export default function Portal({ children }: Readonly<{ children: ReactNode }>) {
    if (typeof document === 'undefined') {
        return null;
    }

    return createPortal(children, document.body);
}

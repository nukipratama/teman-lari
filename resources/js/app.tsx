import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ComponentType } from 'react';
import { addCollection } from '@iconify/react';
import ErrorBoundary from '@/components/ErrorBoundary';
import { installGlobalErrorReporting } from '@/lib/clientErrorReporter';
import { mdiBundle } from '@/lib/iconBundle';

const APP_NAME = import.meta.env.VITE_APP_NAME ?? 'Temari';

// Render mdi icons from the bundled collection so <Icon> never fetches from
// api.iconify.design (offline + no external connect-src). See lib/iconBundle.ts.
addCollection(mdiBundle);

installGlobalErrorReporting();

void createInertiaApp({
    title: (title) => (title ? `${title} · ${APP_NAME}` : APP_NAME),
    resolve: async (name) => {
        const pages = import.meta.glob<{ default: ComponentType }>([
            './pages/**/*.tsx',
            '!./pages/**/*.test.tsx',
        ]);
        const importer = pages[`./pages/${name}.tsx`];
        if (!importer) {
            throw new Error(`Inertia page not found: ${name}`);
        }
        const module = await importer();
        return module.default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <ErrorBoundary>
                <App {...props} />
            </ErrorBoundary>,
        );
    },
    progress: {
        color: '#0E7A4C',
        showSpinner: false,
    },
});

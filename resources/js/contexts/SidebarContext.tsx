import { createContext, useContext, useMemo, useRef, type ReactNode, type RefObject } from 'react';

interface SidebarContextValue {
    dialogRef: RefObject<HTMLDialogElement | null>;
    open: () => void;
    close: () => void;
}

const SidebarContext = createContext<SidebarContextValue | null>(null);

export function SidebarProvider({ children }: Readonly<{ children: ReactNode }>) {
    const dialogRef = useRef<HTMLDialogElement | null>(null);

    const value = useMemo<SidebarContextValue>(
        () => ({
            dialogRef,
            open: () => dialogRef.current?.showModal(),
            close: () => dialogRef.current?.close(),
        }),
        [],
    );

    return <SidebarContext.Provider value={value}>{children}</SidebarContext.Provider>;
}

export function useSidebar(): SidebarContextValue {
    const value = useContext(SidebarContext);
    if (value === null) {
        throw new Error('useSidebar must be used inside a <SidebarProvider>');
    }
    return value;
}

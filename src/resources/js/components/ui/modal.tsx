import { type ReactNode, type RefObject, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

export type ModalSize = 'sm' | 'md' | 'lg' | 'xl' | 'full';

export interface ModalProps {
    isOpen: boolean;
    onClose: () => void;
    title?: ReactNode;
    description?: ReactNode;
    children: ReactNode;
    footer?: ReactNode;
    size?: ModalSize;
    closeOnBackdropClick?: boolean;
    closeOnEsc?: boolean;
    showCloseButton?: boolean;
    darkenBackground?: boolean;
    backdropClassName?: string;
    className?: string;
    panelClassName?: string;
    initialFocusRef?: RefObject<HTMLElement>;
}

const sizeClasses: Record<ModalSize, string> = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-2xl',
    full: 'max-w-4xl',
};

function Modal({
    isOpen,
    onClose,
    title,
    description,
    children,
    footer,
    size = 'md',
    closeOnBackdropClick = true,
    closeOnEsc = true,
    showCloseButton = true,
    darkenBackground = true,
    backdropClassName,
    className = '',
    panelClassName = '',
    initialFocusRef,
}: ModalProps) {
    const [mounted, setMounted] = useState(false);
    const [entered, setEntered] = useState(false);
    const panelRef = useRef<HTMLDivElement>(null);

    // Avoid SSR portal mismatches.
    useEffect(() => setMounted(true), []);

    // Trigger the enter transition on the next frame after mount.
    useEffect(() => {
        if (!isOpen) {
            setEntered(false);
            return;
        }
        const raf = requestAnimationFrame(() => setEntered(true));
        return () => cancelAnimationFrame(raf);
    }, [isOpen]);

    // Escape to close.
    useEffect(() => {
        if (!isOpen || !closeOnEsc) return;
        const handler = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [isOpen, closeOnEsc, onClose]);

    // Lock page scroll while open.
    useEffect(() => {
        if (!isOpen) return;
        const original = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = original;
        };
    }, [isOpen]);

    // Focus the panel (or given element) on open.
    useEffect(() => {
        if (isOpen) {
            (initialFocusRef?.current ?? panelRef.current)?.focus();
        }
    }, [isOpen, initialFocusRef]);

    if (!mounted || !isOpen) return null;

    const resolvedBackdropClass =
        backdropClassName ?? (darkenBackground ? 'bg-slate-900/60 backdrop-blur-sm' : 'bg-slate-900/20');

    return createPortal(
        <div
            className={`fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 ${className}`}
            role="dialog"
            aria-modal="true"
            aria-labelledby={title ? 'modal-title' : undefined}
        >
            {/* Backdrop */}
            <div
                className={`absolute inset-0 transition-opacity duration-200 ${resolvedBackdropClass} ${
                    entered ? 'opacity-100' : 'opacity-0'
                }`}
                onClick={() => closeOnBackdropClick && onClose()}
                aria-hidden="true"
            />

            {/* Panel */}
            <div
                ref={panelRef}
                tabIndex={-1}
                className={`relative w-full ${sizeClasses[size]} rounded-2xl bg-white shadow-xl ring-1 ring-slate-900/5 outline-none transition-all duration-200 ${
                    entered ? 'opacity-100 scale-100' : 'opacity-0 scale-95'
                } ${panelClassName}`}
            >
                {(title || showCloseButton) && (
                    <div className="flex items-start justify-between gap-4 px-6 py-5 border-b border-slate-200">
                        <div className="min-w-0">
                            {title && (
                                <h2 id="modal-title" className="text-base font-semibold text-slate-900">
                                    {title}
                                </h2>
                            )}
                            {description && <p className="mt-1 text-sm text-slate-500">{description}</p>}
                        </div>

                        {showCloseButton && (
                            <button
                                type="button"
                                onClick={onClose}
                                className="shrink-0 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition"
                                aria-label="Close"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        )}
                    </div>
                )}

                <div className="px-6 py-5 max-h-[70vh] overflow-y-auto">{children}</div>

                {footer && (
                    <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl">
                        {footer}
                    </div>
                )}
            </div>
        </div>,
        document.body,
    );
}

export default Modal;

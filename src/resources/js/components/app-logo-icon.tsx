import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 64 64"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            >
            {/* Document */}
            <rect x="8" y="10" width="28" height="36" rx="2" fill="#ffffff" stroke="#2563eb" strokeWidth="2" />
            <path d="M8 18h28M8 26h28M8 34h20" stroke="#93c5fd" strokeWidth="2" strokeLinecap="round" />

            {/* Megaphone */}
            <path
                d="M38 28l14-6v20l-14-6v-8z"
                fill="#ffffff"
                stroke="#2563eb"
                strokeWidth="2"
                strokeLinejoin="round"
            />
            <rect x="52" y="26" width="4" height="12" rx="1" fill="#2563eb" />
            <circle cx="40" cy="36" r="3" fill="#facc15" />

            {/* Sound lines */}
            <path d="M58 24l4-2M58 40l4 2M60 32h6" stroke="#facc15" strokeWidth="2" strokeLinecap="round" />

            {/* Optional school silhouette */}
            <path
                d="M20 52h24l-12-10-12 10zM32 42v-4"
                stroke="#2563eb"
                strokeWidth="2"
                strokeLinecap="round"
            />
            </svg>
    );
}

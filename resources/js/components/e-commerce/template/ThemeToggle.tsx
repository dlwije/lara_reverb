'use client';
import { useAppearance } from '@/hooks/use-appearance';
import { useEffect, useState } from 'react';

export default function ThemeToggle() {
    const { appearance, updateAppearance } = useAppearance();
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setMounted(true);
    }, []);

    const toggleTheme = () => {
        updateAppearance(appearance === 'light' ? 'dark' : 'light');
    };

    // Don't render anything until mounted to prevent hydration mismatch
    if (!mounted) {
        return (
            <div className="bg-muted relative flex h-8 w-8 items-center justify-center rounded-full sm:h-6 sm:w-10">
                <div className="bg-background flex h-4 w-4 items-center justify-center rounded-full shadow-md sm:h-5 sm:w-5">
                    <span className="text-xs">🌙</span>
                </div>
            </div>
        );
    }

    return (
        <button
            onClick={toggleTheme}
            className="group relative touch-manipulation"
            title={`Switch to ${appearance === 'light' ? 'dark' : 'light'} mode`}
            aria-label={`Switch to ${appearance === 'light' ? 'dark' : 'light'} mode`}
        >
            {/* Mobile: Icon-only button */}
            <div className="flex h-8 w-8 items-center justify-center rounded-full border border-emerald-200/50 bg-gradient-to-r from-emerald-200/80 to-green-200/80 shadow-lg backdrop-blur-sm transition-all duration-300 hover:from-emerald-300/80 hover:to-green-300/80 hover:shadow-xl sm:hidden dark:border-emerald-700/50 dark:from-emerald-900/80 dark:to-green-900/80 dark:hover:from-emerald-800/80 dark:hover:to-green-800/80">
                <span className="text-sm transition-transform duration-300 group-hover:scale-110">{appearance === 'light' ? '☀️' : '🌙'}</span>
            </div>

            {/* Desktop: Full toggle */}
            <div className="relative hidden h-6 w-12 rounded-full border border-emerald-200/50 bg-gradient-to-r from-emerald-200/80 to-green-200/80 shadow-lg backdrop-blur-sm transition-all duration-300 hover:from-emerald-300/80 hover:to-green-300/80 hover:shadow-xl sm:block dark:border-emerald-700/50 dark:from-emerald-900/80 dark:to-green-900/80 dark:hover:from-emerald-800/80 dark:hover:to-green-800/80">
                {/* Toggle Track */}
                <div className="absolute inset-0 rounded-full bg-gradient-to-r from-emerald-50/30 to-green-50/30 dark:from-emerald-950/30 dark:to-green-950/30"></div>

                {/* Toggle Button */}
                <div
                    className={`absolute top-0.5 flex h-5 w-5 transform items-center justify-center rounded-full bg-white shadow-md transition-all duration-300 group-hover:scale-110 dark:bg-gray-800 ${
                        appearance === 'light'
                            ? 'left-0.5 bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-gray-800 dark:to-gray-700'
                            : 'left-6 bg-gradient-to-br from-emerald-50 to-green-50 dark:from-gray-700 dark:to-gray-800'
                    }`}
                >
                    <span
                        className={`text-sm transition-all duration-300 ${
                            appearance === 'light' ? 'text-yellow-600 dark:text-yellow-400' : 'text-emerald-600 dark:text-emerald-400'
                        }`}
                    >
                        {appearance === 'light' ? '☀️' : '🌙'}
                    </span>
                </div>

                {/* Background Icons */}
                <div className="pointer-events-none absolute inset-0 flex items-center justify-between px-1.5">
                    <span className={`text-xs transition-opacity duration-300 ${appearance === 'light' ? 'opacity-0' : 'opacity-40'}`}>☀️</span>
                    <span className={`text-xs transition-opacity duration-300 ${appearance === 'light' ? 'opacity-40' : 'opacity-0'}`}>🌙</span>
                </div>
            </div>
        </button>
    );
}

'use client';

import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

interface AutoCompleteProps {
    endpoint: string;
    onSelect?: (option: any) => void;
    placeholder?: string;
    defaultValue?: number | string | null;
    extraParams?: Record<string, any>;
}

export default function AutoCompleteDrop({
    endpoint,
    onSelect,
    placeholder = 'Select an option...',
    defaultValue = null,
    extraParams = {},
}: AutoCompleteProps) {
    const [inputValue, setInputValue] = useState('');
    const [selected, setSelected] = useState<any>(null);
    const [options, setOptions] = useState<any[]>([]);
    const [showDropdown, setShowDropdown] = useState(false);
    const [highlightedIndex, setHighlightedIndex] = useState(0);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [isLoading, setIsLoading] = useState(false);

    const containerRef = useRef<HTMLDivElement>(null);
    const listRef = useRef<HTMLUListElement>(null);

    /** 🔍 Fetch options with pagination and dynamic params */
    const fetchOptions = useCallback(
        async (reset = false, search = inputValue, pageNum = page) => {
            if (isLoading || (!reset && !hasMore)) return;
            setIsLoading(true);

            try {
                const { data } = await axios.get(endpoint, {
                    params: {
                        searchTerm: search,
                        page: pageNum,
                        resCount: 10,
                        ...(extraParams || {}),
                    },
                });

                const newOptions = data.results || [];
                setOptions((prev) => (reset ? newOptions : [...prev, ...newOptions]));
                setHasMore(data.pagination?.more || false);
            } catch (error) {
                console.error('Error fetching options', error);
            } finally {
                setIsLoading(false);
            }
        },
        [endpoint, inputValue, page, extraParams, isLoading, hasMore],
    );

    /** ✍️ Handle input changes */
    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setInputValue(value);
        setPage(1);
        setHasMore(true);
        setOptions([]);
        fetchOptions(true, value, 1);
        setShowDropdown(true);
    };

    /** ✅ Handle option selection */
    const handleSelect = (option: any) => {
        setSelected(option);
        setInputValue(option.text);
        setShowDropdown(false);
        onSelect?.(option);
    };

    /** ⌨️ Handle keyboard navigation */
    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (!showDropdown) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setHighlightedIndex((prev) => {
                const next = prev + 1;
                if (next >= options.length - 1 && hasMore) {
                    const nextPage = page + 1;
                    setPage(nextPage);
                    fetchOptions(false, inputValue, nextPage);
                }
                return Math.min(next, options.length - 1);
            });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setHighlightedIndex((prev) => Math.max(prev - 1, 0));
        } else if (e.key === 'Enter' && options[highlightedIndex]) {
            e.preventDefault();
            handleSelect(options[highlightedIndex]);
        } else if (e.key === 'Escape') {
            setShowDropdown(false);
        }
    };

    /** 🖱️ Close dropdown when clicking outside */
    useEffect(() => {
        const handleClickOutside = (e: MouseEvent) => {
            if (!containerRef.current?.contains(e.target as Node)) {
                setShowDropdown(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    /** 🔁 Refetch when `extraParams` changes */
    useEffect(() => {
        if (!extraParams || Object.keys(extraParams).length === 0) return;

        const timer = setTimeout(() => {
            setInputValue('');
            setOptions([]);
            setPage(1);
            setHasMore(true);
            setShowDropdown(false);
            fetchOptions(true, '', 1);
        }, 150);

        return () => clearTimeout(timer);
    }, [JSON.stringify(extraParams)]);

    /** 📜 Infinite scroll inside dropdown */
    useEffect(() => {
        const el = listRef.current;
        if (!el) return;

        const handleScroll = () => {
            const { scrollTop, scrollHeight, clientHeight } = el;
            if (scrollTop + clientHeight >= scrollHeight - 10 && hasMore && !isLoading) {
                const nextPage = page + 1;
                setPage(nextPage);
                fetchOptions(false, inputValue, nextPage);
            }
        };

        el.addEventListener('scroll', handleScroll);
        return () => el.removeEventListener('scroll', handleScroll);
    }, [inputValue, page, hasMore, isLoading]);

    /** 🎯 Fetch and prefill default value */
    useEffect(() => {
        if (!defaultValue) return;

        const fetchDefault = async () => {
            try {
                const { data } = await axios.get(endpoint, {
                    params: { searchTerm: '', page: 1, resCount: 50 },
                });
                const matched = data.results.find((item: any) => item.id === defaultValue);
                if (matched) {
                    setSelected(matched);
                    setInputValue(matched.text);
                    onSelect?.(matched);
                }
            } catch (error) {
                console.error('Error fetching default value', error);
            }
        };
        fetchDefault();
    }, [defaultValue]);

    return (
        <div ref={containerRef} className="relative w-full">
            {/* 🔹 Input */}
            <Input
                type="text"
                placeholder={placeholder}
                value={inputValue}
                onChange={handleInputChange}
                onFocus={() => {
                    setShowDropdown(true);
                    if (options.length === 0) fetchOptions(true, inputValue, 1);
                }}
                onKeyDown={handleKeyDown}
            />

            {/* 🔹 Dropdown */}
            {showDropdown && (
                <div className="bg-popover text-popover-foreground animate-in fade-in-0 zoom-in-95 absolute left-0 right-0 top-full z-50 mt-1 rounded-md border shadow-md">
                    <ScrollArea ref={listRef} className="max-h-52 w-full">
                        {/* No results */}
                        {!isLoading && options.length === 0 && (
                            <div className="text-muted-foreground select-none px-3 py-2 text-sm">No results found</div>
                        )}

                        {/* Options */}
                        {options.map((option, idx) => (
                            <div
                                key={option.id}
                                title={option.text}
                                className={cn(
                                    'hover:bg-accent hover:text-accent-foreground cursor-pointer truncate px-3 py-2 text-sm',
                                    highlightedIndex === idx && 'bg-accent text-accent-foreground',
                                )}
                                onMouseEnter={() => setHighlightedIndex(idx)}
                                onClick={() => handleSelect(option)}
                            >
                                {option.text}
                            </div>
                        ))}

                        {/* Loading */}
                        {isLoading && <div className="text-muted-foreground select-none px-3 py-2 text-sm">Loading...</div>}
                    </ScrollArea>
                </div>
            )}
        </div>
    );
}

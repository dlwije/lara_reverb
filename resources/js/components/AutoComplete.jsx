import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

const AutoComplete = ({
    endpoint = '',
    onSelect = '',
    placeholder = 'Search...',
    defaultValue = null,
    extraParams = {}, // ✅ this enables dynamic parameter injection
}) => {
    const [inputValue, setInputValue] = useState('');
    const [selected, setSelected] = useState(null);
    const [options, setOptions] = useState([]);
    const [showDropdown, setShowDropdown] = useState(false);
    const [highlightedIndex, setHighlightedIndex] = useState(0);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [isLoading, setIsLoading] = useState(false);
    const containerRef = useRef(null);
    const listRef = useRef(null);

    const fetchOptions = async (reset = false, search = inputValue, pageNum = page) => {
        if (isLoading || (!reset && !hasMore)) return;
        setIsLoading(true);
        try {
            const res = await axios.get(endpoint, {
                params: {
                    searchTerm: search,
                    page: pageNum,
                    resCount: 10,
                    ...(extraParams || {}),
                },
            });

            const newOptions = res.data.results || [];
            setOptions((prev) => (reset ? newOptions : [...prev, ...newOptions]));
            setHasMore(res.data.pagination?.more || false);
        } catch (err) {
            console.error('Error fetching options', err);
        } finally {
            setIsLoading(false);
        }
    };

    const handleInputChange = (e) => {
        const val = e.target.value;
        setInputValue(val);
        setPage(1);
        setHasMore(true);
        setOptions([]);
        fetchOptions(true, val, 1);
        setShowDropdown(true);
    };

    const handleSelect = (option) => {
        setSelected(option);
        setInputValue(option.text);
        setShowDropdown(false);
        onSelect && onSelect(option);
    };

    const handleKeyDown = (e) => {
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
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (options[highlightedIndex]) {
                handleSelect(options[highlightedIndex]);
            }
        } else if (e.key === 'Escape') {
            setShowDropdown(false);
        }
    };

    const handleClickOutside = (e) => {
        if (!containerRef.current?.contains(e.target)) {
            setShowDropdown(false);
        }
    };

    useEffect(() => {
        const resetAndFetch = async () => {
            if (!extraParams || Object.keys(extraParams).length === 0) return;

            setInputValue('');
            setOptions([]);
            setPage(1);
            setHasMore(true);
            setShowDropdown(false); // only show after data loads

            await fetchOptions(true, '', 1);
        };

        // debounce-like delay to avoid multiple rapid triggers
        const timer = setTimeout(() => {
            resetAndFetch();
        }, 150); // adjust delay if needed

        return () => clearTimeout(timer); // clean up on rapid changes
    }, [JSON.stringify(extraParams)]);

    // Scroll listener for infinite scroll
    useEffect(() => {
        const listEl = listRef.current;
        if (!listEl) return;

        const handleScroll = () => {
            const { scrollTop, scrollHeight, clientHeight } = listEl;
            if (scrollTop + clientHeight >= scrollHeight - 10 && hasMore && !isLoading) {
                const nextPage = page + 1;
                setPage(nextPage);
                fetchOptions(false, inputValue, nextPage);
            }
        };

        listEl.addEventListener('scroll', handleScroll);
        return () => listEl.removeEventListener('scroll', handleScroll);
    }, [inputValue, page, hasMore, isLoading]);

    useEffect(() => {
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    useEffect(() => {
        if (defaultValue) {
            const fetchDefault = async () => {
                try {
                    const response = await axios.get(endpoint, {
                        params: {
                            searchTerm: '',
                            page: 1,
                            resCount: 50,
                        },
                    });
                    const matched = response.data.results.find((item) => item.id === defaultValue);
                    if (matched) {
                        setSelected(matched);
                        setInputValue(matched.text);
                        onSelect && onSelect(matched);
                    }
                } catch (err) {
                    console.error('Error fetching default value', err);
                }
            };
            fetchDefault();
        }
    }, [defaultValue]);

    console.log(options);
    return (
        <div className="relative" ref={containerRef}>
            <input
                type="text"
                placeholder={placeholder}
                value={inputValue}
                onChange={handleInputChange}
                onFocus={() => {
                    setShowDropdown(true);
                    if (options.length === 0) fetchOptions(true, inputValue, 1);
                }}
                onKeyDown={handleKeyDown}
                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            />

            {showDropdown && (
                <ul
                    ref={listRef}
                    className="absolute z-50 mt-1 w-full max-h-52 overflow-y-auto rounded-md border bg-popover text-popover-foreground shadow-md animate-in fade-in-0 zoom-in-95"
                >
                    {!isLoading && options.length === 0 && (
                        <li className="px-3 py-2 text-sm text-muted-foreground select-none">No results found</li>
                    )}

                    {options.map((option, idx) => (
                        <li
                            key={option.id}
                            className={`px-3 py-2 text-sm cursor-pointer truncate ${
                                highlightedIndex === idx
                                    ? 'bg-accent text-accent-foreground'
                                    : 'hover:bg-accent hover:text-accent-foreground'
                            }`}
                            title={option.text}
                            onClick={() => handleSelect(option)}
                        >
                            {option.text}
                        </li>
                    ))}

                    {isLoading && (
                        <li className="px-3 py-2 text-sm text-muted-foreground select-none">Loading...</li>
                    )}
                </ul>
            )}
        </div>
    );
};

export default AutoComplete;

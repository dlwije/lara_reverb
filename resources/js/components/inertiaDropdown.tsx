// components/DropdownSelect.tsx

import { useMemo } from 'react';
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem
} from '@/components/ui/select'; // adjust to your path

interface Option {
    id: string;
    name: string;
}

interface DropdownSelectProps {
    options: Option[];
    selected: string;
    setSelected: (value: string) => void;
    placeholder?: string;
}

export const InertiaDropdown = ({
                                   options,
                                   selected,
                                   setSelected,
                                   placeholder = 'Select'
                               }: DropdownSelectProps) => {
    const selectedOption = useMemo(
        () => options.find((option) => option.id === selected),
        [options, selected]
    );

    return (
        <Select value={selected} onValueChange={setSelected}>
            <SelectTrigger className="h-10">
                <SelectValue>
                    {selectedOption ? (
                        <div className="flex items-center gap-2">
                            <span>{selectedOption.id}</span>
                            <span className="ms-2">{selectedOption.name}</span>
                        </div>
                    ) : (
                        <div className="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                            <span className="text-dark-800">{placeholder}</span>
                        </div>
                    )}
                </SelectValue>
            </SelectTrigger>

            <SelectContent>
                {options.length > 0 ? (
                    options.map((option) => (
                        <SelectItem key={option.id} value={option.id} className="text-foreground">
                            <div className="flex w-full items-center gap-2">
                                <span className="ms-2">{option.name}</span>
                            </div>
                        </SelectItem>
                    ))
                ) : (
                    <div className="px-4 py-2 text-sm text-muted-foreground select-none">
                        No results found
                    </div>
                )}
            </SelectContent>
        </Select>
    );
};

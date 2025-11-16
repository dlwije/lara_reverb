import { PaginationData } from '@/types/eCommerce/pagination';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import React from 'react';

interface PaginationBottomProps {
    pagination: PaginationData;
}
const PaginationBottom:React.FC<PaginationBottomProps> = ({ pagination }) => {

    // Function to generate visible page numbers with ellipsis
    const getVisiblePages = (): (number | string)[] => {
        if (!pagination) return [];

        const current = pagination.current_page;
        const last = pagination.last_page;
        const delta = 2; // Number of pages to show on each side of current page
        const range: number[] = [];
        const rangeWithDots: (number | string)[] = [];

        for (let i = 1; i <= last; i++) {
            if (
                i === 1 ||
                i === last ||
                (i >= current - delta && i <= current + delta)
            ) {
                range.push(i);
            }
        }

        let prev = 0;
        for (let i of range) {
            if (i - prev === 2) {
                rangeWithDots.push(prev + 1);
            } else if (i - prev !== 1) {
                rangeWithDots.push('...');
            }
            rangeWithDots.push(i);
            prev = i;
        }

        return rangeWithDots;
    };

    return (
        <div className='flex justify-center mt-8'>
            {/* Pagination */}
            <Pagination>
                <PaginationContent>
                    {/* Previous Button */}
                    <PaginationItem>
                        <PaginationPrevious
                            // size="button"
                            href={pagination.current_page > 1 ? `?page=${pagination.current_page - 1}` : '#'}
                            className={pagination.current_page <= 1 ? 'pointer-events-none opacity-50' : ''}
                        />
                    </PaginationItem>

                    {/* Page Numbers */}
                    {getVisiblePages().map((page, index) => {
                        if (page === '...') {
                            return (
                                <PaginationItem key={`ellipsis-${index}`}>
                                    <PaginationEllipsis />
                                </PaginationItem>
                            );
                        }

                        return (
                            <PaginationItem key={page} className={`hover:bg-gray-500/10`}>
                                <PaginationLink
                                    size="icon"
                                    href={`?page=${page}`}
                                    isActive={page === pagination.current_page}
                                >
                                    {page}
                                </PaginationLink>
                            </PaginationItem>
                        );
                    })}

                    {/* Next Button */}
                    <PaginationItem>
                        <PaginationNext
                            // size="icon"
                            href={pagination.current_page < pagination.last_page ? `?page=${pagination.current_page + 1}` : '#'}
                            className={pagination.current_page >= pagination.last_page ? 'pointer-events-none opacity-50' : ''}
                        />
                    </PaginationItem>
                </PaginationContent>
            </Pagination>
        </div>
    );
}

export default PaginationBottom;

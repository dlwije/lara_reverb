'use client';

import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { ChatUser } from '@/types/chat';
import { Download, Filter, MoreHorizontal, Plus } from 'lucide-react';
import { ComposeMessageDialog } from './compose-message-dialog';

interface TableActionsProps {
    currentUser: ChatUser;
    authToken: string;
    onMessageSent?: (conversationId: number, user: ChatUser) => void;
    onExport?: () => void;
    onFilter?: () => void;
    onBulkAction?: (action: string) => void;
    selectedCount?: number;
}

export function TableActions({ currentUser, authToken, onMessageSent, onExport, onFilter, onBulkAction, selectedCount = 0 }: TableActionsProps) {
    return (
        <div className="mr-4 flex w-full items-center gap-3 py-4">
            <div className="relative min-w-0 flex-1">
                {/* This empty div takes up the left space, similar to the search input in the table */}
                {selectedCount > 0 && (
                    <div className="flex items-center gap-2">
                        <span className="text-sm text-muted-foreground">
                            {selectedCount} item{selectedCount > 1 ? 's' : ''} selected
                        </span>
                        <Button variant="outline" size="sm" onClick={() => onBulkAction?.('delete')} className="text-red-600 hover:text-red-700">
                            Delete Selected
                        </Button>
                    </div>
                )}
            </div>

            {/* Action Buttons */}
            <div className="flex items-center gap-2 pr-2">
                {/* Export Button */}
                {onExport && (
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={onExport}
                        className="inline-flex shrink-0 items-center gap-1.5 bg-transparent whitespace-nowrap"
                    >
                        <Download className="h-4 w-4 shrink-0" />
                        <span>Export</span>
                    </Button>
                )}

                {/* Filter Button */}
                {onFilter && (
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={onFilter}
                        className="inline-flex shrink-0 items-center gap-1.5 bg-transparent whitespace-nowrap"
                    >
                        <Filter className="h-4 w-4 shrink-0" />
                        <span>Filter</span>
                    </Button>
                )}

                {/* Compose Message Button */}
                <ComposeMessageDialog
                    currentUser={currentUser}
                    authToken={authToken}
                    onMessageSent={onMessageSent}
                    trigger={
                        <Button variant="outline" className="inline-flex shrink-0 items-center gap-1.5 bg-transparent whitespace-nowrap">
                            <Plus className="h-4 w-4 shrink-0" />
                            <span>Compose Message</span>
                        </Button>
                    }
                />

                {/* More Actions Dropdown */}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="outline" size="sm" className="inline-flex shrink-0 items-center gap-1.5 bg-transparent whitespace-nowrap">
                            <MoreHorizontal className="h-4 w-4 shrink-0" />
                            <span>More</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={() => onBulkAction?.('refresh')}>Refresh Data</DropdownMenuItem>
                        <DropdownMenuItem onClick={() => onBulkAction?.('import')}>Import Users</DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={() => onBulkAction?.('settings')}>Table Settings</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    );
}

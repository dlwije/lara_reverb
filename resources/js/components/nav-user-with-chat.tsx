"use client"

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from "@/components/ui/sidebar"
import { Badge } from "@/components/ui/badge"
import type { SharedData } from "@/types"
import { Link, router, usePage } from "@inertiajs/react"
import { BadgeCheck, Bell, ChevronsUpDown, CreditCard, LogOut, Sparkles } from "lucide-react"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { useMobileNavigation } from "@/hooks/use-mobile-navigation"
import { GlobalChat } from "./global-chat"
import { useConversations } from "@/hooks/use-conversations"
import { useState } from "react"

export function NavUserWithChat() {
    const { auth } = usePage<SharedData>().props
    const { isMobile } = useSidebar()
    const cleanup = useMobileNavigation()
    const [chatOpen, setChatOpen] = useState(false)

    // Get unread count for notifications badge
    const { getTotalUnreadCount } = useConversations(auth.user?.id || 0)
    const totalUnreadCount = getTotalUnreadCount()

    const handleLogout = () => {
        // Clear frontend storage
        localStorage.removeItem("acc_token")
        cleanup()
        router.flushAll()
    }

    const openChat = () => {
        setChatOpen(true)
    }

    // Custom trigger for the chat - hidden button that we'll trigger programmatically
    const chatTrigger = (
        <button onClick={() => setChatOpen(true)} className="hidden" aria-hidden="true">
            Open Chat
        </button>
    )

    return (
        <>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <SidebarMenuButton
                                size="lg"
                                className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                            >
                                <Avatar className="h-8 w-8 rounded-lg">
                                    <AvatarImage src={auth.user?.avatar || "/placeholder.svg"} alt={auth.user?.name} />
                                    <AvatarFallback className="rounded-lg">CN</AvatarFallback>
                                </Avatar>
                                <div className="grid flex-1 text-left text-sm leading-tight">
                                    <span className="truncate font-medium">{auth.user?.name}</span>
                                    <span className="truncate text-xs">{auth.user?.email}</span>
                                </div>
                                <ChevronsUpDown className="ml-auto size-4" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                            side={isMobile ? "bottom" : "right"}
                            align="end"
                            sideOffset={4}
                        >
                            <DropdownMenuLabel className="p-0 font-normal">
                                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                    <Avatar className="h-8 w-8 rounded-lg">
                                        <AvatarImage src={auth.user?.avatar || "/placeholder.svg"} alt={auth.user?.name} />
                                        <AvatarFallback className="rounded-lg">CN</AvatarFallback>
                                    </Avatar>
                                    <div className="grid flex-1 text-left text-sm leading-tight">
                                        <span className="truncate font-medium">{auth.user?.name}</span>
                                        <span className="truncate text-xs">{auth.user?.email}</span>
                                    </div>
                                </div>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuGroup>
                                <DropdownMenuItem>
                                    <Sparkles />
                                    Upgrade to Pro
                                </DropdownMenuItem>
                            </DropdownMenuGroup>
                            <DropdownMenuSeparator />
                            <DropdownMenuGroup>
                                <DropdownMenuItem className="cursor-pointer flex items-center w-full">
                                    <BadgeCheck />
                                    <Link
                                        href={route("profile.edit")}
                                        as="button"
                                        onClick={cleanup}
                                        className="cursor-pointer flex items-center w-full"
                                    >
                                        Account
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem>
                                    <CreditCard />
                                    Billing
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={openChat}
                                    className="cursor-pointer flex items-center justify-between w-full"
                                >
                                    <div className="flex items-center gap-2">
                                        <Bell />
                                        Notifications
                                    </div>
                                    {totalUnreadCount > 0 && (
                                        <Badge variant="destructive" className="h-5 w-5 flex items-center justify-center p-0 text-xs">
                                            {totalUnreadCount > 9 ? "9+" : totalUnreadCount}
                                        </Badge>
                                    )}
                                </DropdownMenuItem>
                            </DropdownMenuGroup>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem className="cursor-pointer flex items-center w-full">
                                <LogOut />
                                <Link
                                    method="post"
                                    href={route("logout")}
                                    onClick={handleLogout}
                                    className="cursor-pointer flex items-center w-full"
                                >
                                    Log out
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>

            {/* Global Chat Component */}
            <GlobalChat
                currentUser={{
                    id: auth.user?.id || 0,
                    name: auth.user?.name || "",
                    email: auth.user?.email || "",
                    avatar: auth.user?.avatar,
                }}
                authToken={auth.accessToken || ""}
                trigger={chatTrigger}
                open={chatOpen}
                onOpenChange={setChatOpen}
            />
        </>
    )
}

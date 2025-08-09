import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarRail
} from '@/components/ui/sidebar';
import { type NavItem, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
// import { BookOpen, Folder, LayoutGrid, User2 } from 'lucide-react';

import {
    AudioWaveform,
    BookOpen,
    Bot,
    Frame,
    GalleryVerticalEnd,
    PieChart,
    Settings2,
    SquareTerminal,
    Command,
    Map,
    LayoutDashboard as IconDashboard, User2
} from 'lucide-react';
import { NavProjects } from '@/components/nav-projects';
import { TeamSwitcher } from '@/components/team-switcher';
import NotificationPopover from '@/components/NotificationPopover';
import { useEffect, useState } from 'react';
import useEcho from '@/lib/echo';
import { NavItems } from '@/types/nav-list';

interface User {
    id: number
    name: string
    email: string
    avatar?: string | null
    phone: string | null
    user_type: string
    updated_at: string
    role: string
    DT_RowIndex: number
}

// This is sample data.
const data:NavItems = {
    teams: [
        {
            name: "Acme Inc",
            logo: GalleryVerticalEnd,
            plan: "Enterprise",
        },
        {
            name: "Acme Corp.",
            logo: AudioWaveform,
            plan: "Startup",
        },
        {
            name: "Evil Corp.",
            logo: Command,
            plan: "Free",
        },
    ],
    navMain: [
        {
            title: "Dashboard",
            url: "dashboard",
            icon: IconDashboard,
        },
        {
            title: "Users",
            url: "#",
            icon: User2,
            isActive: true,
            items: [
                {
                    title: "List",
                    url: "auth.users.list",
                }
            ],
        },
        {
            title: "Models",
            url: "",
            icon: Bot,
            items: [
                {
                    title: "Genesis",
                    url: "",
                },
                {
                    title: "Explorer",
                    url: "",
                },
                {
                    title: "Quantum",
                    url: "",
                },
            ],
        },
        {
            title: "Documentation",
            url: "",
            icon: BookOpen,
            items: [
                {
                    title: "Introduction",
                    url: "",
                },
                {
                    title: "Get Started",
                    url: "",
                },
                {
                    title: "Tutorials",
                    url: "",
                },
                {
                    title: "Changelog",
                    url: "",
                },
            ],
        },
        {
            title: "Settings",
            url: "",
            icon: Settings2,
            items: [
                {
                    title: "General",
                    url: "",
                },
                {
                    title: "Team",
                    url: "",
                },
                {
                    title: "Billing",
                    url: "",
                },
                {
                    title: "Limits",
                    url: "",
                },
            ],
        },
    ],
    projects: [
        {
            name: "Design Engineering",
            url: "",
            icon: Frame,
        },
        {
            name: "Sales & Marketing",
            url: "",
            icon: PieChart,
        },
        {
            name: "Travel",
            url: "",
            icon: Map,
        },
    ],
}
export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {

    const [onlineUsers, setOnlineUsers] = useState<User[]>([]);
    const echo = useEcho()
    const { auth } = usePage<SharedData>().props;

    useEffect(() => {
        const existing = localStorage.getItem('acc_token');
        if (auth?.accessToken && !existing) {
            localStorage.setItem('acc_token', auth.accessToken);
            console.log('Token saved to localStorage');
        }
    }, [auth]);

    useEffect(() => {
        // Here we are going to listen for real-time events.
        if (echo) {
            // 1. Join the channel ONCE and store the instance.
            const channel = echo.join('online');
            console.log('Attempting to join online presence channel:', channel);

            // 2. Chain all event listeners to this single instance.
            channel
                .subscribed(() => {
                    // This confirms the WebSocket connection AND authorization were successful.
                    console.log(
                        '✅ Successfully subscribed to the "online" presence channel on app-sidebar!'
                    );
                })
                .here((members: User[]) => {
                    // This is called immediately after a successful subscription.
                    console.log('👥 Users currently here on app-sidebar:', members);
                    const withUnread = members.map((user) => ({
                        ...user,
                        unread: Math.random() > 0.5
                    }));
                    setOnlineUsers(withUnread);
                })
                .joining((user: User) => {
                    console.log('➕ User joining on app-sidebar:', user);
                    setOnlineUsers((prev) => [...prev, { ...user, unread: true }]);
                })
                .leaving((user: User) => {
                    console.log('➖ User leaving on app-sidebar:', user);
                    setOnlineUsers((prev) => prev.filter((u) => u.id !== user.id));
                })
                .error((error: any) => {
                    // This is crucial for debugging auth issues!
                    console.error('Subscription Error on app-sidebar:', error);
                });

            // 3. Cleanup: Leave the channel when the component unmounts.
            return () => {
                console.log('Leaving "online" channel on app-sidebar.');
                echo?.leave('online');
            };
        }
    }, [echo])

    return (
        <Sidebar collapsible="icon" {...props}>
            <SidebarHeader>
                <TeamSwitcher teams={data.teams} />
            </SidebarHeader>
            <SidebarContent>
                <NavMain items={data.navMain} />
                <NavProjects projects={data.projects} />
            </SidebarContent>
            <SidebarFooter>
                <NotificationPopover />
                <NavUser />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    )
}

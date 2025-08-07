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
import { useEffect } from 'react';

// const mainNavItems: NavItem[] = [
//     {
//         title: 'Dashboard',
//         href: '/dashboard',
//         icon: LayoutGrid,
//     },
//     {
//         title: 'User Management',
//         href: '/user',
//         icon: User2,
//         children: [
//             {
//                 title: 'User List',
//                 href: '/user/list',
//             },
//             {
//                 title: 'Roles',
//                 href: '/user/roles',
//             },
//         ],
//     },
// ];
//
// const footerNavItems: NavItem[] = [
//     {
//         title: 'Repository',
//         href: 'https://github.com/laravel/react-starter-kit',
//         icon: Folder,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits#react',
//         icon: BookOpen,
//     },
// ];
// This is sample data.
const data = {
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
            url: "/dashboard",
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
                    url: "auth/users/list",
                }
            ],
        },
        {
            title: "Models",
            url: "#",
            icon: Bot,
            items: [
                {
                    title: "Genesis",
                    url: "#",
                },
                {
                    title: "Explorer",
                    url: "#",
                },
                {
                    title: "Quantum",
                    url: "#",
                },
            ],
        },
        {
            title: "Documentation",
            url: "#",
            icon: BookOpen,
            items: [
                {
                    title: "Introduction",
                    url: "#",
                },
                {
                    title: "Get Started",
                    url: "#",
                },
                {
                    title: "Tutorials",
                    url: "#",
                },
                {
                    title: "Changelog",
                    url: "#",
                },
            ],
        },
        {
            title: "Settings",
            url: "#",
            icon: Settings2,
            items: [
                {
                    title: "General",
                    url: "#",
                },
                {
                    title: "Team",
                    url: "#",
                },
                {
                    title: "Billing",
                    url: "#",
                },
                {
                    title: "Limits",
                    url: "#",
                },
            ],
        },
    ],
    projects: [
        {
            name: "Design Engineering",
            url: "#",
            icon: Frame,
        },
        {
            name: "Sales & Marketing",
            url: "#",
            icon: PieChart,
        },
        {
            name: "Travel",
            url: "#",
            icon: Map,
        },
    ],
}
export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {

    const { auth } = usePage<SharedData>().props;

    // console.log(auth);
    useEffect(() => {
        const existing = localStorage.getItem('acc_token');
        if (auth?.accessToken && !existing) {
            localStorage.setItem('acc_token', auth.accessToken);
            console.log('Token saved to localStorage');
        }
    }, [auth]);

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

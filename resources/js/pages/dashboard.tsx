import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, SharedData, type User } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import echo from '@/lib/echo';
import { useEffect, useState } from 'react';
import type { PageProps } from '@/types' // wherever your types file is

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// interface PageProps {
//     userData: SharedData,
//     auth: User;
//     [key: string]: unknown; // ✅ Required to satisfy Inertia's constraint
// }

export default function Dashboard() {

    const [privateMessage, setPrivateMessage] = useState<string | null>(null);
    const { auth } = usePage<SharedData>().props;
    // console.log(sharedData);
    const userId = auth.user.id;
    console.log(auth.user);

    useEffect(() => {
        // Private Channel Listener
        const privateChannel = echo.private(`chat.${userId}`);
        privateChannel.listen(".PrivateMessageEvent", (data: { message: string }) => {
            console.log("🔒 Private event received:", data);
            setPrivateMessage(data.message);
        });

        return () => {
            privateChannel.stopListening(".PrivateMessageEvent");
        };
    }, [userId]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </AppLayout>
    );
}

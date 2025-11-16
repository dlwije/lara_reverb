import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';
import { Toaster } from 'sonner';
import { NotificationProvider } from '@/contexts/NotificationContext';
import NotificationListener from '@/components/NotificationListener';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => (
    <NotificationProvider>
        <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
            <NotificationListener />
            {children}
            <Toaster position="top-right" />
        </AppLayoutTemplate>
    </NotificationProvider>
);

'use client'

import { HomeIcon, ShieldCheckIcon, StoreIcon, TicketPercentIcon } from "lucide-react"
import { Link, usePage } from '@inertiajs/react';
import { assets } from '../../../../../public/e-commerce/assets/assets.js';
import React from 'react';

const CustomerSidebar = () => {
    const { url } = usePage();

    const sidebarLinks = [
        { name: 'Dashboard', href: '/customer/dashboard', icon: HomeIcon },
        { name: 'Addresses', href: '/customer/addresses', icon: ShieldCheckIcon },
        { name: 'Order', href: '/customer/orders', icon: StoreIcon },
        { name: 'Gift Cards', href: '/customer/gift-cards', icon: TicketPercentIcon  },
    ]

    const isLinkActive = (linkHref) => {
        // Exact match or starts with (for nested routes)
        return url === linkHref || url.startsWith(linkHref + '/');
    }

    return (
        <div className="inline-flex h-full flex-col gap-5 border-r bg-slate-100 border-slate-200 dark:border-slate-700 sm:min-w-60 bg-white dark:bg-slate-900">
            <div className="flex flex-col gap-3 justify-center items-center pt-8 max-sm:hidden">
                <img src={assets.gs_logo} alt={'dsstack'} className="h-8 w-8 rounded-lg object-cover" width={80} height={80} />
                <p className="text-slate-700 dark:text-slate-300">Hi, GreatStack</p>
            </div>

            <div className="max-sm:mt-6">
                {sidebarLinks.map((link, index) => {
                    const isActive = isLinkActive(link.href);

                    return (
                        <Link
                            key={index}
                            href={link.href}
                            className={`relative flex items-center gap-3 p-2.5 transition ${
                                isActive
                                    ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-medium'
                                    : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                            }`}
                        >
                            <link.icon
                                size={18}
                                className={`sm:ml-5 ${isActive ? 'text-slate-900 dark:text-white' : ''}`}
                            />
                            <p className="max-sm:hidden">{link.name}</p>
                            {isActive && (
                                <span className="absolute bg-green-500 dark:bg-green-400 right-0 top-1.5 bottom-1.5 w-1 sm:w-1.5 rounded-l"></span>
                            )}
                        </Link>
                    );
                })}
            </div>
        </div>
    )
}

export default CustomerSidebar

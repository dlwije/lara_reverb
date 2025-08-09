// components/NotificationPopover.tsx (for app-sidebar)
import { useCallback, useEffect } from 'react';
import { Popover } from '@headlessui/react';
import { motion } from 'framer-motion'; // Removed useAnimation as it's now from context
import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';
import apiClient from '@/lib/apiClient';
import { useNotification } from '@/contexts/NotificationContext';

export default function NotificationPopover() {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;

    // Get state and actions from context, including controls
    const { unreadMessages, messages, markAsRead, notificationControls } = useNotification();

    // Mark messages as read when popover is opened
    const handleMarkAsRead = useCallback(() => {
        if (unreadMessages > 0 && user?.id) {
            console.log('Marking messages as read');
            apiClient
                .post('/api/v1/mark-messages-read', {
                    user_id: user.id,
                })
                .then(() => {
                    console.log('Messages marked as read');
                    markAsRead(); // Use context action to reset count
                })
                .catch(error => {
                    console.error('Failed to mark messages as read:', error);
                });
        }
    }, [unreadMessages, user?.id, markAsRead]);

    return (
        <Popover className="relative z-50">
            <Popover.Button
                className="relative rounded-full bg-blue-600 p-1 text-white hover:bg-blue-700 focus:outline-none"
                onClick={handleMarkAsRead}
            >
                {/* Apply controls directly to motion.div */}
                <motion.div animate={notificationControls}>
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" strokeWidth="2"
                         viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round"
                              d="M15 17h5l-1.405-1.405M19 13V9a7 7 0 10-14 0v4l-1.405 1.405M5 17h5m0 0v1a3 3 0 006 0v-1m-6 0h6"/>
                    </svg>
                    {unreadMessages > 0 && (
                        <motion.span
                            initial={{ scale: 0 }}
                            animate={{ scale: 1 }}
                            className="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-600 rounded-full"
                        >
                            {unreadMessages > 99 ? '99+' : unreadMessages}
                        </motion.span>
                    )}
                </motion.div>
            </Popover.Button>

            <Popover.Panel
                as={motion.div}
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: 20 }}
                className="absolute bottom-full rounded-md right-0 mb-3 w-80 translate-x-24 bg-white shadow-lg ring-1 ring-black ring-opacity-5"
            >
                <div className="divide-y divide-gray-200 p-3 max-h-96 overflow-y-auto">
                    <div className="pb-2 mb-2 border-b">
                        <h3 className="text-sm font-medium text-gray-900">
                            Messages ({unreadMessages} unread)
                        </h3>
                    </div>

                    {messages.length === 0 ? (
                        <div className="text-sm text-gray-500 py-4 text-center">
                            No messages
                        </div>
                    ) : (
                        messages.map((msg) => (
                            <motion.div
                                key={msg.id}
                                initial={{ opacity: 0, y: -10 }}
                                animate={{ opacity: 1, y: 0 }}
                                className="flex items-start gap-3 py-2"
                            >
                                <img
                                    src={`https://ui-avatars.com/api/?name=${msg.from.name}&size=64`}
                                    alt={msg.from.name}
                                    className="w-8 h-8 rounded-full"
                                />
                                <div className="flex flex-col flex-1">
                                    <p className="text-sm text-gray-800 line-clamp-2">
                                        {msg.message}
                                    </p>
                                    <span className="text-xs text-gray-400 mt-1">
                    {msg.from.name}
                  </span>
                                </div>
                            </motion.div>
                        ))
                    )}
                </div>
            </Popover.Panel>
        </Popover>
    );
}

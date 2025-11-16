import { useCallback, useState } from 'react';

export function useDisclosure(initial = false) {
    const [isOpen, setIsOpen] = useState(initial);

    const onOpen = useCallback(() => setIsOpen(true), []);
    const onClose = useCallback(() => setIsOpen(false), []);
    const onOpenChange = useCallback(() => setIsOpen(prev => !prev), []);

    return {
        isOpen,
        onOpen,
        onClose,
        onOpenChange,
    };
}

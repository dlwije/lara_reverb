"use client"

import type { ReactNode } from "react"
import { I18nextProvider } from "react-i18next"
import { useEffect } from "react"
import i18n from "@/lib/i18n"

interface I18nProviderProps {
    children: ReactNode
    locale?: string
}

export function I18nProvider({ children, locale }: I18nProviderProps) {
    useEffect(() => {
        if (locale && i18n.language !== locale) {
            i18n.changeLanguage(locale)
        }
    }, [locale])

    useEffect(() => {
        const handleNavigate = (event: any) => {
            const newLocale = event.detail.page.props?.locale
            if (newLocale && i18n.language !== newLocale) {
                i18n.changeLanguage(newLocale)
            }
        }

        document.addEventListener("inertia:success", handleNavigate)
        return () => document.removeEventListener("inertia:success", handleNavigate)
    }, [])

    return <I18nextProvider i18n={i18n}>{children}</I18nextProvider>
}

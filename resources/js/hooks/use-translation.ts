import { useTranslation as useReactTranslation } from "react-i18next"
import { router, usePage } from '@inertiajs/react';

export type Language = {
    value: string
    label: string
    flag: string
}

interface SharedProps {
    language: string
    languages: Language[]
}

export function useTranslation() {
    const { t, i18n } = useReactTranslation()
    const { props } = usePage<{ props: SharedProps }>()

    const serverLocale = props.language || "en"

    const changeLanguage = (locale: string) => {
        // Update i18n immediately for UI
        i18n.changeLanguage(locale)

        // Make Inertia request to sync with server
        router.post(
            "/locale",
            { locale },
            { preserveState: true, preserveScroll: true,
                only: [], // Don't reload any props
            }
        )
    }

    return {
        t,
        locale: i18n.language,
        changeLanguage,
        languages: props.languages ?? [],
    }
}

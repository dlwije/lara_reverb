import { useTranslation as useReactTranslation } from "react-i18next"
import { router } from "@inertiajs/react"

export function useTranslation() {
    const { t, i18n } = useReactTranslation()

    const changeLanguage = (locale: string) => {
        // Update i18n immediately for UI
        i18n.changeLanguage(locale)

        // Make Inertia request to sync with server
        router.post(
            "/locale",
            { locale },
            {
                preserveState: true,
                preserveScroll: true,
                only: [], // Don't reload any props
            },
        )
    }

    return {
        t,
        locale: i18n.language,
        changeLanguage,
    }
}

// For components that need the $t equivalent
export function useT() {
    const { t } = useReactTranslation()
    return t
}

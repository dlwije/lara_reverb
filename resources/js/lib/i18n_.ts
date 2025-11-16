import i18n from "i18next"
import { initReactI18next } from "react-i18next"
import en from "@lang/en.json";
import es from "@lang/es.json";
import fr from "@lang/fr.json";
import languages from "@lang/languages.json"

const messages = { en }

export const LANGUAGES = languages.available
export const SUPPORT_LOCALES = languages.available.map((l: any) => l.value).filter((l: string) => l !== "en")

const getInitialLocale = () => {
    if (typeof window === "undefined") return "es"

    // Check for Inertia page props first
    if ((window as any).page?.props?.locale) {
        return (window as any).page.props.locale
    }

    // Fallback to window.Locale or default
    return (window as any).Locale || "es"
}

// Initialize i18next
i18n.use(initReactI18next).init({
    resources: messages,
    lng: getInitialLocale(),
    fallbackLng: "es",

    // Disable warnings
    debug: false,
    saveMissing: true,

    interpolation: {
        escapeValue: false, // React already escapes values
    },

    // Handle missing translations
    missingKeyHandler: (lng, ns, key) => {
        console.log(`Add to ${lng}.json => "${key}": "${key}",`)
    },

    // React i18next options
    react: {
        useSuspense: false,
    },
})

export default i18n

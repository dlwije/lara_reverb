import { GlobeIcon } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger
} from '@/components/ui/select';

export default function LanguageSelector({ selectId = 'lang' }) {
    const { locale, changeLanguage, languages } = useTranslation();

    const currentLang = languages.find((l) => l.value === locale);

    return (
        <Select defaultValue={locale} onValueChange={changeLanguage}>
            <SelectTrigger
                id={`language-${selectId}`}
                className="[&>svg]:text-muted-foreground/80 hover:bg-accent hover:text-accent-foreground h-8 border-none px-2 shadow-none [&>svg]:shrink-0"
                aria-label="Select language"
            >
                <GlobeIcon size={16} aria-hidden={true} />
                {currentLang && (
                    <span className="ml-1 flex items-center gap-2">
            <span className="me-1">{getFlagEmoji(currentLang.flag)}</span>
            <span className="hidden sm:inline-flex">{currentLang.value}</span>
          </span>
                )}
            </SelectTrigger>

            <SelectContent>
                {languages.map((lang) => (
                    <SelectItem key={lang.value} value={lang.value}>
            <span className="flex items-center gap-2">
              <span className="me-2">{getFlagEmoji(lang.flag)}</span>
              <span className="truncate">{lang.label}</span>
            </span>
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function getFlagEmoji(code: string) {
    return code
        .toUpperCase()
        .replace(/./g, (char) =>
            String.fromCodePoint(127397 + char.charCodeAt(0))
        );
}

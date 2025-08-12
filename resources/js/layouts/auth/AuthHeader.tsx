import { cn } from '@/lib/utils';

interface AuthHeaderProps {
    title: string;
    description?: string;
    className?: string;
}
export default function AuthHeader({ title, description, className }: AuthHeaderProps) {
    return (
        <div className={cn("flex flex-col items-center text-center", className)}>
            <h1 className="text-2xl font-bold">{title}</h1>
            {description && <p className="text-muted-foreground text-balance">{description}</p>}
        </div>
    )
}

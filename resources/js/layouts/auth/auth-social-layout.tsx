import { PropsWithChildren } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { GalleryVerticalEnd } from 'lucide-react';
import loginBgPlaceholder from '/public/images/auth/placeholder.svg'

interface AuthLayoutProps {
    name?: string;
    title?: string;
    description?: string;
    className?: string;
}
export default function AuthSocialLayout({ children, title, description, className }: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div className="grid min-h-svh lg:grid-cols-2">
            <div className="flex flex-col gap-4 p-6 md:p-10">
                {/* Logo/Brand */}
                <div className="">
                    <a href="#" className="flex items-center gap-2 font-medium">
                        <div className="bg-primary text-primary-foreground flex size-6 items-center justify-center rounded-md">
                            <GalleryVerticalEnd className="size-4" />
                        </div>
                        { title }
                    </a>
                </div>
                {/* Centered Login Form */}
                <div className="flex flex-1 items-center justify-center">
                    <div className="w-full max-w-md">
                        {" "}
                        {/* Changed from max-w-xs to max-w-md */}
                        <div className={cn("flex flex-col gap-6", className)}>
                            <Card className="overflow-hidden">
                                <CardContent className="p-0">
                                    { children }
                                    <div className="bg-muted relative hidden md:block">
                                        <img
                                            src={loginBgPlaceholder || "/images/auth/placeholder.svg"}
                                            alt="Image"
                                            className="absolute inset-0 h-full w-full object-cover dark:brightness-[0.2] dark:grayscale"
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                            <div className="text-muted-foreground *:[a]:hover:text-primary text-center text-xs text-balance *:[a]:underline *:[a]:underline-offset-4">
                                By clicking continue, you agree to our <a href="#">Terms of Service</a>{" "}
                                and <a href="#">Privacy Policy</a>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}

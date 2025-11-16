import { useState } from 'react';
import axios from 'axios';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import apiClient from '@/lib/apiClient';

export default function TwoFactorAuth() {
    const [enabled, setEnabled] = useState(false);
    const [qrCode, setQrCode] = useState<string | null>(null);
    const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);

    const enable2FA = async () => {
        const { data } = await apiClient.post('/api/v1/user/two-factor/enable');
        setEnabled(true);
        setQrCode(data.svg);
        setRecoveryCodes(data.codes);
    };

    const disable2FA = async () => {
        await apiClient.post('/api/v1/user/two-factor/disable');
        setEnabled(false);
        setQrCode(null);
        setRecoveryCodes([]);
    };

    return (
        <div className="space-y-6">
            <HeadingSmall title="Two-Factor Authentication" description="Add additional security to your account." />

            {!enabled ? (
                <Button onClick={enable2FA}>Enable 2FA</Button>
            ) : (
                <div className="space-y-4">
                    {qrCode && (
                        <div>
                            <p className="mb-2 text-sm text-neutral-600">
                                Scan this QR code in your authenticator app.
                            </p>
                            <div dangerouslySetInnerHTML={{ __html: qrCode }} />
                        </div>
                    )}

                    {recoveryCodes.length > 0 && (
                        <div>
                            <p className="mb-2 text-sm text-neutral-600">Recovery Codes:</p>
                            <ul className="grid gap-2 text-sm font-mono p-3 rounded bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100">
                                {recoveryCodes.map((code, idx) => (
                                    <li key={idx}>{code}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <Button variant="destructive" onClick={disable2FA}>
                        Disable 2FA
                    </Button>
                </div>
            )}
        </div>
    );
}

import { z } from 'zod';

// Luhn algorithm for card number validation
function luhnCheck(cardNumber: string): boolean {
    let sum = 0;
    let shouldDouble = false;

    for (let i = cardNumber.length - 1; i >= 0; i--) {
        let digit = parseInt(cardNumber.charAt(i));

        if (shouldDouble) {
            digit *= 2;
            if (digit > 9) digit -= 9;
        }

        sum += digit;
        shouldDouble = !shouldDouble;
    }

    return sum % 10 === 0;
}

export const cardFormSchema = z.object({
    cardholder_name: z.string().min(1, 'Cardholder name is required'),

    card_number: z
        .string()
        .regex(/^\d{13,19}$/, 'Card number must be 13-19 digits')
        .refine(luhnCheck, { message: 'Invalid card number' }),

    expiry_date: z
        .string()
        .regex(/^(0[1-9]|1[0-2])\/\d{2}$/, 'Expiry must be in MM/YY format')
        .refine((val) => {
            const [mm, yy] = val.split('/');
            const month = parseInt(mm, 10);
            const year = parseInt(yy, 10) + 2000;

            const now = new Date();
            const expiry = new Date(year, month - 1, 1);

            // Expiry must be >= current month
            return expiry >= new Date(now.getFullYear(), now.getMonth(), 1);
        }, { message: 'Card has expired' }),

    cvv: z
        .string()
        .regex(/^\d{3,4}$/, 'Invalid CVV'),
});

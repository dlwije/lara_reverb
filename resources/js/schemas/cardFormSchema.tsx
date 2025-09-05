import { z } from 'zod';

// Luhn algorithm check for card numbers
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
    expiry_month: z
        .string()
        .regex(/^(0[1-9]|1[0-2])$/, 'Invalid month'),
    expiry_year: z
        .string()
        .regex(/^\d{2}$/, 'Year must be 2 digits')
        .refine((val) => {
            const year = parseInt(val, 10) + 2000;
            const now = new Date();
            return year >= now.getFullYear();
        }, { message: 'Card has expired' }),
    cvv: z
        .string()
        .regex(/^\d{3,4}$/, 'Invalid CVV'),
})

import { z } from 'zod';

export const productFormSchema = z.object({
    name: z
        .string()
        .min(1, { message: "Product name is required." })
        .max(100, { message: "Product name must not be longer than 100 characters." }),
    code: z
        .string()
        .min(1, { message: "Product code is required." })
        .max(50, { message: "Product code must not be longer than 50 characters." }),
    price: z.number().min(0, { message: "Price must be a positive number." }).optional(),
    cost: z.number().min(0, { message: "Cost must be a positive number." }).optional(),
    weight: z.number().min(0, { message: "Weight must be a positive number." }).optional(),
    alert_quantity: z.number().min(0, { message: "Alert quantity must be a positive number." }).optional(),
    video_url: z.string().url({ message: "Please enter a valid URL." }).optional().or(z.literal("")),
    details: z.string().max(1000, { message: "Details must not be longer than 1000 characters." }).optional(),
})

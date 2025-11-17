import { usePage } from '@inertiajs/react';
import { AxiosInstance } from 'axios';

// Types
interface Settings {
    fraction?: number;
    quantity_fraction?: number;
    default_locale?: string;
    date_format?: string;
    weight_unit?: string;
    dimension_unit?: string;
    hour12?: number;
    timezone?: string;
}

interface PageProps {
    settings?: Settings;
    default_currency?: {
        code: string;
    };
    taxes?: Tax[];
    is_impersonating?: boolean;
    user?: User;
    auth: {
        user: User;
    };
}

interface User {
    roles: Role[];
    all_permissions: string[];
}

interface Role {
    name: string;
}

interface Address {
    lot_no?: string;
    street?: string;
    address_line_1?: string;
    address_line_2?: string;
    city?: string;
    postal_code?: string;
    state?: {
        name: string;
    };
    country?: {
        name: string;
    };
}

interface Tax {
    id: number;
    rate: number;
    amount?: number;
}

interface Product {
    id: number;
    code: string;
    name: string;
    cost: number;
    taxes: Tax[];
    tax_included: boolean;
    unit?: {
        id: number;
        subunits: Subunit[];
    };
    valid_promotions?: Promotion[];
    category?: {
        valid_promotions?: Promotion[];
    };
}

interface Subunit {
    id: number;
    operator: string;
    operation_value: number;
}

interface Promotion {
    id: number;
    type: string;
    discount: number | string;
    quantity_to_buy?: number;
    product_id_to_get?: number;
    quantity_to_get?: number;
    amount_to_spend?: number;
}

interface Item {
    id?: number | null;
    code?: string;
    name?: string;
    price: number;
    quantity: number;
    cost: number;
    taxes: number[];
    tax_included: boolean;
    product_id?: number;
    promo_product_id?: number;
    product?: Product;
    promotion_id?: number | null;
    discount?: string | null;
    discount_amount?: number;
    total_discount_amount?: number;
    tax_amount?: number;
    before_discount_tax_amount?: number;
    total_tax_amount?: number;
    unit_price?: number;
    net_price?: number;
    subtotal?: number;
    total?: number;
    applied_taxes?: Tax[];
    variations?: Item[];
    price_before_discount?: number;
}

interface Form {
    items: Item[];
    calculate_on: string;
    grand_total?: number;
}

interface ExtraField {
    name: string;
    type: string;
}

interface NumberFormatOptions extends Intl.NumberFormatOptions {
    currency?: string;
    currencyDisplay?: string;
    unit?: string;
    unitDisplay?: string;
}

// Hook to get page props
const usePageProps = (): PageProps => {
    const page = usePage<PageProps>();
    return page.props;
};

// Capitalize function
export const capitalize = (str: string): string => {
    if (!str) return '';

    const res = str.split(' ');
    for (let i = 0; i < res.length; i++) {
        if (res[i].length > 0) {
            res[i] = res[i][0].toUpperCase() + res[i].substring(1);
        }
    }
    return res.join(' ');
};

// Address formatting
export const formatAddress = (row: Address | null): string => {
    if (!row) return '';

    return `${row.lot_no || ''} ${row.street || ''} ${row.address_line_1 || ''} ${row.address_line_2 || ''} ${row.city || ''} ${
        row.postal_code || ''
    } ${row.state?.name || ''} ${row.country?.name || ''}`.trim().replace(/\s+/g, ' ');
};

// Decimal formatting
export const formatDecimal = (amount: number | string, format: boolean = false): string | number => {
    const settings = usePageProps().settings;
    const fraction = settings?.fraction || 0;
    const formatted = numberFormat(amount, fraction);

    return format ? Number(formatted) : formatted;
};

export const formatDecimalQty = (amount: number | string, format: boolean = false): string | number => {
    const settings = usePageProps().settings;
    const quantityFraction = settings?.quantity_fraction || 0;
    const formatted = numberFormat(amount, quantityFraction);

    return format ? Number(formatted) : formatted;
};

// Meta formatting
export const formatMeta = (meta: Record<string, any>): string => {
    const sortedMeta = Object.keys(meta)
        .sort()
        .reduce((result, key) => {
            result[key] = meta[key];
            return result;
        }, {} as Record<string, any>);

    return Object.keys(sortedMeta)
        .map(key => `${key}: ${sortedMeta[key]}`)
        .join(', ');
};

// Number formatting
export const formatNumber = (
    amount: number | string,
    locale?: string,
    options?: NumberFormatOptions
): string => {
    if (!amount) amount = 0;

    let formatted = parseFloat(amount as string);
    const settings = usePageProps().settings;

    if (!locale || (locale.length !== 2 && locale.length !== 5)) {
        locale = settings?.default_locale || 'en-US';
    }

    if (!options) {
        options = {
            minimumFractionDigits: settings?.fraction || 0,
            maximumFractionDigits: settings?.fraction || 0,
        };
    }

    try {
        return new Intl.NumberFormat(locale, options).format(formatted);
    } catch (err) {
        return new Intl.NumberFormat('en-US', options).format(formatted);
    }
};

// Currency formatting
export const formatCurrency = (
    amount: number | string,
    locale?: string,
    options?: NumberFormatOptions
): string => {
    if (!amount) amount = 0;

    let formatted = parseFloat(amount as string);
    const settings = usePageProps().settings;
    const pageProps = usePageProps();

    if (!locale || (locale.length !== 2 && locale.length !== 5)) {
        locale = settings?.default_locale || 'en-US';
    }

    const currencyCode = pageProps.default_currency?.code || 'USD';

    if (options?.currency && options.currency.length !== 3) {
        options.currency = currencyCode;
    }

    if (!options) {
        options = {
            style: 'currency',
            currency: currencyCode,
            currencyDisplay: 'narrowSymbol',
            minimumFractionDigits: settings?.fraction || 0,
            maximumFractionDigits: settings?.fraction || 0,
        };
    }

    try {
        return new Intl.NumberFormat(locale, options).format(formatted);
    } catch (err) {
        return new Intl.NumberFormat('en-US', options).format(formatted);
    }
};

// Unit formatting
export const formatUnit = (
    amount: number | string,
    locale?: string,
    options?: NumberFormatOptions
): string => {
    if (!amount) amount = 0;

    let formatted = parseFloat(amount as string);
    const settings = usePageProps().settings;

    if (!locale || (locale.length !== 2 && locale.length !== 5)) {
        locale = settings?.default_locale || 'en-US';
    }

    if (!options) {
        options = {
            style: 'unit',
            unitDisplay: 'narrow',
            minimumFractionDigits: settings?.quantity_fraction || 0,
            maximumFractionDigits: settings?.quantity_fraction || 0,
            unit: (settings?.weight_unit || 'kilogram').toLowerCase(),
        };
    }

    try {
        return new Intl.NumberFormat(locale, options).format(formatted);
    } catch (err) {
        return new Intl.NumberFormat('en-US', options).format(formatted);
    }
};

// Quantity formatting with unit
export const formatNumberQty = (
    amount: number | string,
    unit?: string,
    locale?: string,
    options?: NumberFormatOptions
): string => {
    const settings = usePageProps().settings;

    if (!options && unit) {
        options = {
            style: 'unit',
            unitDisplay: 'narrow',
            minimumFractionDigits: settings?.quantity_fraction || 0,
            maximumFractionDigits: settings?.quantity_fraction || 0,
            unit: (unit || settings?.weight_unit || 'kilogram').toLowerCase(),
        };
    } else if (!options) {
        options = {
            minimumFractionDigits: settings?.quantity_fraction || 0,
            maximumFractionDigits: settings?.quantity_fraction || 0,
        };
    }

    try {
        return formatUnit(amount, locale, options);
    } catch (err) {
        options = {
            minimumFractionDigits: settings?.quantity_fraction || 0,
            maximumFractionDigits: settings?.quantity_fraction || 0,
        };
        return formatNumber(amount, locale, options);
    }
};

// Weight formatting
export const formatWeight = (
    amount: number | string,
    locale?: string,
    options?: NumberFormatOptions
): string => {
    const settings = usePageProps().settings;

    if (!options) {
        options = {
            style: 'unit',
            unitDisplay: 'narrow',
            minimumFractionDigits: settings?.quantity_fraction || 0,
            maximumFractionDigits: settings?.quantity_fraction || 0,
            unit: (settings?.weight_unit || 'kilogram').toLowerCase(),
        };
    }

    return formatUnit(amount, locale, options);
};

// Length formatting
export const formatLength = (
    amount: number | string,
    locale?: string,
    options?: NumberFormatOptions
): string => {
    const settings = usePageProps().settings;

    if (!options) {
        options = {
            style: 'unit',
            unitDisplay: 'narrow',
            minimumFractionDigits: settings?.fraction || 0,
            maximumFractionDigits: settings?.fraction || 0,
            unit: (settings?.dimension_unit || 'centimeter').toLowerCase(),
        };
    }

    return formatUnit(amount, locale, options);
};

// Date validation
export const isValidDate = (dateString: string): boolean => {
    if (!dateString) return false;

    const regEx = /^\d{4}-\d{2}-\d{2}$/;
    if (!dateString.match(regEx)) return false;

    const d = new Date(dateString);
    const dNum = d.getTime();
    if (!dNum && dNum !== 0) return false;

    return d.toISOString().slice(0, 10) === dateString;
};

// Date formatting
export const formatDate = (
    date: string,
    locale?: string,
    style?: 'full' | 'long' | 'medium' | 'short',
    force: boolean = false
): string => {
    if (!date) return '';

    const settings = usePageProps().settings;

    if (!force && settings?.date_format === 'php') {
        return date.split('T')[0];
    }

    date = date.split(' ')[0];
    let formatted = new Date(Date.parse(date));

    try {
        const [year, month, day] = date.split('T')[0].split('-');
        formatted = new Date(parseInt(year), parseInt(month) - 1, parseInt(day), 0, 0, 0, 0);
    } catch (err) {
        // Use the original formatted date
    }

    if (!locale || (locale.length !== 2 && locale.length !== 5)) {
        locale = settings?.default_locale || 'en-US';
    }

    try {
        return formatted.toLocaleString(locale, {
            dateStyle: style || 'medium',
        });
    } catch (err) {
        return formatted.toLocaleString('en-US', {
            dateStyle: style || 'medium',
        });
    }
};

// DateTime formatting
export const formatDateTime = (
    datetime: string,
    locale?: string,
    style?: 'full' | 'long' | 'medium' | 'short',
    force: boolean = false
): string => {
    if (!datetime) return '';

    const settings = usePageProps().settings;

    if (!force && settings?.date_format === 'php') {
        return datetime;
    }

    let formatted = new Date(Date.parse(datetime));

    try {
        if (datetime.includes('T')) {
            const [datePart, timePart] = datetime.split('T');
            const [year, month, day] = datePart.split('-');
            const [hour, minute] = timePart.split(':');
            formatted = new Date(
                parseInt(year),
                parseInt(month) - 1,
                parseInt(day),
                parseInt(hour),
                parseInt(minute),
                0,
                0
            );
        } else if (datetime.includes(' ')) {
            const [datePart, timePart] = datetime.split(' ');
            const [year, month, day] = datePart.split('-');
            const [hour, minute] = timePart.split(':');
            formatted = new Date(
                parseInt(year),
                parseInt(month) - 1,
                parseInt(day),
                parseInt(hour),
                parseInt(minute),
                0,
                0
            );
        }
    } catch (err) {
        console.error('Failed to parse date time.');
    }

    if (!locale || (locale.length !== 2 && locale.length !== 5)) {
        locale = settings?.default_locale || 'en-US';
    }

    try {
        return formatted.toLocaleString(locale, {
            timeStyle: 'short',
            dateStyle: style || 'medium',
            hour12: true,
        });
    } catch (err) {
        return formatted.toLocaleString('en-US', {
            timeStyle: 'short',
            dateStyle: style || 'medium',
            hour12: true,
        });
    }
};

// Boolean formatting (returns JSX for React)
export const formatBoolean = (value: boolean, centered: boolean = false): JSX.Element => {
    const className = `w-full h-full max-w-6 max-h-6 ${centered ? 'flex items-center justify-center' : ''}`;

    if (value) {
        return (
            <div className={className}>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    strokeWidth="1.5"
                    stroke="currentColor"
                    className="w-full h-full max-h-6 max-w-6 text-green-500"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
        );
    } else {
        return (
            <div className={className}>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    strokeWidth="1.5"
                    stroke="currentColor"
                    className="w-full h-full max-h-6 max-w-6 text-red-500"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </div>
        );
    }
};

// Extras formatting
export const formatExtras = (
    fields: ExtraField[],
    extraAttributes: Record<string, any> = {}
): Record<string, any> => {
    const extras: Record<string, any> = {};

    fields.forEach(field => {
        if (extraAttributes[field.name] !== undefined) {
            extras[field.name] = extraAttributes[field.name];
        } else {
            extras[field.name] = field.type === 'checkbox' ? [] : '';
        }
    });

    return extras;
};

// Permission check
export const hasPermission = (permissions: string | string[]): boolean => {
    const pageProps = usePageProps();
    const user = pageProps.is_impersonating ? pageProps.user : pageProps.auth.user;

    if (!user) return false;

    if (user.roles.find(role => role.name === 'Super Admin')) {
        return true;
    }

    const permissionArray = Array.isArray(permissions) ? permissions : [permissions];

    if (permissionArray.includes('all')) {
        return true;
    }

    return permissionArray.some(permission =>
        user.all_permissions && user.all_permissions.includes(permission)
    );
};

// Random number generator
export const random = (min: number = 0, max: number = 999): number => {
    const minCeiled = Math.ceil(min);
    const maxFloored = Math.floor(max);
    return Math.floor(Math.random() * (maxFloored - minCeiled) + minCeiled);
};

// Value check
export const hasValueWithZero = (value: any): boolean => {
    return value === '0' || value === 0 || Boolean(value);
};

// Number formatting utility
export const numberFormat = (
    number: number | string,
    decimals: number,
    decPoint: string = '.',
    thousandsSep: string = ''
): string => {
    const settings = usePageProps().settings;

    if (decimals === undefined) {
        decimals = settings?.fraction || 0;
    }

    let numStr = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    let n = !isFinite(+numStr) ? 0 : +numStr;
    let prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
    let sep = thousandsSep;
    let dec = decPoint;
    let s = '';

    const toFixedFix = (n: number, prec: number): number => {
        if (('' + n).indexOf('e') === -1) {
            return +(Math.round(Number(n + 'e+' + prec)) + 'e-' + prec);
        } else {
            const arr = ('' + n).split('e');
            let sig = '';
            if (+arr[1] + prec > 0) {
                sig = '+';
            }
            return (+(Math.round(Number(+arr[0] + 'e' + sig + (+arr[1] + prec))) + 'e-' + prec)).toFixed(prec) as unknown as number;
        }
    };

    s = (prec ? toFixedFix(n, prec).toString() : '' + Math.round(n)).split('.');

    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }

    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }

    return s.join(dec);
};

// Array chunking
export const chunkArray = <T>(array: T[], size: number): T[][] => {
    if (size <= 0) return [array];

    return array.reduce((result: T[][], _, index) => {
        if (index % size === 0) result.push(array.slice(index, index + size));
        return result;
    }, []);
};

// Export all functions with their original names for backward compatibility
export {
    capitalize as $capitalize,
    formatAddress as $address,
    formatDecimal as $decimal,
    formatDecimalQty as $decimal_qty,
    formatMeta as $meta,
    formatNumber as $number,
    formatCurrency as $currency,
    formatUnit as $unit,
    formatNumberQty as $number_qty,
    formatWeight as $weight,
    formatLength as $length,
    formatDate as $date,
    formatDateTime as $datetime,
    formatBoolean as $boolean,
    formatExtras as $extras,
    hasPermission as $can,
    random as $random,
    hasValueWithZero as has_value_with_zero,
    numberFormat as number_format,
};

// Note: The remaining functions (calculate_item, calculate_discount, calculate_inclusive_tax,
// calculate_exclusive_tax, calculate_taxes, convert_to_base_unit, discount_keypress, check_promotions)
// would need additional context about your axios instance and specific business logic to be properly converted.
// You would need to provide the axios import and any missing types for a complete conversion.

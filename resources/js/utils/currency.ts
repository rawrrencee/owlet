import type { Currency } from '@/types';

/**
 * Format a price value with the correct number of decimal places for a currency.
 *
 * @param amount - The price amount (can be string or number)
 * @param currency - The currency object containing symbol and decimal_places
 * @param options - Optional formatting options
 * @returns Formatted price string (e.g., "S$12.50" or "₩1,200")
 */
export function formatPrice(
    amount: string | number | null | undefined,
    currency?: Currency | null,
    options: { showSymbol?: boolean } = {},
): string {
    const { showSymbol = true } = options;

    if (amount === null || amount === undefined || amount === '') {
        return '-';
    }

    const numericAmount =
        typeof amount === 'string' ? parseFloat(amount) : amount;

    if (isNaN(numericAmount)) {
        return '-';
    }

    const decimalPlaces = currency?.decimal_places ?? 2;
    const formattedAmount = numericAmount.toLocaleString(undefined, {
        minimumFractionDigits: decimalPlaces,
        maximumFractionDigits: decimalPlaces,
    });

    if (showSymbol && currency?.symbol) {
        return `${currency.symbol}${formattedAmount}`;
    }

    return formattedAmount;
}

/**
 * Format a price from a ProductPrice object.
 *
 * @param price - The product price object
 * @returns Formatted price string
 */
export function formatProductPrice(
    price:
        | { unit_price?: string | null; currency?: Currency | null }
        | null
        | undefined,
): string {
    if (!price) {
        return '-';
    }

    return formatPrice(price.unit_price, price.currency);
}

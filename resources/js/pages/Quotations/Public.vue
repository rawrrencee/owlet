<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

interface PublicQuotationItem {
    id: number;
    product?: {
        product_name: string;
        product_number: string;
        variant_name: string | null;
    } | null;
    currency?: {
        code: string;
        symbol: string;
    } | null;
    quantity: number;
    unit_price: string;
    offer_name: string | null;
    line_subtotal: string;
    line_discount: string;
    line_total: string;
}

interface CurrencyTotal {
    currency_id: number;
    currency_code: string;
    currency_symbol: string;
    subtotal: string;
    discount: string;
    tax: string;
    total: string;
}

interface PublicQuotation {
    id: number;
    quotation_number: string;
    company?: {
        company_name: string;
        registration_number: string | null;
        address_1: string | null;
        address_2: string | null;
        city: string | null;
        postal_code: string | null;
        email: string | null;
        phone_number: string | null;
    } | null;
    customer?: {
        full_name: string;
        email: string | null;
        phone: string | null;
    } | null;
    status: string;
    status_label: string;
    show_company_logo: boolean;
    show_company_address: boolean;
    show_company_uen: boolean;
    tax_mode: string;
    tax_percentage: string | null;
    tax_inclusive: boolean;
    terms_and_conditions: string | null;
    external_notes: string | null;
    payment_terms: string | null;
    validity_date: string | null;
    customer_discount_percentage: string | null;
    items: PublicQuotationItem[];
    payment_modes: Array<{ id: number; name: string }>;
    totals: CurrencyTotal[];
    created_at: string | null;
}

interface Props {
    quotation: PublicQuotation | null;
    logoDataUri: string | null;
    requiresPassword?: boolean;
    shareToken?: string;
    passwordError?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    requiresPassword: false,
    shareToken: '',
    passwordError: null,
});

const passwordForm = useForm({
    password: '',
});

function submitPassword() {
    passwordForm.post(`/q/${props.shareToken}`, {
        preserveScroll: true,
    });
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatCurrency(value: string | null, symbol: string): string {
    if (!value) return '-';
    return `${symbol}${parseFloat(value).toFixed(2)}`;
}
</script>

<template>
    <Head :title="quotation ? `Quotation ${quotation.quotation_number}` : 'Quotation'" />

    <!-- Password Entry -->
    <div v-if="requiresPassword" class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
        <div class="w-full max-w-sm">
            <div class="rounded-lg bg-white p-8 shadow">
                <div class="mb-6 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                        <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">Password Required</h2>
                    <p class="mt-1 text-sm text-gray-500">This quotation is password protected.</p>
                </div>
                <form @submit.prevent="submitPassword">
                    <div class="mb-4">
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            placeholder="Enter password"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                            required
                            autofocus
                        />
                        <p v-if="passwordError" class="mt-1 text-sm text-red-600">{{ passwordError }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="passwordForm.processing || !passwordForm.password"
                        class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ passwordForm.processing ? 'Verifying...' : 'View Quotation' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Quotation Content -->
    <div v-else-if="quotation" class="min-h-screen bg-gray-50">
        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <!-- Header -->
                <div class="border-b border-gray-200 px-6 py-6 sm:px-8">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <img
                                v-if="logoDataUri && quotation.show_company_logo"
                                :src="logoDataUri"
                                alt=""
                                class="mb-3 h-14 object-contain"
                            />
                            <div v-if="quotation.company">
                                <h2 class="text-xl font-bold text-gray-900">{{ quotation.company.company_name }}</h2>
                                <div class="mt-1 text-sm text-gray-500">
                                    <div v-if="quotation.show_company_uen && quotation.company.registration_number">
                                        UEN: {{ quotation.company.registration_number }}
                                    </div>
                                    <template v-if="quotation.show_company_address">
                                        <div v-if="quotation.company.address_1">{{ quotation.company.address_1 }}</div>
                                        <div v-if="quotation.company.address_2">{{ quotation.company.address_2 }}</div>
                                        <div v-if="quotation.company.city || quotation.company.postal_code">
                                            {{ quotation.company.city }}{{ quotation.company.city && quotation.company.postal_code ? ' ' : '' }}{{ quotation.company.postal_code }}
                                        </div>
                                    </template>
                                    <div v-if="quotation.company.email">{{ quotation.company.email }}</div>
                                    <div v-if="quotation.company.phone_number">{{ quotation.company.phone_number }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-left sm:text-right">
                            <h1 class="text-2xl font-bold tracking-wide text-gray-900 uppercase">Quotation</h1>
                            <div class="mt-1 text-sm text-gray-500">{{ quotation.quotation_number }}</div>
                            <div class="mt-2 text-sm text-gray-500">
                                <div>Date: {{ formatDate(quotation.created_at) }}</div>
                                <div v-if="quotation.validity_date">Valid Until: {{ formatDate(quotation.validity_date) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-6 sm:px-8">
                    <!-- Customer -->
                    <div v-if="quotation.customer" class="mb-6">
                        <div class="mb-1 text-xs font-semibold tracking-wider text-gray-400 uppercase">Bill To</div>
                        <div class="text-sm">
                            <div class="font-semibold text-gray-900">{{ quotation.customer.full_name }}</div>
                            <div v-if="quotation.customer.email" class="text-gray-500">{{ quotation.customer.email }}</div>
                            <div v-if="quotation.customer.phone" class="text-gray-500">{{ quotation.customer.phone }}</div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="mb-6 overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-2 border-gray-800 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase">
                                    <th class="pb-2 pr-4">#</th>
                                    <th class="pb-2 pr-4">Product</th>
                                    <th class="hidden pb-2 pr-4 sm:table-cell">Currency</th>
                                    <th class="hidden pb-2 pr-4 text-right sm:table-cell">Unit Price</th>
                                    <th class="pb-2 pr-4 text-center">Qty</th>
                                    <th class="hidden pb-2 pr-4 text-right sm:table-cell">Discount</th>
                                    <th class="pb-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, index) in quotation.items"
                                    :key="item.id"
                                    class="border-b border-gray-100"
                                >
                                    <td class="py-3 pr-4 text-sm text-gray-500">{{ index + 1 }}</td>
                                    <td class="py-3 pr-4">
                                        <div class="text-sm font-medium text-gray-900">{{ item.product?.product_name ?? 'Unknown' }}</div>
                                        <div class="text-xs text-gray-400">
                                            {{ item.product?.product_number ?? '' }}
                                            <span v-if="item.product?.variant_name"> - {{ item.product.variant_name }}</span>
                                        </div>
                                        <span
                                            v-if="item.offer_name"
                                            class="mt-0.5 inline-block rounded bg-green-50 px-1.5 py-0.5 text-xs text-green-700"
                                        >
                                            {{ item.offer_name }}
                                        </span>
                                    </td>
                                    <td class="hidden py-3 pr-4 text-sm text-gray-500 sm:table-cell">{{ item.currency?.code ?? '-' }}</td>
                                    <td class="hidden py-3 pr-4 text-right text-sm sm:table-cell">{{ formatCurrency(item.unit_price, item.currency?.symbol ?? '') }}</td>
                                    <td class="py-3 pr-4 text-center text-sm">{{ item.quantity }}</td>
                                    <td class="hidden py-3 pr-4 text-right text-sm sm:table-cell">
                                        <span v-if="parseFloat(item.line_discount) > 0" class="text-green-600">
                                            -{{ formatCurrency(item.line_discount, item.currency?.symbol ?? '') }}
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="py-3 text-right text-sm font-semibold">{{ formatCurrency(item.line_total, item.currency?.symbol ?? '') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals -->
                    <div v-if="quotation.totals && quotation.totals.length > 0" class="mb-6 flex justify-end">
                        <div class="w-full sm:w-72">
                            <template v-for="(total, tIndex) in quotation.totals" :key="total.currency_id">
                                <div v-if="quotation.totals.length > 1" class="mb-1 text-sm font-semibold text-gray-900">{{ total.currency_code }}</div>
                                <div class="flex justify-between py-1 text-sm">
                                    <span class="text-gray-500">Subtotal</span>
                                    <span>{{ total.currency_symbol }}{{ parseFloat(total.subtotal).toFixed(2) }}</span>
                                </div>
                                <div v-if="parseFloat(total.discount) > 0" class="flex justify-between py-1 text-sm text-green-600">
                                    <span>Discount</span>
                                    <span>-{{ total.currency_symbol }}{{ parseFloat(total.discount).toFixed(2) }}</span>
                                </div>
                                <div v-if="parseFloat(total.tax) > 0" class="flex justify-between py-1 text-sm">
                                    <span class="text-gray-500">Tax{{ quotation.tax_inclusive ? ' (incl.)' : '' }}</span>
                                    <span>{{ total.currency_symbol }}{{ parseFloat(total.tax).toFixed(2) }}</span>
                                </div>
                                <div class="mt-1 flex justify-between border-t-2 border-gray-800 pt-2 text-base font-bold text-gray-900">
                                    <span>Total</span>
                                    <span>{{ total.currency_symbol }}{{ parseFloat(total.total).toFixed(2) }}</span>
                                </div>
                                <div v-if="tIndex < quotation.totals.length - 1" class="my-3 border-b border-gray-200" />
                            </template>
                        </div>
                    </div>

                    <!-- Terms & Conditions -->
                    <div v-if="quotation.terms_and_conditions" class="mb-4 border-t border-gray-100 pt-4">
                        <h3 class="mb-1 text-sm font-semibold text-gray-900">Terms & Conditions</h3>
                        <div class="prose prose-sm max-w-none text-sm text-gray-600" v-html="quotation.terms_and_conditions" />
                    </div>

                    <!-- Payment Terms -->
                    <div v-if="quotation.payment_terms" class="mb-4 border-t border-gray-100 pt-4">
                        <h3 class="mb-1 text-sm font-semibold text-gray-900">Payment Terms</h3>
                        <p class="whitespace-pre-wrap text-sm text-gray-600">{{ quotation.payment_terms }}</p>
                    </div>

                    <!-- External Notes -->
                    <div v-if="quotation.external_notes" class="mb-4 border-t border-gray-100 pt-4">
                        <h3 class="mb-1 text-sm font-semibold text-gray-900">Notes</h3>
                        <p class="whitespace-pre-wrap text-sm text-gray-600">{{ quotation.external_notes }}</p>
                    </div>

                    <!-- Payment Modes -->
                    <div v-if="quotation.payment_modes && quotation.payment_modes.length > 0" class="border-t border-gray-100 pt-4">
                        <h3 class="mb-2 text-sm font-semibold text-gray-900">Accepted Payment Modes</h3>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="pm in quotation.payment_modes"
                                :key="pm.id"
                                class="inline-block rounded bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"
                            >
                                {{ pm.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

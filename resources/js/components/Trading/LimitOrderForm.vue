<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTrading } from '@/composables/useTrading';
import { computed, ref, watch } from 'vue';

interface Props {
    balance: number;
    assets: Array<{
        symbol: string;
        amount: number;
        locked_amount: number;
        available: number;
    }>;
    onOrderPlaced?: () => void;
}

const props = defineProps<Props>();

const { createOrder, loading, error } = useTrading();

const symbol = ref<'BTC' | 'ETH'>('BTC');
const side = ref<'buy' | 'sell'>('buy');
const price = ref<string>('');
const amount = ref<string>('');

const maxAmount = computed(() => {
    if (!price.value || parseFloat(price.value) <= 0) {
        return 0;
    }

    const priceNum = parseFloat(price.value);

    if (side.value === 'buy') {
        // buy orders: max amount = balance / price
        return props.balance / priceNum;
    } else {
        // sell orders: max amount = available asset amount
        const asset = props.assets.find((a) => a.symbol === symbol.value);
        return asset ? parseFloat(asset.available.toString()) : 0;
    }
});

// Calculate total cost
const totalValue = computed(() => {
    if (!price.value || !amount.value) {
        return 0;
    }
    return parseFloat(price.value) * parseFloat(amount.value);
});

// Check if amount exceeds available balance
const exceedsBalance = computed(() => {
    if (!amount.value || !price.value) {
        return false;
    }

    if (side.value === 'buy') {
        return totalValue.value > props.balance;
    } else {
        const asset = props.assets.find((a) => a.symbol === symbol.value);
        if (!asset) {
            return true;
        }
        const available = parseFloat(asset.available.toString());
        return parseFloat(amount.value) > available;
    }
});

const autoFillAmount = () => {
    if (side.value === 'buy' && price.value && parseFloat(price.value) > 0) {
        const currentAmount = parseFloat(amount.value) || 0;
        if (
            currentAmount === 0 ||
            !amount.value ||
            amount.value === '0' ||
            amount.value === '0.00' ||
            amount.value === '0.00000000'
        ) {
            const calculated = maxAmount.value;
            if (calculated > 0) {
                amount.value = calculated.toFixed(8);
            }
        }
    } else if (side.value === 'sell') {
        // For sell orders, show max available asset only if amount is empty
        const currentAmount = parseFloat(amount.value) || 0;
        if (
            currentAmount === 0 ||
            !amount.value ||
            amount.value === '0' ||
            amount.value === '0.00' ||
            amount.value === '0.00000000'
        ) {
            const asset = props.assets.find((a) => a.symbol === symbol.value);
            if (asset) {
                const available = parseFloat(asset.available.toString());
                if (available > 0) {
                    amount.value = available.toFixed(8);
                }
            }
        }
    }
};

watch([side, symbol], () => {
    autoFillAmount();
});

const handlePriceBlur = () => {
    autoFillAmount();
};

const setMaxAmount = () => {
    if (maxAmount.value > 0) {
        amount.value = maxAmount.value.toFixed(8);
    }
};

const handleSubmit = async () => {
    await createOrder({
        symbol: symbol.value,
        side: side.value,
        price: parseFloat(price.value),
        amount: parseFloat(amount.value),
    });
    price.value = '';
    amount.value = '';
    if (props.onOrderPlaced) {
        props.onOrderPlaced();
    }
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Place Limit Order</CardTitle>
        </CardHeader>
        <CardContent>
            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div>
                    <Label for="symbol">Symbol</Label>
                    <select
                        id="symbol"
                        v-model="symbol"
                        class="mt-1 block w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="BTC">BTC</option>
                        <option value="ETH">ETH</option>
                    </select>
                </div>

                <div>
                    <Label for="side">Side</Label>
                    <select
                        id="side"
                        v-model="side"
                        class="mt-1 block w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="buy">Buy</option>
                        <option value="sell">Sell</option>
                    </select>
                </div>

                <div>
                    <Label for="price">Price (USD)</Label>
                    <Input
                        id="price"
                        v-model="price"
                        type="number"
                        step="0.01"
                        min="0.01"
                        required
                        placeholder="0.00"
                        @blur="handlePriceBlur"
                    />
                </div>

                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <Label for="amount">Amount ({{ symbol }})</Label>
                        <button
                            type="button"
                            @click="setMaxAmount"
                            class="text-xs text-muted-foreground underline hover:text-foreground"
                            :disabled="maxAmount <= 0"
                        >
                            Max:
                            {{
                                maxAmount > 0
                                    ? maxAmount.toFixed(8)
                                    : '0.00000000'
                            }}
                        </button>
                    </div>
                    <Input
                        id="amount"
                        v-model="amount"
                        type="number"
                        step="0.00000001"
                        min="0.00000001"
                        :max="maxAmount"
                        required
                        placeholder="0.00000000"
                        :class="{ 'border-destructive': exceedsBalance }"
                    />
                    <div
                        v-if="price && amount"
                        class="mt-1 text-xs"
                        :class="
                            exceedsBalance
                                ? 'text-destructive'
                                : 'text-muted-foreground'
                        "
                    >
                        Total: ${{
                            totalValue.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            })
                        }}
                        <span v-if="side === 'buy'">
                            (Balance: ${{
                                balance.toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                })
                            }})
                        </span>
                        <span v-else>
                            (Available:
                            {{
                                assets
                                    .find((a) => a.symbol === symbol)
                                    ?.available?.toFixed(8) || '0.00000000'
                            }}
                            {{ symbol }})
                        </span>
                    </div>
                    <div
                        v-if="exceedsBalance"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{
                            side === 'buy'
                                ? 'Amount exceeds your balance'
                                : 'Amount exceeds your available ' + symbol
                        }}
                    </div>
                </div>

                <div v-if="error" class="text-sm text-destructive">
                    {{ error }}
                </div>

                <Button
                    type="submit"
                    :disabled="loading || exceedsBalance || !price || !amount"
                    class="w-full"
                >
                    {{ loading ? 'Placing Order...' : 'Place Order' }}
                </Button>
            </form>
        </CardContent>
    </Card>
</template>

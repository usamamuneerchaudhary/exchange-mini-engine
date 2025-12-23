<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useOrderbook } from '@/composables/useOrderbook';
import { onMounted, watch } from 'vue';
import { RefreshCw } from 'lucide-vue-next';

interface Props {
    symbol: string;
}

const props = defineProps<Props>();

const { groupedBuyOrders, groupedSellOrders, fetchOrderbook, loading } = useOrderbook(props.symbol);

onMounted(() => {
    fetchOrderbook();
});

watch(() => props.symbol, () => {
    fetchOrderbook();
});


defineExpose({
    refresh: fetchOrderbook,
});
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <CardTitle>Orderbook - {{ symbol }}</CardTitle>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    @click="fetchOrderbook"
                    :disabled="loading"
                    class="h-7 w-7"
                >
                    <RefreshCw :class="{ 'animate-spin': loading }" class="h-4 w-4" />
                </Button>
            </div>
        </CardHeader>
        <CardContent>
            <div v-if="loading" class="text-sm text-muted-foreground text-center py-8">
                Loading...
            </div>
            <div v-else class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-sm font-medium mb-2 text-destructive">Sell Orders</div>
                    <div class="space-y-1 max-h-96 overflow-y-auto">
                        <div
                            v-for="order in groupedSellOrders.slice(0, 20)"
                            :key="`sell-${order.price}`"
                            class="flex items-center justify-between text-sm py-1"
                        >
                            <span class="text-destructive">${{ order.price.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                            <span>{{ parseFloat(order.amount.toString()).toFixed(8) }}</span>
                        </div>
                        <div v-if="groupedSellOrders.length === 0" class="text-sm text-muted-foreground">
                            No sell orders
                        </div>
                    </div>
                </div>
                <div>
                    <div class="text-sm font-medium mb-2 text-green-600 dark:text-green-400">Buy Orders</div>
                    <div class="space-y-1 max-h-96 overflow-y-auto">
                        <div
                            v-for="order in groupedBuyOrders.slice(0, 20)"
                            :key="`buy-${order.price}`"
                            class="flex items-center justify-between text-sm py-1"
                        >
                            <span class="text-green-600 dark:text-green-400">${{ order.price.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                            <span>{{ parseFloat(order.amount.toString()).toFixed(8) }}</span>
                        </div>
                        <div v-if="groupedBuyOrders.length === 0" class="text-sm text-muted-foreground">
                            No buy orders
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>


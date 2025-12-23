<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { useTrading } from '@/composables/useTrading';

interface Order {
    id: number;
    symbol: string;
    side: 'buy' | 'sell';
    price: number;
    amount: number;
    status: number;
    created_at: string;
}

interface PaginatedOrders {
    data: Order[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    orders?: PaginatedOrders;
}

const props = defineProps<Props>();

const { cancelOrder, loading } = useTrading();

const getStatusBadge = (status: number) => {
    switch (status) {
        case 1:
            return { label: 'Open', variant: 'default' as const };
        case 2:
            return { label: 'Filled', variant: 'default' as const };
        case 3:
            return { label: 'Cancelled', variant: 'secondary' as const };
        default:
            return { label: 'Unknown', variant: 'secondary' as const };
    }
};

const handleCancel = async (orderId: number) => {
    if (confirm('Are you sure you want to cancel this order?')) {
        await cancelOrder(orderId);
    }
};

const ordersList = props.orders?.data || [];
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Order History</CardTitle>
        </CardHeader>
        <CardContent>
            <div
                v-if="ordersList.length === 0"
                class="py-8 text-center text-sm text-muted-foreground"
            >
                No orders yet
            </div>
            <div v-else class="space-y-2">
                <div
                    v-for="order in ordersList"
                    :key="order.id"
                    class="flex items-center justify-between border-b pb-2 last:border-0"
                >
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ order.symbol }}</span>
                            <Badge
                                :variant="
                                    order.side === 'buy'
                                        ? 'default'
                                        : 'secondary'
                                "
                                class="text-xs"
                            >
                                {{ order.side.toUpperCase() }}
                            </Badge>
                            <Badge
                                :variant="getStatusBadge(order.status).variant"
                                class="text-xs"
                            >
                                {{ getStatusBadge(order.status).label }}
                            </Badge>
                        </div>
                        <div class="mt-1 text-sm text-muted-foreground">
                            Price: ${{
                                order.price.toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                })
                            }}
                            | Amount:
                            {{ parseFloat(order.amount.toString()).toFixed(8) }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ new Date(order.created_at).toLocaleString() }}
                        </div>
                    </div>
                    <Button
                        v-if="order.status === 1"
                        variant="outline"
                        size="sm"
                        :disabled="loading"
                        @click="handleCancel(order.id)"
                    >
                        Cancel
                    </Button>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="orders && orders.last_page > 1" class="mt-4 border-t pt-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ orders.data.length }} of {{ orders.total }} orders
                    </div>
                </div>
                <div class="flex items-center justify-center gap-2">
                    <Link
                        v-for="link in orders.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1 text-sm rounded-md border transition-colors',
                            link.active
                                ? 'bg-primary text-primary-foreground border-primary'
                                : link.url
                                  ? 'border-input hover:bg-accent hover:text-accent-foreground'
                                  : 'border-input text-muted-foreground cursor-not-allowed opacity-50'
                        ]"
                        :disabled="!link.url"
                        :preserve-scroll="true"
                    >
                        <span v-html="link.label"></span>
                    </Link>
                </div>
            </div>
        </CardContent>
    </Card>
</template>

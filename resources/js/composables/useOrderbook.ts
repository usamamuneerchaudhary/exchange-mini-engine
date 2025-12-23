import { ref, computed } from 'vue';

interface OrderbookOrder {
    id: number;
    symbol: string;
    side: 'buy' | 'sell';
    price: number;
    amount: number;
    created_at: string;
}

export function useOrderbook(symbol: string) {
    const orders = ref<OrderbookOrder[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    const fetchOrderbook = async () => {
        try {
            loading.value = true;
            error.value = null;
            const response = await fetch(`/api/orders?symbol=${symbol}`, {
                credentials: 'include',
            });
            if (!response.ok) {
                throw new Error('Failed to fetch orderbook');
            }
            orders.value = await response.json();
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
        } finally {
            loading.value = false;
        }
    };

    const buyOrders = computed(() => {
        return orders.value
            .filter((order) => order.side === 'buy')
            .sort((a, b) => b.price - a.price);
    });

    const sellOrders = computed(() => {
        return orders.value
            .filter((order) => order.side === 'sell')
            .sort((a, b) => a.price - b.price);
    });

    const groupedBuyOrders = computed(() => {
        const grouped = new Map<number, number>();
        buyOrders.value.forEach((order) => {
            const current = grouped.get(order.price) || 0;
            grouped.set(order.price, current + order.amount);
        });
        return Array.from(grouped.entries())
            .map(([price, amount]) => ({ price, amount }))
            .sort((a, b) => b.price - a.price);
    });

    const groupedSellOrders = computed(() => {
        const grouped = new Map<number, number>();
        sellOrders.value.forEach((order) => {
            const current = grouped.get(order.price) || 0;
            grouped.set(order.price, current + order.amount);
        });
        return Array.from(grouped.entries())
            .map(([price, amount]) => ({ price, amount }))
            .sort((a, b) => a.price - b.price);
    });

    return {
        orders,
        loading,
        error,
        buyOrders,
        sellOrders,
        groupedBuyOrders,
        groupedSellOrders,
        fetchOrderbook,
    };
}


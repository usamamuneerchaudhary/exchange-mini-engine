<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import LimitOrderForm from '@/components/Trading/LimitOrderForm.vue';
import WalletOverview from '@/components/Trading/WalletOverview.vue';
import OrderList from '@/components/Trading/OrderList.vue';
import Orderbook from '@/components/Trading/Orderbook.vue';
import FlashMessages from '@/components/FlashMessages.vue';
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

const props = withDefaults(defineProps<Props>(), {
    orders: undefined,
});

const page = usePage();
const user = page.props.auth.user;

const symbol = ref<'BTC' | 'ETH'>('BTC');
const { profile, fetchProfile, usdBalance, assetBalances } = useTrading();


// Expose orderbook refresh function
const orderbookRef = ref<{ refresh: () => void } | null>(null);

let echo: any = null;

onMounted(async () => {
    await fetchProfile();

    if (typeof window !== 'undefined' && (window as any).Echo) {
        try {
            echo = (window as any).Echo;
            const channel = echo.private(`user.${user.id}`);

            channel.listen('.order.matched', async (data: any) => {
                router.reload({ only: ['orders'] });

                const userOrderIds = (props.orders?.data || []).map((o: any) => o.id);
                const isBuyer = userOrderIds.includes(data.buy_order_id);
                const isSeller = userOrderIds.includes(data.sell_order_id);

                if (profile.value && (isBuyer || isSeller)) {
                    const balanceData = isBuyer ? data.buyer : data.seller;
                    const assetData = isBuyer ? data.buyer?.asset : data.seller?.asset;

                    if (balanceData) {
                        const newBalance = parseFloat(balanceData.balance.toString());

                        if (profile.value) {
                            profile.value = {
                                ...profile.value,
                                balance: Number(newBalance) || 0,
                            };

                            if (assetData) {
                                updateAssetFromEvent(profile.value, assetData);
                            }
                        }
                    }
                }

                await new Promise(resolve => setTimeout(resolve, 100));

                await fetchProfile();

                if (orderbookRef.value) {
                    orderbookRef.value.refresh();
                }
            });

            // Listen for order cancellations event
            channel.listen('.order.cancelled', async () => {
                await fetchProfile();
                router.reload({ only: ['orders'] });
            });

            const updateAssetFromEvent = (profileData: any, assetData: any) => {
                if (!profileData.assets) {
                    profileData.assets = [];
                }

                const assetIndex = profileData.assets.findIndex((a: any) => a.symbol === assetData.symbol);
                const updatedAsset = {
                    symbol: assetData.symbol,
                    amount: parseFloat(assetData.amount.toString()),
                    locked_amount: parseFloat(assetData.locked_amount.toString()),
                    available: parseFloat(assetData.amount.toString()) - parseFloat(assetData.locked_amount.toString()),
                };

                if (assetIndex >= 0) {
                    profileData.assets[assetIndex] = updatedAsset;
                } else {
                    profileData.assets.push(updatedAsset);
                }
            };

            const ordersChannel = echo.channel('orders');

            ordersChannel.listen('.order.cancelled', () => {
                if (orderbookRef.value) {
                    orderbookRef.value.refresh();
                }

                router.reload({ only: ['orders'] });
            });

            ordersChannel.listen('.order.matched', () => {
                if (orderbookRef.value) {
                    orderbookRef.value.refresh();
                }
            });
        } catch (e) {
            console.warn('Failed to initialize Echo:', e);
        }
    }
});

onUnmounted(() => {
    if (echo) {
        echo.disconnect();
    }
});
</script>

<template>
    <Head title="Trading" />

    <AppLayout :breadcrumbs="[]">
        <FlashMessages />
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div class="lg:col-span-1">
                    <LimitOrderForm
                        :balance="usdBalance"
                        :assets="assetBalances"
                        :on-order-placed="() => orderbookRef?.refresh()"
                    />
                </div>
                <div class="lg:col-span-1">
                    <WalletOverview
                        :balance="usdBalance"
                        :assets="assetBalances"
                    />
                </div>
                <div class="lg:col-span-1">
                    <div class="mb-2">
                        <label for="symbol-select" class="text-sm font-medium">Symbol</label>
                        <select
                            id="symbol-select"
                            v-model="symbol"
                            class="mt-1 block w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] outline-none"
                        >
                            <option value="BTC">BTC</option>
                            <option value="ETH">ETH</option>
                        </select>
                    </div>
                    <Orderbook ref="orderbookRef" :symbol="symbol" />
                </div>
            </div>
            <div>
                <OrderList :orders="props.orders" />
            </div>
        </div>
    </AppLayout>
</template>


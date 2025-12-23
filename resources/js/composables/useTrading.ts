import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useFlash } from './useFlash';

interface Profile {
    balance: number;
    assets: Array<{
        symbol: string;
        amount: number;
        locked_amount: number;
        available: number;
    }>;
}

export function useTrading() {
    const profile = ref<Profile | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const { setSuccess, setError } = useFlash();

    const fetchProfile = async () => {
        try {
            loading.value = true;
            error.value = null;
            const response = await fetch('/api/profile', {
                credentials: 'include',
            });

            const data = await response.json();

            profile.value = {
                balance: Number(data.balance) || 0,
                assets: (data.assets || []).map((asset: any) => ({
                    symbol: asset.symbol,
                    amount: Number(asset.amount) || 0,
                    locked_amount: Number(asset.locked_amount) || 0,
                    available: Number(asset.available) || 0,
                })),
            };
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
        } finally {
            loading.value = false;
        }
    };


    const createOrder = async (orderData: {
        symbol: string;
        side: 'buy' | 'sell';
        price: number;
        amount: number;
    }) => {
        try {
            loading.value = true;
            error.value = null;
            const response = await fetch('/api/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                credentials: 'include',
                body: JSON.stringify(orderData),
            });

            const contentType = response.headers.get('content-type');
            let data;

            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            }

            if (!response.ok) {
                const errorMessage = data.errors
                    ? Object.values(data.errors).flat().join(', ')
                    : data.message || 'Failed to create order';
                setError(errorMessage);
            }

            await fetchProfile();

            router.reload({ only: ['orders'] });

            setSuccess(data.message || 'Order placed successfully.');

            return data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const cancelOrder = async (orderId: number) => {
        try {
            loading.value = true;
            error.value = null;
            const response = await fetch(`/api/orders/${orderId}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                credentials: 'include',
            });

            const contentType = response.headers.get('content-type');
            let data;

            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            }

            if (!response.ok) {
                const errorMessage = data.message || 'Failed to cancel order';
                setError(errorMessage);
            }

            await fetchProfile();

            // Reload orders
            router.reload({ only: ['orders'] });

            setSuccess(data.message || 'Order cancelled successfully.');

            return data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            throw e;
        } finally {
            loading.value = false;
        }
    };

    const usdBalance = computed(() => profile.value?.balance || 0);
    const assetBalances = computed(() => profile.value?.assets || []);

    return {
        profile,
        loading,
        error,
        usdBalance,
        assetBalances,
        fetchProfile,
        createOrder,
        cancelOrder,
    };
}


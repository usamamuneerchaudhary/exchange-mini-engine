<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Props {
    balance: number;
    assets: Array<{
        symbol: string;
        amount: number;
        locked_amount: number;
        available: number;
    }>;
}

defineProps<Props>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Wallet</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div>
                <div class="text-sm text-muted-foreground">USD Balance</div>
                <div class="text-2xl font-semibold">
                    ${{
                        balance.toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        })
                    }}
                </div>
            </div>

            <div v-if="assets.length > 0" class="space-y-3">
                <div class="text-sm font-medium">Assets</div>
                <div
                    v-for="asset in assets"
                    :key="asset.symbol"
                    class="flex items-center justify-between border-b pb-2"
                >
                    <div>
                        <div class="font-medium">{{ asset.symbol }}</div>
                        <div class="text-sm text-muted-foreground">
                            Available:
                            {{ parseFloat(String(asset.available)).toFixed(8) }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-medium">
                            {{ parseFloat(String(asset.amount)).toFixed(8) }}
                        </div>
                        <Badge
                            v-if="asset.locked_amount > 0"
                            variant="secondary"
                            class="text-xs"
                        >
                            Locked:
                            {{
                                parseFloat(String(asset.locked_amount)).toFixed(
                                    8,
                                )
                            }}
                        </Badge>
                    </div>
                </div>
            </div>

            <div v-else class="text-sm text-muted-foreground">
                No assets yet
            </div>
        </CardContent>
    </Card>
</template>

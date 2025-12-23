<script setup lang="ts">
import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CheckCircle2, XCircle, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { useFlash } from '@/composables/useFlash';

const page = usePage();
const { flash: apiFlash, clear } = useFlash();

const inertiaFlash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

const flash = computed(() => {
    if (apiFlash.value) {
        return apiFlash.value;
    }
    return inertiaFlash.value;
});

const visible = computed(() => {
    return !!(flash.value && (flash.value.success || flash.value.error));
});

const close = () => {
    clear();
};
</script>

<template>
    <div
        v-if="flash && visible"
        class="fixed top-4 right-4 z-50 w-full max-w-md animate-in slide-in-from-top-5"
    >
        <Alert
            :variant="flash.error ? 'destructive' : 'default'"
            class="relative"
        >
            <component
                :is="flash.error ? XCircle : CheckCircle2"
                class="size-4"
            />
            <AlertTitle>
                {{ flash.error ? 'Error' : 'Success' }}
            </AlertTitle>
            <AlertDescription>
                {{ flash.error || flash.success }}
            </AlertDescription>
            <Button
                variant="ghost"
                size="icon-sm"
                class="absolute right-2 top-2 h-6 w-6"
                @click="close"
            >
                <X class="h-4 w-4" />
            </Button>
        </Alert>
    </div>
</template>


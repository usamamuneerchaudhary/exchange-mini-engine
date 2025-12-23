import { ref } from 'vue';

const flash = ref<{ success?: string; error?: string } | null>(null);

export function useFlash() {
    const setSuccess = (message: string) => {
        flash.value = { success: message };
        setTimeout(() => {
            flash.value = null;
        }, 5000);
    };

    const setError = (message: string) => {
        flash.value = { error: message };
        setTimeout(() => {
            flash.value = null;
        }, 5000);
    };

    const clear = () => {
        flash.value = null;
    };

    return {
        flash,
        setSuccess,
        setError,
        clear,
    };
}


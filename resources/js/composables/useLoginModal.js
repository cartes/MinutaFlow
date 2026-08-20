import { ref } from 'vue';

const isLoginModalOpen = ref(false);
const redirectTarget = ref(null);

export function useLoginModal() {
    function openLoginModal(redirect = null) {
        redirectTarget.value = redirect;
        isLoginModalOpen.value = true;
    }

    function closeLoginModal() {
        isLoginModalOpen.value = false;
    }

    return {
        isLoginModalOpen,
        redirectTarget,
        openLoginModal,
        closeLoginModal,
    };
}

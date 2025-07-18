<script setup>
import {Link} from "@inertiajs/vue3";

const props = defineProps({
    text: String,
    href: String,
    IconAfter: {
        type: Boolean,
        default: false
    }
})
</script>

<template>
  <div>
    <Link v-if="href" type="button"
            :href="href"
            class="bg-red-600 hover:bg-red-700 text-white rounded-full font-semibold text-sm px-5 py-2 flex items-center justify-center text-center border-none shadow-none focus:outline-none focus:ring-2 focus:ring-red-400">
        <span v-if="IconAfter" class="ltr:mr-2 rtl:ml-2 flex items-center justify-center">{{ text }}</span>
        <slot />
        <span v-if="!IconAfter"  class="ltr:ml-2 rtl:mr-2 flex items-center justify-center">{{ text }}</span>
    </Link>
    <button v-else type="button"
            class="bg-red-600 hover:bg-red-700 text-white rounded-full font-semibold text-sm px-5 py-2 flex items-center justify-center text-center border-none shadow-none focus:outline-none focus:ring-2 focus:ring-red-400">
        <span v-if="IconAfter" class="ltr:mr-2 rtl:ml-2 flex items-center justify-center">{{ text }}</span>
        <slot />
        <span v-if="!IconAfter"  class="ltr:ml-2 rtl:mr-2 flex items-center justify-center">{{ text }}</span>
    </button>
  </div>
</template>

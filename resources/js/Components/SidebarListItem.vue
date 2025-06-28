<script setup>
import {Link} from "@inertiajs/vue3";

const props = defineProps({
    itemName: String,
    link: String,
    hasBadge: Boolean,
    badge: String,
    badgeContent: String,
    activeLinksRecursive: Array,
    activeLinks: Array, // ['employees.index, employees.create, etc ]
})

</script>

<template>
    <li>
        <Link :href="route(link)"
              class="flex items-center p-2 py-4 rounded-lg hover:bg-red-700 text-gray-100"
              :class="{'bg-red-800' :
              activeLinksRecursive ? activeLinksRecursive.some(item => $page.url.includes(item)) :
              activeLinks.includes(route().current())
        }">
            <svg aria-hidden="true"
                 class="flex-shrink-0 w-6 h-6 text-gray-100 transition duration-75
                         group-hover:text-white"
                 fill="currentColor" viewBox="0 0 20 20"
                 xmlns="http://www.w3.org/2000/svg">
                <slot/>
            </svg>

            <span class="flex-1 mx-4 whitespace-nowrap">{{ itemName }}</span>

            <span v-if="hasBadge"
                  :class="{'inline-flex items-center justify-center w-3 h-3 p-3 ml-3 text-sm font-medium rounded-full bg-red-700 text-gray-300': badge === 'number',
                            'inline-flex items-center justify-center px-2 ml-3 text-sm font-medium rounded-full bg-red-700 text-gray-300': badge !== 'number'}">
                {{ badgeContent }}
                </span>
        </Link>
    </li>
</template>

<style scoped>

</style>

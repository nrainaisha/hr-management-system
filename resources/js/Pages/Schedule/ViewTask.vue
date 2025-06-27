<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';
import dayjs from 'dayjs';
import axios from 'axios';
import MyScheduleTabs from '@/Components/Tabs/MyScheduleTabs.vue';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import Card from '@/Components/Card.vue';
import FlexButton from '@/Components/FlexButton.vue';

const selectedDate = ref(dayjs().format('YYYY-MM-DD'));
const tasks = ref({ morning: [], evening: [] });
const loading = ref(false);

const formattedSelectedDate = computed(() => {
  return dayjs(selectedDate.value).format('dddd, D MMMM YYYY');
});

async function fetchTasks() {
  loading.value = true;
  const { data } = await axios.get('/my-tasks/day', { params: { date: selectedDate.value } });
  tasks.value = data.tasks || { morning: [], evening: [] };
  loading.value = false;
}

onMounted(fetchTasks);
watch(selectedDate, fetchTasks);
</script>

<template>
  <AuthenticatedLayout>
    <template #tabs>
      <MyScheduleTabs />
    </template>
    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <Card>
          <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">View My Tasks</h1>
            <FlexButton :text="'Today'" @click="selectedDate = dayjs().format('YYYY-MM-DD')" />
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 items-center">
            <div class="flex items-center gap-2">
              <span class="font-semibold">Select Date:</span>
              <VueDatePicker
                v-model="selectedDate"
                :enable-time-picker="false"
                format="yyyy-MM-dd"
                :placeholder="'Select a date...'"
                class="w-full max-w-xs"
                required
              />
            </div>
            <div class="text-blue-700 text-sm font-medium">{{ formattedSelectedDate }}</div>
          </div>
        </Card>
        <Card class="mt-8">
          <div v-if="loading" class="flex justify-center items-center py-12">
            <svg class="animate-spin h-8 w-8 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
          </div>
          <div v-else>
            <div class="mb-8">
              <h2 class="text-lg font-semibold text-gray-700 mb-4">Morning Tasks</h2>
              <ul v-if="tasks.morning.length">
                <li v-for="task in tasks.morning" :key="task.id" class="flex items-center bg-gray-50 rounded-full px-4 py-2 text-sm mb-2">
                  <span class="font-bold text-gray-900 mr-2">{{ task.title }}</span>
                  <span v-if="task.description" class="text-gray-500 mr-2">- {{ task.description }}</span>
                  <span class="ml-auto text-xs text-gray-400">[{{ task.status }}]</span>
                </li>
              </ul>
              <div v-else class="text-gray-400 italic">No Task Assigned</div>
            </div>
            <div>
              <h2 class="text-lg font-semibold text-gray-700 mb-4">Evening Tasks</h2>
              <ul v-if="tasks.evening.length">
                <li v-for="task in tasks.evening" :key="task.id" class="flex items-center bg-gray-50 rounded-full px-4 py-2 text-sm mb-2">
                  <span class="font-bold text-gray-900 mr-2">{{ task.title }}</span>
                  <span v-if="task.description" class="text-gray-500 mr-2">- {{ task.description }}</span>
                  <span class="ml-auto text-xs text-gray-400">[{{ task.status }}]</span>
                </li>
              </ul>
              <div v-else class="text-gray-400 italic">No Task Assigned</div>
            </div>
          </div>
        </Card>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
/**** Remove most custom styles, rely on shared components for layout and theme ****/
</style>

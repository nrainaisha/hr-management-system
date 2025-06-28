<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import dayjs from 'dayjs';
import axios from 'axios';
import MyScheduleTabs from '@/Components/Tabs/MyScheduleTabs.vue';

const today = dayjs();
const thisMonday = today.startOf('week').add(1, 'day');
const currentMonday = ref(today.isBefore(thisMonday, 'day') ? thisMonday : thisMonday.add(7, 'day'));

const assignments = ref({});

async function fetchAssignments() {
  const weekStart = currentMonday.value.format('YYYY-MM-DD');
  const { data } = await axios.get('/my-schedule/week', { params: { week_start: weekStart } });
  assignments.value = data.assignments || {};
}

onMounted(fetchAssignments);
watch(currentMonday, fetchAssignments);

function prevWeek() {
  currentMonday.value = currentMonday.value.subtract(1, 'week');
}
function nextWeek() {
  currentMonday.value = currentMonday.value.add(1, 'week');
}

const days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

// Tab state
const activeTab = ref('weekly');
</script>
<template>
  <AuthenticatedLayout>
    <template #tabs>
      <MyScheduleTabs />
    </template>
    <div class="py-8 bg-gray-50 min-h-screen">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <template v-if="activeTab === 'weekly'">
          <div class="flex justify-between items-center mb-4">
            <button @click="prevWeek" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded shadow-sm transition">Previous</button>
            <span class="font-semibold text-lg">{{ currentMonday.format('MMM D, YYYY') }} - {{ currentMonday.add(6, 'day').format('MMM D, YYYY') }}</span>
            <button @click="nextWeek" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded shadow-sm transition">Next</button>
          </div>
          <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Day</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Morning<br><span class="text-xs text-gray-400">6:00 AM - 3:00 PM</span></th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Evening<br><span class="text-xs text-gray-400">3:00 PM - 12:00 AM</span></th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-100">
                <tr v-for="i in 7" :key="i">
                  <td class="px-6 py-4 whitespace-nowrap text-base font-medium text-gray-800">{{ days[i-1] }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span v-if="assignments[currentMonday.add(i-1, 'day').format('YYYY-MM-DD')]?.morning" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                      Morning
                    </span>
                    <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-200 text-gray-500">
                      No Shift Assigned
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span v-if="assignments[currentMonday.add(i-1, 'day').format('YYYY-MM-DD')]?.evening" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                      Evening
                    </span>
                    <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-200 text-gray-500">
                      No Shift Assigned
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-8 text-center text-gray-500 text-sm">
            <span class="italic">Contact your manager if you have questions about your schedule.</span>
          </div>
        </template>
        <template v-else-if="activeTab === 'task'">
          <div class="flex flex-col items-center justify-center h-64">
            <span class="text-xl text-gray-500">View Task feature coming soon...</span>
          </div>
        </template>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
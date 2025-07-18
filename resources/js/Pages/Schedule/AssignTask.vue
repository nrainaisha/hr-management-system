<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import dayjs from 'dayjs';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import ScheduleTabs from '@/Components/Tabs/ScheduleTabs.vue';
import Card from '@/Components/Card.vue';
import Table from '@/Components/Table/Table.vue';
import TableHead from '@/Components/Table/TableHead.vue';
import TableRow from '@/Components/Table/TableRow.vue';
import TableBody from '@/Components/Table/TableBody.vue';
import FlexButton from '@/Components/FlexButton.vue';

const selectedDate = ref(dayjs().format('YYYY-MM-DD'));
const assignments = ref({});
const staffList = ref([]);
const tasks = ref({}); // { scheduleId: [task, ...] }
const newTask = ref({}); // { scheduleId: { title, description } }
const loading = ref(false);

const shiftNames = ['Morning', 'Night'];
const shiftTimes = [
  { label: 'Morning', start: '06:00', end: '15:00' },
  { label: 'Night', start: '15:00', end: '00:00' }
];

const formattedSelectedDate = computed(() => {
  return dayjs(selectedDate.value).format('dddd, D MMMM YYYY');
});

async function fetchAssignments() {
  loading.value = true;
  const dateString = dayjs(selectedDate.value).format('YYYY-MM-DD');
  const { data } = await axios.get('/schedule/day', { params: { date: dateString } });
  assignments.value = data.assignments || {};
  // Fetch staff list (optional: pass as prop)
  const staffRes = await axios.get('/employees');
  staffList.value = staffRes.data.employees || [];
  // Fetch tasks for both shifts
  for (let shiftIdx = 0; shiftIdx < 2; shiftIdx++) {
    const scheduleId = assignments.value[shiftIdx + '_id'];
    if (scheduleId) {
      const res = await axios.get('/tasks', { params: { schedule_id: scheduleId } });
      tasks.value[scheduleId] = res.data.tasks;
    }
  }
  loading.value = false;
}

onMounted(fetchAssignments);
watch(selectedDate, fetchAssignments);

function getStaffName(staffObj) {
  return staffObj && staffObj.name ? staffObj.name : '';
}

function getStaffInitials(staffObj) {
  return staffObj && staffObj.name ? staffObj.name.split(' ').map(n => n[0]).join('').toUpperCase() : '';
}

async function addTask(scheduleId) {
  if (!newTask.value[scheduleId]?.title) return;
  await axios.post('/tasks', {
    schedule_id: scheduleId,
    title: newTask.value[scheduleId].title,
    description: newTask.value[scheduleId].description || '',
  });
  newTask.value[scheduleId] = { title: '', description: '' };
  const res = await axios.get('/tasks', { params: { schedule_id: scheduleId } });
  tasks.value[scheduleId] = res.data.tasks;
}
</script>

<template>
  <Head title="Assign Task" />
  <AuthenticatedLayout>
    <template #tabs>
      <ScheduleTabs />
    </template>
    <div class="py-8  min-h-screen w-full overflow-x-hidden">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 w-full">
        <div class="bg-gray-900 rounded-2xl shadow-lg border border-gray-800 p-8 mb-8 w-full">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <h1 class="text-3xl font-extrabold text-gray-100">Assign Task to Shifts</h1>
            <button @click="selectedDate = dayjs().format('YYYY-MM-DD')" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-full font-semibold text-lg shadow transition">Today</button>
          </div>
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
              <span class="font-semibold text-gray-200 text-lg">Select Date:</span>
              <VueDatePicker
                v-model="selectedDate"
                :enable-time-picker="false"
                format="yyyy-MM-dd"
                :placeholder="'Select a date...'"
                class="w-full max-w-xs bg-gray-800 text-gray-100 border border-gray-700 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-400"
                required
              />
            </div>
            <div class="text-blue-400 text-base font-medium">{{ formattedSelectedDate }}</div>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full">
          <div v-for="shiftIdx in [0,1]" :key="shiftIdx" class="bg-gray-900 rounded-2xl shadow-lg border border-gray-800 p-8 flex flex-col min-h-[340px] w-full overflow-x-auto">
            <div class="flex items-center gap-3 mb-4 border-b border-gray-800 pb-2">
              <div class="text-xl font-bold text-gray-100">{{ shiftNames[shiftIdx] }}</div>
              <div class="text-sm text-gray-400">{{ shiftTimes[shiftIdx].start }} - {{ shiftTimes[shiftIdx].end }}</div>
            </div>
            <div class="mb-4">
              <div v-if="assignments[shiftIdx]" class="inline-flex items-center gap-2 bg-blue-900 rounded-full px-4 py-1 text-base font-semibold">
                <span class="text-blue-200 font-semibold" :title="getStaffName(assignments[shiftIdx])">{{ getStaffName(assignments[shiftIdx]) }}</span>
              </div>
              <div v-else class="italic text-gray-500 bg-gray-800 rounded px-4 py-1">No Staff Assigned</div>
            </div>
            <div class="flex-1">
              <div v-if="assignments[shiftIdx + '_id']">
                <div v-if="tasks[assignments[shiftIdx + '_id']] && tasks[assignments[shiftIdx + '_id']].length">
                  <ul class="space-y-3 mb-6">
                    <li v-for="task in tasks[assignments[shiftIdx + '_id']]" :key="task.id" class="flex items-center bg-gray-800 rounded-lg px-4 py-3 text-base group border border-gray-700">
                      <span class="font-bold text-gray-100 mr-2">{{ task.title }}</span>
                      <span v-if="task.description" class="text-gray-400 mr-2">- {{ task.description }}</span>
                      <span class="ml-auto text-xs font-semibold px-3 py-1 rounded-full shadow"
                            :class="task.status === 'completed' ? 'bg-green-800 text-green-100' : 'bg-yellow-800 text-yellow-100'">
                        {{ task.status === 'completed' ? 'Completed' : 'Pending' }}
                      </span>
                    </li>
                  </ul>
                </div>
                <div class="flex flex-col gap-4 bg-gray-800 rounded-xl p-5 border border-gray-700">
                  <div class="font-semibold text-gray-200 mb-1 text-lg">Add New Task</div>
                  <div class="flex flex-col md:flex-row md:items-end gap-4">
                    <div class="flex-1 flex flex-col gap-1">
                      <label class="text-gray-300 text-sm font-semibold mb-1" :for="'task-title-' + shiftIdx">Task Title</label>
                      <input
                        :id="'task-title-' + shiftIdx"
                        v-model="(newTask[assignments[shiftIdx + '_id']] ??= { title: '', description: '' }).title"
                        placeholder="Enter task title"
                        class="border border-gray-700 bg-gray-900 text-gray-100 rounded-lg px-4 py-2 text-base focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm w-full"
                        aria-label="Task title"
                      />
                    </div>
                    <div class="flex-1 flex flex-col gap-1">
                      <label class="text-gray-300 text-sm font-semibold mb-1" :for="'task-desc-' + shiftIdx">Description</label>
                      <textarea
                        :id="'task-desc-' + shiftIdx"
                        v-model="(newTask[assignments[shiftIdx + '_id']] ??= { title: '', description: '' }).description"
                        placeholder="Describe the task (optional)"
                        rows="2"
                        class="border border-gray-700 bg-gray-900 text-gray-100 rounded-lg px-4 py-2 text-base resize-none focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm w-full"
                        aria-label="Task description"
                      />
                    </div>
                    <button
                      @click="addTask(assignments[shiftIdx + '_id'])"
                      :disabled="!(newTask[assignments[shiftIdx + '_id']] && newTask[assignments[shiftIdx + '_id']].title)"
                      class="bg-green-700 hover:bg-green-800 text-white px-6 py-2 rounded-full font-semibold text-base shadow transition disabled:opacity-50 disabled:cursor-not-allowed mt-2 md:mt-0"
                    >Add</button>
                  </div>
                </div>
              </div>
              <div v-else class="text-gray-500 italic mt-8">No staff assigned for this shift.</div>
            </div>
          </div>
        </div>
        <div v-if="!assignments[0] && !assignments[1]" class="flex flex-col items-center justify-center py-16">
          <svg class="mb-2 w-12 h-12 text-blue-800" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          <span class="text-gray-500 font-semibold text-xl">No staff assigned for this date.</span>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.group:hover {
  background-color: #1f2937;
}
</style>

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
    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <Card>
          <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Assign Task to Shifts</h1>
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
            <Table :totalNumber="2" :enablePaginator="false">
              <template #Head>
                <TableHead class="bg-gray-100 text-base font-bold text-gray-700 py-4">Shift</TableHead>
                <TableHead class="bg-gray-100 text-base font-bold text-gray-700 py-4">Staff</TableHead>
                <TableHead class="bg-gray-100 text-base font-bold text-gray-700 py-4">Tasks</TableHead>
              </template>
              <template #Body>
                <TableRow v-for="shiftIdx in [0,1]" :key="shiftIdx" class="border-b last:border-b-0">
                  <TableBody class="py-4 align-top">
                    <div class="font-semibold text-gray-900 text-base">{{ shiftNames[shiftIdx] }}</div>
                    <div class="text-xs text-gray-500">{{ shiftTimes[shiftIdx].start }} - {{ shiftTimes[shiftIdx].end }}</div>
                  </TableBody>
                  <TableBody class="py-4 align-top">
                    <span v-if="assignments[shiftIdx]" class="inline-flex items-center gap-2 bg-gray-100 rounded-full px-3 py-1 text-sm font-semibold">
                      <span class="flex items-center justify-center bg-blue-900 text-white rounded-full w-7 h-7 font-bold">{{ getStaffInitials(assignments[shiftIdx]) }}</span>
                      <span class="text-blue-700 font-semibold" :title="getStaffName(assignments[shiftIdx])">{{ getStaffName(assignments[shiftIdx]) }}</span>
                    </span>
                    <span v-else class="italic text-gray-400 bg-gray-50 rounded px-3 py-1">No Staff Assigned</span>
                  </TableBody>
                  <TableBody class="py-4 align-top">
                    <div v-if="assignments[shiftIdx + '_id']">
                      <div v-if="tasks[assignments[shiftIdx + '_id']] && tasks[assignments[shiftIdx + '_id']].length">
                        <ul class="space-y-2">
                          <li v-for="task in tasks[assignments[shiftIdx + '_id']]" :key="task.id" class="flex items-center bg-gray-50 rounded-full px-4 py-2 text-sm">
                            <span class="font-bold text-gray-900 mr-2">{{ task.title }}</span>
                            <span v-if="task.description" class="text-gray-500 mr-2">- {{ task.description }}</span>
                            <span class="ml-auto text-xs text-gray-400">[{{ task.status }}]</span>
                          </li>
                        </ul>
                      </div>
                      <div class="flex gap-2 mt-2">
                        <input v-model="(newTask[assignments[shiftIdx + '_id']] ??= { title: '', description: '' }).title" placeholder="Task title" class="border border-gray-300 rounded-full px-4 py-2 text-sm w-32" aria-label="Task title" />
                        <input v-model="(newTask[assignments[shiftIdx + '_id']] ??= { title: '', description: '' }).description" placeholder="Description" class="border border-gray-300 rounded-full px-4 py-2 text-sm w-40" aria-label="Task description" />
                        <FlexButton :text="'Add'" @click="addTask(assignments[shiftIdx + '_id'])" />
                      </div>
                    </div>
                  </TableBody>
                </TableRow>
              </template>
            </Table>
            <div v-if="!assignments[0] && !assignments[1]" class="flex flex-col items-center justify-center py-12">
              <svg class="mb-2 w-10 h-10 text-blue-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              <span class="text-gray-400 font-semibold text-base">No staff assigned for this date.</span>
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

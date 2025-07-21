<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { router } from '@inertiajs/vue3';

// Icon SVGs for summary cards
const cardIcons = [
  `<svg class='w-7 h-7 text-blue-400' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M17 20h5v-2a4 4 0 0 0-3-3.87M9 20H4v-2a4 4 0 0 1 3-3.87M16 3.13a4 4 0 0 1 0 7.75M8 3.13a4 4 0 0 0 0 7.75'/></svg>`, // Employees
  `<svg class='w-7 h-7 text-green-400' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M5 13l4 4L19 7'/></svg>`, // Attendance Rate
  `<svg class='w-7 h-7 text-yellow-400' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M12 8v4l3 3'/><circle cx='12' cy='12' r='10'/></svg>`, // Payroll Cost
  `<svg class='w-7 h-7 text-pink-400' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M12 20l9-5-9-5-9 5 9 5z'/><path d='M12 12V4'/></svg>` // Top Staff
];

const ratingCounts = [2, 7, 13, 17, 15, 33, 19, 28, 14, 5];
const statusData = [
  { label: 'Full time', value: 40, color: '#6C63FF' },
  { label: 'Part time', value: 25, color: '#3ECF8E' },
  { label: 'Sick leave', value: 10, color: '#FFB946' },
  { label: 'Vacation', value: 8, color: '#FF5C5C' },
];

const tableRows = [
  { id: 1, name: 'Milli Parkes', org: 'Chicago Dreams', kpi: 'L2 inclusive', kpiValue: '87%', avgScore: 6, progress: 0.7 },
  { id: 2, name: 'Billie Barclay', org: 'Maple Leaf College', kpi: '5 L2', kpiValue: '97%', avgScore: 9, progress: 0.95 },
  { id: 3, name: 'Conna Rankin', org: 'Chicago Dreams', kpi: '5 L1', kpiValue: '81%', avgScore: 8, progress: 0.8 },
  { id: 4, name: 'Dan Keenan', org: 'Pinewood School', kpi: '5 A/A', kpiValue: '37%', avgScore: 7, progress: 0.6 },
];

const search = ref('');
const filteredRows = computed(() => {
  if (!search.value) return tableRows;
  return tableRows.filter(row =>
    row.name.toLowerCase().includes(search.value.toLowerCase()) ||
    row.org.toLowerCase().includes(search.value.toLowerCase())
  );
});

const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
];
const now = new Date();
const defaultMonth = now.getMonth() === 0 ? 11 : now.getMonth() - 1;
const selectedMonth = ref(defaultMonth);
const showMonthDropdown = ref(false);
function selectMonth(idx) {
  selectedMonth.value = idx;
  showMonthDropdown.value = false;
  // Format month as 'YYYY-MM'
  const now = new Date();
  const year = now.getFullYear();
  const month = (idx + 1).toString().padStart(2, '0');
  const monthParam = `${year}-${month}`;
  // Trigger Inertia visit with new month param
  router.get(route('reports.index'), { month: monthParam }, { preserveState: true, preserveScroll: true });
}
const selectedMonthLabel = computed(() => monthNames[selectedMonth.value]);

// Remove all mock data arrays and use real props from backend
const props = defineProps({
  employeeStats: Array,
  summary: Object,
  month: String,
  allStaff: Array,
  selectedStaffId: [String, Number, null],
});

const staffNames = computed(() => ['All Staff', ...props.allStaff.map(s => s.name)]);
const selectedStaff = ref('All Staff');

const filteredStats = computed(() => {
  if (selectedStaff.value === 'All Staff') return props.employeeStats;
  return props.employeeStats.filter(s => s.name === selectedStaff.value);
});

const summaryCards = computed(() => [
  { label: 'Employees', value: props.summary.employees },
  { label: 'Attendance Rate', value: props.summary.attendance_rate ? props.summary.attendance_rate.toFixed(1) + '%' : '0%' },
  { label: 'Payroll Cost', value: 'RM ' + (props.summary.payroll_cost ?? 0) },
  { label: 'Top Staff', value: props.summary.top_staff ?? '-' },
]);

const filteredStaffAttendance = computed(() => filteredStats.value.map(s => ({
  name: s.name,
  present: s.attended,
  absent: s.absented,
  late: s.late,
})));

const filteredStaffTasks = computed(() => filteredStats.value.map(s => ({
  name: s.name,
  completed: s.task_completed,
  total: s.task_total,
  task_label: s.task_label || null,
})));

const filteredRankedStaff = computed(() => {
  // Exclude supervisor/admin (id === 1 or task_label === 'Non-applicable')
  const filtered = filteredStats.value.filter(s => s.id !== 1 && s.task_label !== 'Non-applicable');
  const maxClients = Math.max(...filtered.map(s => s.clients ?? 0), 1);
  return filtered.map(s => {
  const clientScore = (s.clients / maxClients) * 100;
    const attendance = s.attended + s.absented + s.late > 0 ? (s.attended / (s.attended + s.absented + s.late)) * 100 : 0;
    const task = s.task_total > 0 ? (s.task_completed / s.task_total) * 100 : 0;
    const score = (clientScore * 0.5) + (attendance * 0.3) + (task * 0.2);
    return { ...s, clients: s.clients, attendance: Math.round(attendance), task: Math.round(task), score: score.toFixed(1) };
}).sort((a, b) => b.score - a.score);
});

// Helper for donut SVG
function donutPath(percent, r) {
  const c = 2 * Math.PI * r;
  return {
    strokeDasharray: `${(percent / 100) * c} ${c}`,
    strokeDashoffset: 0,
  };
}

// Medal icons for top 3
const medalIcons = [
  `<svg class='w-5 h-5 text-yellow-400 inline' fill='currentColor' viewBox='0 0 20 20'><path d='M10 2a1 1 0 01.894.553l1.382 2.8 3.09.45a1 1 0 01.554 1.706l-2.236 2.18.528 3.08a1 1 0 01-1.451 1.054L10 12.347l-2.771 1.456a1 1 0 01-1.451-1.054l.528-3.08-2.236-2.18a1 1 0 01.554-1.706l3.09-.45L9.106 2.553A1 1 0 0110 2z'/></svg>`,
  `<svg class='w-5 h-5 text-gray-300 inline' fill='currentColor' viewBox='0 0 20 20'><path d='M10 2a1 1 0 01.894.553l1.382 2.8 3.09.45a1 1 0 01.554 1.706l-2.236 2.18.528 3.08a1 1 0 01-1.451 1.054L10 12.347l-2.771 1.456a1 1 0 01-1.451-1.054l.528-3.08-2.236-2.18a1 1 0 01.554-1.706l3.09-.45L9.106 2.553A1 1 0 0110 2z'/></svg>`,
  `<svg class='w-5 h-5 text-yellow-700 inline' fill='currentColor' viewBox='0 0 20 20'><path d='M10 2a1 1 0 01.894.553l1.382 2.8 3.09.45a1 1 0 01.554 1.706l-2.236 2.18.528 3.08a1 1 0 01-1.451 1.054L10 12.347l-2.771 1.456a1 1 0 01-1.451-1.054l.528-3.08-2.236-2.18a1 1 0 01.554-1.706l3.09-.45L9.106 2.553A1 1 0 0110 2z'/></svg>`
];

function exportCSV() {
  const headers = ['ID','Name','Organization','KPI label','KPI value','Average score','Curriculum Progress'];
  const rows = filteredRows.value.map(row => [row.id, row.name, row.org, row.kpi, row.kpiValue, row.avgScore, Math.round(row.progress * 100) + '%']);
  const csv = [headers, ...rows].map(r => r.join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `reports.csv`;
  a.click();
  URL.revokeObjectURL(url);
}

// Update noData to also check if all employees have 0 attendance and 0 tasks
const noData = computed(() => {
  if (!props.employeeStats || props.employeeStats.length === 0) return true;
  // If all employees have 0 attendance and 0 tasks
  return props.employeeStats.every(e =>
    (!e.attended && !e.absented && !e.late && !e.on_time) &&
    (!e.task_completed && !e.task_total)
  );
});
</script>

<template>
  <Head title="Reports" />
  <AuthenticatedLayout>
    <div class="py-8 px-2 md:px-0">
      <div class="max-w-7xl mx-auto">
        <!-- Section 1: 4 summary cards in a row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div
            v-for="(card, i) in summaryCards"
            :key="card.label"
            class="flex flex-col items-center justify-center bg-gray-900 border border-gray-700 rounded-2xl p-4 shadow-md transition hover:shadow-xl hover:-translate-y-1"
          >
            <span v-html="cardIcons[i]" class="mb-2 text-xl"></span>
            <div class="text-2xl font-extrabold text-white mb-1" :class="[i === 3 ? 'text-pink-400 text-center' : '', '']">
              {{ card.value }}
            </div>
            <div class="text-gray-400 text-sm font-medium tracking-wide text-center">
              {{ card.label }}
            </div>
          </div>
        </div>
        <hr class="my-6 border-0 h-0.5 bg-gray-800 rounded" />
        <!-- Filters: Month and Staff in the same row above charts (section 2) -->
        <div class="flex flex-row items-center mb-4 gap-4 justify-between">
          <div class="flex items-center">
          <span class="text-gray-400 text-xs font-semibold mr-2">Filter by month</span>
          <div class="relative">
            <button @click="showMonthDropdown = !showMonthDropdown" class="bg-gray-900 border border-gray-700 text-gray-200 px-3 py-1 rounded-md text-sm font-semibold flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-red-500">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3M16 7V3M4 11h16M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>
              {{ selectedMonthLabel }}
              <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div v-if="showMonthDropdown" class="absolute right-0 mt-2 w-36 bg-gray-900 border border-gray-700 rounded-md shadow-lg z-50">
              <ul>
                <li v-for="(name, idx) in monthNames" :key="name">
                  <button @click="selectMonth(idx)" class="w-full text-left px-4 py-2 text-gray-200 hover:bg-gray-800 focus:bg-gray-800 focus:outline-none" :class="{ 'bg-gray-800': selectedMonth.value === idx }">
                    {{ name }}
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>
          <div class="flex items-center">
          <label for="staff" class="text-gray-400 text-xs font-semibold mr-2">Filter by staff</label>
          <select id="staff" v-model="selectedStaff" @change="selectStaff" class="bg-gray-900 border border-gray-700 text-gray-200 px-3 py-1 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
            <option v-for="name in staffNames" :key="name" :value="name">{{ name }}</option>
          </select>
          </div>
        </div>
        <!-- Section 2: 2 chart cards in a row -->
        <div v-if="noData" class="bg-red-100 text-red-700 text-center p-4 rounded mb-6 font-semibold">No data available for the selected month.</div>
        <div v-else class="flex flex-row gap-4 mb-4">
          <!-- Attendance Breakdown Card -->
          <div class="flex-1 bg-gray-800 border border-gray-700 rounded-lg p-6 shadow flex flex-col">
            <div class="font-semibold text-white mb-4">Attendance Breakdown (This Month)</div>
            <div class="flex flex-col gap-7">
              <div v-for="staff in filteredStaffAttendance" :key="staff.name" class="flex flex-col gap-2">
                <div class="text-sm text-gray-200 mb-1">{{ staff.name }}</div>
                <div class="flex items-center h-8 rounded overflow-hidden w-full" style="min-width: 200px;">
                  <div
                    class="flex items-center justify-center h-full bg-green-500 text-white text-xs font-bold"
                    :style="{ width: `${(staff.present / (staff.present + staff.late + staff.absent) * 100).toFixed(1)}%` }"
                    v-if="staff.present > 0"
                  >
                    {{ staff.present }}
                  </div>
                  <div
                    class="flex items-center justify-center h-full bg-yellow-400 text-gray-900 text-xs font-bold"
                    :style="{ width: `${(staff.late / (staff.present + staff.late + staff.absent) * 100).toFixed(1)}%` }"
                    v-if="staff.late > 0"
                  >
                    {{ staff.late }}
                  </div>
                  <div
                    class="flex items-center justify-center h-full bg-red-500 text-white text-xs font-bold"
                    :style="{ width: `${(staff.absent / (staff.present + staff.late + staff.absent) * 100).toFixed(1)}%` }"
                    v-if="staff.absent > 0"
                  >
                    {{ staff.absent }}
                  </div>
                </div>
              </div>
            </div>
            <div class="flex gap-6 mt-8 text-sm text-gray-300">
              <span class="flex items-center"><span class="inline-block w-4 h-4 bg-green-500 rounded-full mr-2"></span>Present</span>
              <span class="flex items-center"><span class="inline-block w-4 h-4 bg-yellow-400 rounded-full mr-2"></span>Late</span>
              <span class="flex items-center"><span class="inline-block w-4 h-4 bg-red-500 rounded-full mr-2"></span>Absent</span>
            </div>
          </div>
          <!-- Task Completion Rate Card (Donuts) -->
          <div class="flex-1 bg-gray-800 border border-gray-700 rounded-lg p-6 shadow flex flex-col">
            <div class="font-semibold text-white mb-4">Task Completion Rate (This Month)</div>
            <div class="grid grid-cols-2 gap-8">
              <div v-for="staff in filteredStaffTasks" :key="staff.name" class="flex flex-col items-center gap-2">
                <svg width="80" height="80" viewBox="0 0 80 80">
                  <circle cx="40" cy="40" r="32" stroke="#22223b" stroke-width="10" fill="none" />
                  <circle
                    cx="40" cy="40" r="32"
                    :stroke="staff.task_label === 'Non-applicable' ? '#6B7280' : staff.completed / staff.total >= 0.8 ? '#3ECF8E' : staff.completed / staff.total >= 0.6 ? '#FFB946' : '#FF5C5C'"
                    stroke-width="10"
                    fill="none"
                    :stroke-dasharray="donutPath((staff.task_label === 'Non-applicable' ? 100 : staff.completed / staff.total) * 100, 32).strokeDasharray"
                    :stroke-dashoffset="donutPath((staff.task_label === 'Non-applicable' ? 100 : staff.completed / staff.total) * 100, 32).strokeDashoffset"
                    stroke-linecap="round"
                    transform="rotate(-90 40 40)"
                  />
                  <text v-if="staff.task_label !== 'Non-applicable'" x="40" y="48" text-anchor="middle" font-size="22" fill="#fff" font-weight="bold">{{ ((staff.completed / staff.total) * 100).toFixed(0) }}%</text>
                </svg>
                <div class="text-sm text-gray-200 mt-2">{{ staff.name }}</div>
                <div v-if="staff.task_label === 'Non-applicable'" class="text-sm text-gray-400">Non-applicable</div>
                <div v-else class="text-sm text-gray-400">{{ staff.completed }}/{{ staff.total }} tasks</div>
              </div>
            </div>
          </div>
        </div>
        <hr class="my-6 border-0 h-0.5 bg-gray-800 rounded" />
        <!-- Section 3: Table -->
        <div class="bg-gray-900 border border-gray-700 rounded-lg shadow p-6">
          <div class="font-semibold text-white mb-4">Ranking (Clients, Attendance, Task Completion)</div>
          <div class="text-xs text-gray-400 mb-2">Score = (ClientScore*0.5) + (AttendanceRate*0.3) + (TaskCompletion*0.2)</div>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-gray-200">
              <thead class="sticky top-0 z-10 bg-gray-900 border-b border-gray-800">
                <tr>
                  <th class="px-4 py-2">Rank</th>
                  <th class="px-4 py-2">Name</th>
                  <th class="px-4 py-2">Clients</th>
                  <th class="px-4 py-2">Attendance</th>
                  <th class="px-4 py-2">Task</th>
                  <th class="px-4 py-2">Score</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in filteredRankedStaff" :key="row.name" class="border-b border-gray-800 hover:bg-gray-800 transition">
                  <td class="px-4 py-2 font-bold text-center">
                    <span v-if="idx < 3" v-html="medalIcons[idx]"></span>
                    <span v-else>{{ idx + 1 }}</span>
                  </td>
                  <td class="px-4 py-2 text-center">{{ row.name }}</td>
                  <td class="px-4 py-2 text-center">
                    <span class="inline-block bg-blue-900 text-blue-300 font-bold px-3 py-1 rounded-full text-xs">{{ row.clients }}</span>
                  </td>
                  <td class="px-4 py-2 w-40">
                    <div class="w-full h-4 bg-gray-800 rounded-full overflow-hidden">
                      <div class="h-4 rounded-full"
                        :style="{ width: row.attendance + '%', background: row.attendance >= 90 ? '#3ECF8E' : row.attendance >= 80 ? '#FFB946' : '#FF5C5C' }">
                      </div>
                    </div>
                    <span class="text-xs text-gray-300 ml-2 align-middle">{{ row.attendance }}%</span>
                  </td>
                  <td class="px-4 py-2 w-40">
                    <div class="w-full h-4 bg-gray-800 rounded-full overflow-hidden">
                      <div class="h-4 rounded-full"
                        :style="{ width: row.task + '%', background: row.task >= 90 ? '#3ECF8E' : row.task >= 80 ? '#FFB946' : '#FF5C5C' }">
                      </div>
                    </div>
                    <span class="text-xs text-gray-300 ml-2 align-middle">{{ row.task }}%</span>
                  </td>
                  <td class="px-4 py-2 font-bold text-center">
                    <span :class="[row.score >= 90 ? 'bg-green-700 text-green-200' : row.score >= 80 ? 'bg-yellow-700 text-yellow-200' : 'bg-red-700 text-red-200', 'px-3 py-1 rounded-full text-xs']">
                      {{ row.score }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.sticky { position: sticky; }
</style> 
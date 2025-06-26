<script setup>
import { ref, computed } from 'vue';
import dayjs from 'dayjs';
import Card from '@/Components/Card.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

// Mock staff data
const staffList = [
  { id: 1, name: 'Alice' },
  { id: 2, name: 'Bob' },
  { id: 3, name: 'Charlie' },
];

const shiftNames = ['Morning', 'Evening'];

// State for the selected week (start on Monday)
const currentMonday = ref(dayjs().startOf('week').add(1, 'day'));

// State for the selected day and shift
const selectedDay = ref(null);
const selectedShiftIdx = ref(null);

// Loading state for validation
const isValidating = ref(false);

// Assignments: Each day holds two shifts, each shift holds staffId or null
const assignments = ref({});

// Calculate the days for the selected week
const weekDays = computed(() => {
  return Array.from({ length: 7 }, (_, i) => currentMonday.value.add(i, 'day'));
});

// Open modal for a specific day and shift
function openDayModal(day, shiftIdx) {
  selectedDay.value = day;
  selectedShiftIdx.value = shiftIdx;
  if (!assignments.value[day.format('YYYY-MM-DD')]) {
    assignments.value[day.format('YYYY-MM-DD')] = [null, null];
  }
}

// Close modal
function closeDayModal() {
  if (isValidating.value) return;
  selectedDay.value = null;
  selectedShiftIdx.value = null;
}

// Assign staff to a shift with loading animation
function assignStaff(staffId) {
  isValidating.value = true;
  setTimeout(() => {
    assignments.value[selectedDay.value.format('YYYY-MM-DD')][selectedShiftIdx.value] = staffId;
    isValidating.value = false;
    closeDayModal();
  }, 1000); // Simulate 1 second validation
}

// Navigate to previous week
function prevWeek() {
  currentMonday.value = currentMonday.value.subtract(1, 'week');
}

// Navigate to next week
function nextWeek() {
  currentMonday.value = currentMonday.value.add(1, 'week');
}

// Submit the schedule
function submitSchedule() {
  let hasError = false;
  Object.values(assignments.value).forEach(day => {
    day.forEach(shift => {
      if (!shift) hasError = true;
    });
  });
  if (hasError) {
    alert('Please assign staff to all shifts before submitting.');
    return;
  }
  alert('Weekly schedule submitted!');
}

// Get staff name for a shift
function getStaffName(day, shiftIdx) {
  const staffId = assignments.value[day.format('YYYY-MM-DD')]?.[shiftIdx];
  const staff = staffList.find(s => s.id === staffId);
  return staff ? staff.name : '';
}

// Check if a shift is assigned
function isShiftAssigned(day, shiftIdx) {
  return !!assignments.value[day.format('YYYY-MM-DD')]?.[shiftIdx];
}

const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const selectedWeek = ref(new Date());

const changeWeek = (direction) => {
    const newDate = new Date(selectedWeek.value);
    newDate.setDate(newDate.getDate() + (direction === 'next' ? 7 : -7));
    selectedWeek.value = newDate;
};
</script>

<template>
  <Head title="Weekly Schedule" />
  <AuthenticatedLayout>
    <template #tabs>
      <!-- Optional: Add ScheduleTabs.vue if you want sub-navigation like CalendarTabs -->
    </template>
    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto py-8 min-h-screen flex flex-col justify-start">
          <Card class="!mt-0 flex-1 flex flex-col">
            <!-- Week Selector -->
            <div class="flex justify-between items-center px-6 pt-6 pb-2">
              <button @click="changeWeek('prev')" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded shadow-sm transition">Previous</button>
              <span class="font-semibold text-lg">{{ currentMonday.format('MMM D, YYYY') }} - {{ currentMonday.add(6, 'day').format('MMM D, YYYY') }}</span>
              <button @click="changeWeek('next')" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded shadow-sm transition">Next</button>
            </div>
            <!-- Vertical Week View -->
            <div class="flex-1 flex flex-col justify-center px-6 pb-6">
              <table class="w-full border-separate border-spacing-y-2">
                <thead>
                  <tr>
                    <th class="text-left text-gray-500 text-xs uppercase tracking-wider py-2">Day</th>
                    <th class="text-center text-gray-700 text-sm font-semibold">Morning</th>
                    <th class="text-center text-gray-700 text-sm font-semibold">Evening</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(day, dayIdx) in weekDays" :key="dayIdx" class="bg-white rounded-lg shadow-sm">
                    <td class="py-4 px-4 text-lg font-bold text-gray-700 w-40">
                      <div class="flex flex-col">
                        <span class="text-xs font-semibold text-gray-400 uppercase">{{ day.format('ddd') }}</span>
                        <span class="text-2xl font-extrabold">{{ day.format('D') }}</span>
                      </div>
                    </td>
                    <td v-for="shiftIdx in [0,1]" :key="shiftIdx" class="py-2 px-2">
                      <div
                        :class="[
                          'w-full h-16 flex items-center justify-center rounded-lg cursor-pointer transition text-base font-medium border-2',
                          isShiftAssigned(day, shiftIdx)
                            ? 'bg-green-200 text-green-900 border-green-300 shadow'
                            : 'bg-gray-100 text-gray-400 border-gray-200 hover:bg-purple-100 hover:text-purple-700 hover:border-purple-300',
                        ]"
                        @click="openDayModal(day, shiftIdx)"
                      >
                        <span v-if="isShiftAssigned(day, shiftIdx)">
                          {{ getStaffName(day, shiftIdx) }}
                        </span>
                        <span v-else>
                          {{ shiftNames[shiftIdx] }}
                        </span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
              <!-- Submit Button -->
              <div class="flex justify-end mt-8">
                <button @click="submitSchedule" class="bg-gradient-to-r from-purple-500 to-purple-700 text-white px-8 py-2 rounded-lg shadow hover:from-purple-600 hover:to-purple-800 transition font-semibold text-base">Submit Weekly Schedule</button>
              </div>
            </div>
          </Card>

          <!-- Stylish Modal -->
          <transition name="fade">
            <div v-if="selectedDay !== null && selectedShiftIdx !== null" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
              <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md relative animate-fadeIn">
                <button @click="closeDayModal" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-xl font-bold" :disabled="isValidating">&times;</button>
                <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">
                  Assign {{ shiftNames[selectedShiftIdx] }} Shift<br>
                  <span class="text-base font-medium text-gray-500">for {{ selectedDay.format('ddd, MMM D') }}</span>
                </h2>
                <div v-if="isValidating" class="flex flex-col items-center justify-center py-8">
                  <svg class="animate-spin h-10 w-10 text-purple-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                  </svg>
                  <span class="text-purple-600 font-semibold text-lg">Validating staff...</span>
                </div>
                <div v-else class="mb-6">
                  <label class="block text-sm font-semibold mb-2 text-gray-700">Select Staff</label>
                  <select
                    v-model="assignments[selectedDay.format('YYYY-MM-DD')][selectedShiftIdx]"
                    @change="assignStaff(assignments[selectedDay.format('YYYY-MM-DD')][selectedShiftIdx])"
                    class="border-2 border-purple-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-200 rounded-lg px-3 py-2 w-full text-base transition shadow-sm outline-none"
                    :disabled="isValidating"
                    autofocus
                  >
                    <option value="">Select Staff</option>
                    <option v-for="staff in staffList" :key="staff.id" :value="staff.id">{{ staff.name }}</option>
                  </select>
                </div>
                <button @click="closeDayModal" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 rounded-lg transition" :disabled="isValidating">Cancel</button>
              </div>
            </div>
          </transition>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
.animate-fadeIn {
  animation: fadeIn 0.3s;
}
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.96); }
  to { opacity: 1; transform: scale(1); }
}
</style> 
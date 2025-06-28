<script setup>
import NavLink from "@/Components/NavLink.vue";
</script>

<template>
  <div class="space-x-8 rtl:space-x-reverse sm:flex">
    <NavLink :href="route('schedule.admin')" :active="route().current('schedule.admin')">
      Schedule
    </NavLink>
    <NavLink :href="route('schedule.assign-task')" :active="route().current('schedule.assign-task')">
      Assign Task
    </NavLink>
  </div>
</template>

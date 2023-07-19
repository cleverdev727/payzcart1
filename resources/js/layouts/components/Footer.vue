<script setup>
import { themeConfig } from '@themeConfig'
</script>

<template>
  <div class="h-100 d-flex align-center justify-space-between">
    <!-- 👉 Footer: left content -->
    <span class="d-flex align-center">
      &copy;
      {{ new Date().getFullYear() }}
      {{ themeConfig.app.title }}
    </span>
    <!-- 👉 Footer: right content -->
    <span class="d-md-flex gap-x-4 text-primary d-none">
      All Rights Reserved.
    </span>
  </div>
</template>

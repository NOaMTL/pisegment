<script setup lang="ts">
import { X, CheckCircle2, AlertCircle, AlertTriangle, Info } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const { toasts, removeToast } = useToast()

const getIcon = (type: string) => {
  switch (type) {
    case 'success': return CheckCircle2
    case 'error': return AlertCircle
    case 'warning': return AlertTriangle
    case 'info': return Info
    default: return Info
  }
}

const getColorClass = (type: string) => {
  switch (type) {
    case 'success': return 'border-green-500 bg-green-50 text-green-900 dark:bg-green-950 dark:text-green-50'
    case 'error': return 'border-red-500 bg-red-50 text-red-900 dark:bg-red-950 dark:text-red-50'
    case 'warning': return 'border-yellow-500 bg-yellow-50 text-yellow-900 dark:bg-yellow-950 dark:text-yellow-50'
    case 'info': return 'border-blue-500 bg-blue-50 text-blue-900 dark:bg-blue-950 dark:text-blue-50'
    default: return 'border-border bg-background'
  }
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-md">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="flex items-start gap-3 p-4 rounded-lg border-2 shadow-lg animate-in slide-in-from-top-5"
          :class="getColorClass(toast.type)"
        >
          <component :is="getIcon(toast.type)" class="h-5 w-5 flex-shrink-0 mt-0.5" />
          <div class="flex-1 min-w-0">
            <div v-if="toast.title" class="font-semibold mb-1">{{ toast.title }}</div>
            <div class="text-sm">{{ toast.message }}</div>
          </div>
          <button
            @click="removeToast(toast.id)"
            class="flex-shrink-0 opacity-70 hover:opacity-100 transition-opacity"
          >
            <X class="h-4 w-4" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100px);
}
</style>

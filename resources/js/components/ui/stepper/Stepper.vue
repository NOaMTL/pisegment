<script setup lang="ts">
import { computed } from 'vue'
import { Check } from 'lucide-vue-next'

interface Step {
  id: number
  label: string
  description?: string
}

interface Props {
  steps: Step[]
  currentStep: number
  allowNavigation?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  allowNavigation: false
})

const emit = defineEmits<{
  'update:currentStep': [value: number]
}>()

const getStepStatus = (stepId: number) => {
  if (stepId < props.currentStep) return 'completed'
  if (stepId === props.currentStep) return 'current'
  return 'upcoming'
}

const canNavigateToStep = (stepId: number) => {
  return props.allowNavigation && (stepId <= props.currentStep || getStepStatus(stepId) === 'completed')
}

const goToStep = (stepId: number) => {
  if (canNavigateToStep(stepId)) {
    emit('update:currentStep', stepId)
  }
}
</script>

<template>
  <nav aria-label="Progress" class="mb-8">
    <ol role="list" class="flex items-center justify-center gap-4">
      <li 
        v-for="(step, index) in steps" 
        :key="step.id"
        class="flex items-center"
        :class="{ 'flex-1 max-w-xs': index < steps.length - 1 }"
      >
        <div class="flex items-center gap-3">
          <!-- Step indicator -->
          <div class="flex items-center justify-center relative">
            <button
              type="button"
              class="h-10 w-10 rounded-full flex items-center justify-center font-semibold transition-all"
              :class="{
                'bg-primary text-primary-foreground': getStepStatus(step.id) === 'current',
                'bg-primary/20 text-primary': getStepStatus(step.id) === 'completed',
                'bg-muted text-muted-foreground': getStepStatus(step.id) === 'upcoming',
                'cursor-pointer hover:bg-primary hover:text-primary-foreground': canNavigateToStep(step.id) && step.id !== currentStep,
                'cursor-default': !canNavigateToStep(step.id) || step.id === currentStep
              }"
              :disabled="!canNavigateToStep(step.id) || step.id === currentStep"
              @click="goToStep(step.id)"
            >
              <Check 
                v-if="getStepStatus(step.id) === 'completed'" 
                class="h-5 w-5" 
              />
              <span v-else>{{ step.id }}</span>
            </button>
          </div>

          <!-- Step label -->
          <div class="flex flex-col min-w-0">
            <span 
              class="text-sm font-semibold transition-colors"
              :class="{
                'text-foreground': getStepStatus(step.id) === 'current',
                'text-muted-foreground': getStepStatus(step.id) !== 'current'
              }"
            >
              {{ step.label }}
            </span>
            <span 
              v-if="step.description" 
              class="text-xs text-muted-foreground"
            >
              {{ step.description }}
            </span>
          </div>
        </div>

        <!-- Connector line -->
        <div 
          v-if="index < steps.length - 1"
          class="flex-1 h-0.5 mx-4 transition-colors"
          :class="{
            'bg-primary/30': getStepStatus(step.id) === 'completed',
            'bg-muted': getStepStatus(step.id) !== 'completed'
          }"
        />
      </li>
    </ol>
  </nav>
</template>

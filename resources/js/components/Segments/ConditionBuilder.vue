<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { X, Trash2, Plus, AlertCircle, Copy, Sparkles, Hash, Type, ToggleLeft, List, Lock, LockOpen } from 'lucide-vue-next'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,  
  TooltipTrigger,
} from '@/components/ui/tooltip'

interface Condition {
  field: string
  operator: string
  value: any
  value_max?: any
  field_label: string
  field_type: string
  field_options?: string[]
  field_operators?: Array<{value: string, label: string}>
  editable?: boolean
}

interface ConditionGroup {
  logical_operator: 'AND' | 'OR'
  conditions: Condition[]
  next_operator?: 'AND' | 'OR'
}

interface Props {
  modelValue: ConditionGroup[]
  activeGroupIndex?: number
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: ConditionGroup[]]
  update: []
  'select-group': [groupIndex: number]
  'add-condition': [groupIndex: number]
}>()

const groupRefs = ref<HTMLElement[]>([])
const isInitialLoad = ref(true)

// Watch for new groups being added and scroll to them
watch(() => props.modelValue.length, async (newLength, oldLength) => {
  // Skip auto-scroll on initial load
  if (isInitialLoad.value) {
    isInitialLoad.value = false
    return
  }
  
  if (newLength > oldLength && oldLength !== undefined) {
    // A new group was added, scroll to it
    await nextTick()
    const lastGroupIndex = newLength - 1
    if (groupRefs.value[lastGroupIndex]) {
      groupRefs.value[lastGroupIndex].scrollIntoView({ 
        behavior: 'smooth', 
        block: 'center' 
      })
    }
  }
})
const operators = {
  number: [
    { value: '=', label: 'est égal à' },
    { value: '!=', label: 'est différent de' },
    { value: '>', label: 'est supérieur à' },
    { value: '>=', label: 'est supérieur ou égal à' },
    { value: '<', label: 'est inférieur à' },
    { value: '<=', label: 'est inférieur ou égal à' },
    { value: 'between', label: 'est entre' },
  ],
  text: [
    { value: '=', label: 'est égal à' },
    { value: '!=', label: 'est différent de' },
    { value: 'contains', label: 'contient' },
    { value: 'not_contains', label: 'ne contient pas' },
  ],
  boolean: [
    { value: '=', label: 'est' },
  ],
  select: [
    { value: '=', label: 'est égal à' },
    { value: '!=', label: 'est différent de' },
  ],
  multi_select: [
    { value: 'in', label: 'est dans' },
    { value: 'not_in', label: "n'est pas dans" },
  ],
}

const getOperators = (condition: Condition) => {
  // Use field-specific operators if available
  if (condition.field_operators && condition.field_operators.length > 0) {
    return condition.field_operators
  }
  // Fallback to default operators by type
  return operators[condition.field_type as keyof typeof operators] || operators.text
}

const removeCondition = (groupIndex: number, conditionIndex: number) => {
  const newGroups = [...props.modelValue]
  newGroups[groupIndex].conditions.splice(conditionIndex, 1)
  emit('update:modelValue', newGroups)
  emit('update')
}

const removeGroup = (groupIndex: number) => {
  if (props.modelValue.length > 1) {
    const newGroups = [...props.modelValue]
    newGroups.splice(groupIndex, 1)
    emit('update:modelValue', newGroups)
    emit('update')
  }
}



const duplicateGroup = async (groupIndex: number) => {
  const newGroups = [...props.modelValue]
  const groupToDuplicate = {
    ...newGroups[groupIndex],
    conditions: newGroups[groupIndex].conditions.map(c => ({ ...c }))
  }
  newGroups.splice(groupIndex + 1, 0, groupToDuplicate)
  emit('update:modelValue', newGroups)
  emit('update')
  
  // Scroll to the duplicated group
  await nextTick()
  const duplicatedGroupIndex = groupIndex + 1
  if (groupRefs.value[duplicatedGroupIndex]) {
    groupRefs.value[duplicatedGroupIndex].scrollIntoView({ 
      behavior: 'smooth', 
      block: 'center' 
    })
  }
}

const updateCondition = (groupIndex: number, conditionIndex: number, field: string, value: any) => {
  console.log(`ConditionBuilder: updateCondition called - group ${groupIndex}, condition ${conditionIndex}, field: ${field}, value:`, value)
  
  const newGroups = props.modelValue.map((group, gIdx) => 
    gIdx === groupIndex 
      ? {
          ...group,
          conditions: group.conditions.map((cond, cIdx) => 
            cIdx === conditionIndex
              ? { ...cond, [field]: value }
              : cond
          )
        }
      : group
  )
  
  console.log('ConditionBuilder: Updated groups:', newGroups)
  console.log('ConditionBuilder: Updated condition editable?', newGroups[groupIndex].conditions[conditionIndex].editable)
  
  emit('update:modelValue', newGroups)
  emit('update')
}

const updateGroupOperator = (groupIndex: number, operator: 'AND' | 'OR') => {
  const newGroups = [...props.modelValue]
  newGroups[groupIndex].logical_operator = operator
  emit('update:modelValue', newGroups)
  emit('update')
}

const updateGroupNextOperator = (groupIndex: number, operator: 'AND' | 'OR') => {
  const newGroups = [...props.modelValue]
  newGroups[groupIndex].next_operator = operator
  emit('update:modelValue', newGroups)
  emit('update')
}

const hasAnyConditions = computed(() => {
  return props.modelValue.some(group => group.conditions.length > 0)
})

const hasAnyGroups = computed(() => {
  return props.modelValue && props.modelValue.length > 0
})

const isConditionValid = (condition: Condition): boolean => {
  if (condition.value === null || condition.value === undefined || condition.value === '') {
    return false
  }
  if (condition.field_type === 'number' && isNaN(Number(condition.value))) {
    return false
  }
  return true
}

const getValidationMessage = (condition: Condition): string => {
  if (condition.value === null || condition.value === undefined || condition.value === '') {
    return 'Veuillez saisir une valeur'
  }
  if (condition.field_type === 'number' && isNaN(Number(condition.value))) {
    return 'Veuillez saisir un nombre valide'
  }
  return ''
}

const getFieldIcon = (fieldType: string) => {
  switch (fieldType) {
    case 'number': return Hash
    case 'text': return Type
    case 'boolean': return ToggleLeft
    case 'select': return List
    case 'multi_select': return List
    default: return Type
  }
}

const getFieldTypeLabel = (fieldType: string): string => {
  switch (fieldType) {
    case 'number': return 'Nombre'
    case 'text': return 'Texte'
    case 'boolean': return 'Oui/Non'
    case 'select': return 'Choix'
    case 'multi_select': return 'Choix multiples'
    default: return fieldType
  }
}

const isBetweenOperator = (operator: string): boolean => {
  return operator === 'between'
}
</script>

<template>
  <div class="space-y-6">
    <div v-if="!hasAnyGroups" class="text-center py-20 space-y-6">
      <div class="flex justify-center">
        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center shadow-lg ring-4 ring-primary/10">
          <Sparkles class="h-10 w-10 text-primary" />
        </div>
      </div>
      <div class="space-y-3">
        <h3 class="text-xl font-semibold text-foreground">Créez votre premier segment</h3>
        <p class="text-sm text-muted-foreground max-w-md mx-auto leading-relaxed">
          Commencez par ajouter un groupe de conditions pour cibler vos clients.
          Vous pourrez ensuite affiner avec plusieurs critères.
        </p>
      </div>
    </div>

    <TransitionGroup name="group" tag="div" class="space-y-6">
      <div 
        v-for="(group, groupIndex) in modelValue" 
        :key="`group-${groupIndex}`" 
        :ref="(el) => { if (el) groupRefs[groupIndex] = el as HTMLElement }"
        class="space-y-4"
      >
        <!-- Group Header with AND/OR selector and delete button -->
        <Card 
          @click="emit('select-group', groupIndex)"
          :class="[
            'shadow-md hover:shadow-lg transition-all duration-300 border-2',
            groupIndex % 2 === 0 ? 'bg-gradient-to-br from-background to-muted/20' : 'bg-gradient-to-br from-background to-accent/10'
          ]"
        >
          <CardHeader class="pb-4 bg-muted/30 border-b">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <span class="text-sm font-semibold text-foreground px-2 py-1 rounded bg-primary/10">Groupe {{ groupIndex + 1 }}</span>
                <Select 
                  :model-value="group.logical_operator"
                  @update:model-value="(v) => updateGroupOperator(groupIndex, v as 'AND' | 'OR')"
                >
                  <SelectTrigger class="w-[120px] h-8">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="AND">ET</SelectItem>
                    <SelectItem value="OR">OU</SelectItem>
                  </SelectContent>
                </Select>
                <span class="text-xs text-muted-foreground">entre les conditions</span>
              </div>
              <div class="flex items-center gap-2">
                <Button 
                  variant="ghost" 
                  size="sm"
                  @click="duplicateGroup(groupIndex)"
                  class="hover:bg-primary/10 hover:text-primary transition-colors"
                >
                  <Copy class="h-4 w-4 mr-1" />
                  Dupliquer
                </Button>
                <Button 
                  v-if="modelValue.length > 1"
                  variant="ghost" 
                  size="sm"
                  @click="removeGroup(groupIndex)"
                  class="hover:bg-destructive/10 hover:text-destructive transition-colors"
                >
                  <Trash2 class="h-4 w-4 mr-1" />
                  Supprimer groupe
                </Button>
              </div>
            </div>
          </CardHeader>

          <CardContent class="space-y-4">
            <div v-if="group.conditions.length === 0" class="text-center py-8 px-4 text-sm text-muted-foreground bg-muted/30 rounded-lg border-2 border-dashed border-muted-foreground/20">
              <div class="font-medium">Ce groupe est vide</div>
              <div class="text-xs mt-1">Ajoutez des conditions pour définir votre segment</div>
            </div>

            <TransitionGroup name="condition" tag="div" class="space-y-3">
              <div v-for="(condition, conditionIndex) in group.conditions" :key="`condition-${groupIndex}-${conditionIndex}`" class="space-y-0">
              
              <div class="flex items-center gap-2">
                <!-- AND/OR badge at the start of the line (except first condition) -->
                <div class="w-16 flex justify-center flex-shrink-0">
                  <Badge 
                    v-if="conditionIndex > 0" 
                    variant="outline" 
                    class="font-bold text-xs py-0.5 px-2 bg-gradient-to-r from-primary/5 to-accent/5 shadow-sm border-2"
                  >
                    {{ group.logical_operator === 'AND' ? 'ET' : 'OU' }}
                  </Badge>
                </div>

                <div class="flex-1 flex items-center gap-2">
                  <!-- Field label badge with icon -->
                  <div class="flex items-center gap-2 px-3 py-2.5 rounded-lg border-2 border-primary/30 bg-gradient-to-r from-primary/10 to-primary/5 min-w-[200px] shadow-sm">
                    <component :is="getFieldIcon(condition.field_type)" class="h-4 w-4 text-primary flex-shrink-0" />
                    <div class="flex-1 min-w-0">
                      <div class="font-semibold text-sm truncate text-foreground">{{ condition.field_label }}</div>
                      <!-- <div class="text-xs text-muted-foreground">{{ getFieldTypeLabel(condition.field_type) }}</div> -->
                    </div>
                  </div>

                  <!-- Operator selector for number and text -->
                  <Select 
                    v-if="condition.field_type === 'number' || condition.field_type === 'text'"
                    :model-value="condition.operator"
                    @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'operator', v)"
                  >
                    <SelectTrigger class="w-[180px]">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem 
                        v-for="op in getOperators(condition)" 
                        :key="op.value" 
                        :value="op.value"
                      >
                        {{ op.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>

                  <!-- Value input for number/text with between support -->
                  <div v-if="condition.field_type === 'number' || condition.field_type === 'text'" class="flex-1 flex gap-2">
                    <div class="relative" :class="isBetweenOperator(condition.operator) ? 'flex-1' : 'w-full'">
                      <Input 
                        :model-value="condition.value"
                        @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'value', v)"
                        :type="condition.field_type === 'number' ? 'number' : 'text'"
                        :class="!isConditionValid(condition) ? 'border-destructive pr-8' : ''"
                        :placeholder="isBetweenOperator(condition.operator) ? 'Min' : ''"
                        class="w-full"
                      />
                      <TooltipProvider v-if="!isConditionValid(condition) && !isBetweenOperator(condition.operator)">
                        <Tooltip>
                          <TooltipTrigger as-child>
                            <AlertCircle class="h-4 w-4 text-destructive absolute right-2 top-1/2 -translate-y-1/2" />
                          </TooltipTrigger>
                          <TooltipContent>
                            {{ getValidationMessage(condition) }}
                          </TooltipContent>
                        </Tooltip>
                      </TooltipProvider>
                    </div>
                    
                    <!-- Second input for between operator -->
                    <div v-if="isBetweenOperator(condition.operator)" class="relative flex-1">
                      <Input 
                        :model-value="condition.value_max"
                        @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'value_max', v)"
                        :type="condition.field_type === 'number' ? 'number' : 'text'"
                        placeholder="Max"
                        class="w-full"
                      />
                    </div>
                  </div>

                  <!-- Operator selector for boolean -->
                  <Select 
                    v-if="condition.field_type === 'boolean'"
                    :model-value="condition.operator"
                    @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'operator', v)"
                  >
                    <SelectTrigger class="w-[120px]">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem 
                        v-for="op in getOperators(condition)" 
                        :key="op.value" 
                        :value="op.value"
                      >
                        {{ op.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>

                  <!-- Value selector for boolean -->
                  <div v-if="condition.field_type === 'boolean'" class="flex-1">
                    <Select 
                      :model-value="String(condition.value)"
                      @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'value', v === 'true')"
                    >
                      <SelectTrigger class="w-full">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="true">Oui</SelectItem>
                        <SelectItem value="false">Non</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Operator selector for select -->
                  <Select 
                    v-if="condition.field_type === 'select'"
                    :model-value="condition.operator"
                    @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'operator', v)"
                  >
                    <SelectTrigger class="w-[160px]">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem 
                        v-for="op in getOperators(condition)" 
                        :key="op.value" 
                        :value="op.value"
                      >
                        {{ op.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>

                  <!-- Value selector for select -->
                  <div v-if="condition.field_type === 'select'" class="flex-1">
                    <Select 
                      :model-value="condition.value"
                      @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'value', v)"
                    >
                      <SelectTrigger class="w-full">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem 
                          v-for="option in condition.field_options" 
                          :key="option" 
                          :value="option"
                        >
                          {{ option }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Operator selector for multi_select -->
                  <Select 
                    v-if="condition.field_type === 'multi_select'"
                    :model-value="condition.operator"
                    @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'operator', v)"
                  >
                    <SelectTrigger class="w-[160px]">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem 
                        v-for="op in getOperators(condition)" 
                        :key="op.value" 
                        :value="op.value"
                      >
                        {{ op.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>

                  <!-- Value selector for multi_select -->
                  <div v-if="condition.field_type === 'multi_select'" class="flex-1">
                    <Select 
                      :model-value="condition.value?.[0]"
                      @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'value', [v])"
                    >
                      <SelectTrigger class="w-full">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem 
                          v-for="option in condition.field_options" 
                          :key="option" 
                          :value="option"
                        >
                          {{ option }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Editable lock icon -->
                  <div class="flex items-center gap-1 border-l-2 pl-2 border-muted">
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger as-child>
                          <Button 
                            variant="ghost" 
                            size="icon"
                            @click="updateCondition(groupIndex, conditionIndex, 'editable', !(condition.editable ?? false))"
                            :class="[
                              'transition-colors',
                              condition.editable 
                                ? 'text-green-600 hover:text-green-700 hover:bg-green-100 dark:hover:bg-green-950' 
                                : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                            ]"
                          >
                            <LockOpen v-if="condition.editable" class="h-4 w-4" />
                            <Lock v-else class="h-4 w-4" />
                          </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                          {{ condition.editable ? 'Modifiable par les agents' : 'Non modifiable par les agents' }}
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  </div>

                  <div class="flex items-center gap-1">
                    <Button 
                      variant="ghost" 
                      size="icon"
                      @click="removeCondition(groupIndex, conditionIndex)"
                      class="hover:bg-destructive/10 hover:text-destructive transition-colors"
                    >
                      <X class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>
            </TransitionGroup>

            <!-- Button to add condition at bottom of group -->
            <div class="pt-4 border-t-2 mt-4 border-muted/50">
              <Button 
                @click.stop="emit('add-condition', groupIndex)"
                class="w-full bg-primary text-primary-foreground hover:bg-primary/90 transition-all duration-200 font-medium shadow-sm"
              >
                <Plus class="mr-2 h-4 w-4" />
                Ajouter une condition
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Operator selector between groups -->
        <div 
          v-if="groupIndex < modelValue.length - 1" 
          class="flex justify-center py-3"
        >
          <div class="relative">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-primary/10 to-transparent"></div>
            <Select 
              :model-value="group.next_operator || 'AND'"
              @update:model-value="(v) => updateGroupNextOperator(groupIndex, v as 'AND' | 'OR')"
            >
              <SelectTrigger class="w-[140px] h-12 border-2 bg-background shadow-md font-bold relative z-10 hover:border-primary/50 transition-colors">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="AND">ET</SelectItem>
                <SelectItem value="OR">OU</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
/* Group animations */
.group-enter-active,
.group-leave-active {
  transition: all 0.4s ease;
}

.group-enter-from {
  opacity: 0;
  transform: translateY(30px) scale(0.95);
}

.group-leave-to {
  opacity: 0;
  transform: translateY(-30px) scale(0.95);
}

.group-move {
  transition: transform 0.4s ease;
}

/* Condition animations */
.condition-enter-active,
.condition-leave-active {
  transition: all 0.3s ease;
}

.condition-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.condition-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

.condition-move {
  transition: transform 0.3s ease;
}
</style>

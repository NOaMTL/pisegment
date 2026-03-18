<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'
import { X, Trash2 } from 'lucide-vue-next'

interface Condition {
  field: string
  operator: string
  value: any
  field_label: string
  field_type: string
  field_options?: string[]
  editable?: boolean
}

interface ConditionGroup {
  logical_operator: 'AND' | 'OR'
  conditions: Condition[]
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
}>()

const operators = {
  number: [
    { value: '=', label: 'est égal à' },
    { value: '!=', label: 'est différent de' },
    { value: '>', label: 'est supérieur à' },
    { value: '>=', label: 'est supérieur ou égal à' },
    { value: '<', label: 'est inférieur à' },
    { value: '<=', label: 'est inférieur ou égal à' },
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
  multi_select: [
    { value: 'in', label: 'est dans' },
    { value: 'not_in', label: "n'est pas dans" },
  ],
}

const getOperators = (fieldType: string) => {
  return operators[fieldType as keyof typeof operators] || operators.text
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

const hasAnyConditions = computed(() => {
  return props.modelValue.some(group => group.conditions.length > 0)
})
</script>

<template>
  <div class="space-y-4">
    <div v-if="!hasAnyConditions" class="text-center py-12 text-muted-foreground">
      <Card>
        <CardContent class="pt-6">
          Sélectionnez des filtres à gauche pour commencer
        </CardContent>
      </Card>
    </div>

    <template v-else>
      <div v-for="(group, groupIndex) in modelValue" :key="groupIndex" class="space-y-4">
        <!-- Group Header with AND/OR selector and delete button -->
        <Card 
          class="border-2 transition-all cursor-pointer"
          :class="{
            'border-primary bg-primary/5 shadow-md': activeGroupIndex === groupIndex,
            'hover:border-primary/50': activeGroupIndex !== groupIndex
          }"
          @click="emit('select-group', groupIndex)"
        >
          <CardHeader class="pb-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-muted-foreground">Groupe {{ groupIndex + 1 }}</span>
                <Badge 
                  v-if="activeGroupIndex === groupIndex" 
                  variant="default"
                  class="text-xs"
                >
                  ACTIF
                </Badge>
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
              <Button 
                v-if="modelValue.length > 1"
                variant="ghost" 
                size="sm"
                @click="removeGroup(groupIndex)"
              >
                <Trash2 class="h-4 w-4 mr-1" />
                Supprimer groupe
              </Button>
            </div>
          </CardHeader>

          <CardContent class="space-y-4">
            <div v-if="group.conditions.length === 0" class="text-center py-4 text-sm text-muted-foreground">
              Ce groupe est vide
            </div>

            <div v-for="(condition, conditionIndex) in group.conditions" :key="conditionIndex" class="space-y-3">
              <div class="flex items-start gap-2">
                <div class="flex-1 space-y-2">
                  <div class="flex items-center gap-2">
                    <Select 
                      :model-value="condition.field" 
                      disabled
                    >
                      <SelectTrigger class="w-[200px]">
                        <SelectValue>{{ condition.field_label }}</SelectValue>
                      </SelectTrigger>
                    </Select>

                    <Select 
                      :model-value="condition.operator"
                      @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'operator', v)"
                    >
                      <SelectTrigger class="w-[220px]">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem 
                          v-for="op in getOperators(condition.field_type)" 
                          :key="op.value" 
                          :value="op.value"
                        >
                          {{ op.label }}
                        </SelectItem>
                      </SelectContent>
                    </Select>

                    <Input 
                      v-if="condition.field_type === 'number' || condition.field_type === 'text'"
                      :model-value="condition.value"
                      @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'value', v)"
                      :type="condition.field_type === 'number' ? 'number' : 'text'"
                      class="flex-1"
                    />

                    <Select 
                      v-else-if="condition.field_type === 'boolean'"
                      :model-value="String(condition.value)"
                      @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'value', v === 'true')"
                    >
                      <SelectTrigger class="flex-1">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="true">Oui</SelectItem>
                        <SelectItem value="false">Non</SelectItem>
                      </SelectContent>
                    </Select>

                    <Select 
                      v-else-if="condition.field_type === 'multi_select'"
                      :model-value="condition.value?.[0]"
                      @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'value', [v])"
                    >
                      <SelectTrigger class="flex-1">
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

                    <Button 
                      variant="ghost" 
                      size="icon"
                      @click="removeCondition(groupIndex, conditionIndex)"
                    >
                      <X class="h-4 w-4" />
                    </Button>
                  </div>

                  <!-- Editable checkbox -->
                  <div class="flex items-center space-x-2 ml-2">
                    <Checkbox 
                      :id="`editable-${groupIndex}-${conditionIndex}`"
                      :checked="condition.editable ?? false"
                      @update:checked="(checked: boolean | 'indeterminate') => {
                        console.log('Checkbox update:checked event fired!', checked)
                        if (checked === 'indeterminate') return
                        updateCondition(groupIndex, conditionIndex, 'editable', checked)
                      }"
                      @click="(e: Event) => {
                        console.log('Checkbox clicked!', e)
                      }"
                    />
                    <label 
                      class="text-sm text-muted-foreground cursor-pointer select-none"
                      @click="() => {
                        console.log('Label clicked! Current value:', condition.editable)
                        const newValue = !(condition.editable ?? false)
                        console.log('Setting to:', newValue)
                        updateCondition(groupIndex, conditionIndex, 'editable', newValue)
                      }"
                    >
                      Modifiable par les agents
                    </label>
                  </div>
                </div>
              </div>

              <!-- Logical operator badge between conditions -->
              <div 
                v-if="conditionIndex < group.conditions.length - 1" 
                class="flex justify-center"
              >
                <Badge variant="outline" class="font-semibold">
                  {{ group.logical_operator }}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- AND badge between groups -->
        <div 
          v-if="groupIndex < modelValue.length - 1" 
          class="flex justify-center py-2"
        >
          <Badge variant="default" class="text-lg px-4 py-1 font-bold">
            ET
          </Badge>
        </div>
      </div>
    </template>
  </div>
</template>

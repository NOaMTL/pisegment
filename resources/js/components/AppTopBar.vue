<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Bell, Settings } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import AppLogoIcon from '@/components/AppLogoIcon.vue'
import UserMenuContent from '@/components/UserMenuContent.vue'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { dashboard } from '@/routes'

const page = usePage()
const user = computed(() => page.props.auth?.user)

const userInitials = computed(() => {
  if (!user.value?.name) return '?'
  return user.value.name
    .split(' ')
    .map((n: string) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})
</script>

<template>
  <header
    class="fixed top-0 left-0 right-0 z-50 h-14 text-white"
    style="background-color: #0a0a0a; border-bottom: 1px solid #0a0a0a;"
  >
    <div class="flex h-full items-center justify-between px-4">
      <!-- Logo et titre -->
      <div class="flex items-center gap-3">
        <Link :href="dashboard()" class="flex items-center gap-2 hover:opacity-80 transition-opacity text-white">
          <AppLogoIcon class="h-6 w-6 text-white" />
          <span class="font-semibold text-lg hidden sm:inline-block">PI Segment</span>
        </Link>
      </div>

      <!-- Actions de droite -->
      <div class="flex items-center gap-2">
        <!-- Notifications -->
        <Button variant="ghost" size="icon" class="relative hover:bg-slate-800 text-white">
          <Bell class="h-5 w-5" />
          <Badge
            variant="destructive"
            class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center p-0 text-xs"
          >
            3
          </Badge>
        </Button>

        <!-- Paramètres -->
        <Link href="/settings/profile">
          <Button variant="ghost" size="icon" class="hover:bg-slate-800 text-white">
            <Settings class="h-5 w-5" />
          </Button>
        </Link>

        <!-- Menu utilisateur -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button
              variant="ghost"
              class="relative h-9 w-9 rounded-full hover:bg-slate-800"
            >
              <Avatar class="h-9 w-9 bg-slate-700">
                <AvatarFallback class="text-xs bg-slate-700 text-white">
                  {{ userInitials }}
                </AvatarFallback>
              </Avatar>
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-56">
            <UserMenuContent v-if="user" :user="user" />
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  </header>
</template>

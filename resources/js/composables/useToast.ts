import { ref } from 'vue'

export interface Toast {
  id: string
  title?: string
  message: string
  type: 'success' | 'error' | 'warning' | 'info'
  duration?: number
}

const toasts = ref<Toast[]>([])

let toastCounter = 0

export function useToast() {
  const addToast = (toast: Omit<Toast, 'id'>) => {
    const id = `toast-${++toastCounter}-${Date.now()}`
    const newToast: Toast = {
      id,
      duration: 3000,
      ...toast,
    }

    toasts.value.push(newToast)

    if (newToast.duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, newToast.duration)
    }

    return id
  }

  const removeToast = (id: string) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  const success = (message: string, title?: string) => {
    return addToast({ message, title, type: 'success' })
  }

  const error = (message: string, title?: string) => {
    return addToast({ message, title, type: 'error', duration: 5000 })
  }

  const warning = (message: string, title?: string) => {
    return addToast({ message, title, type: 'warning', duration: 4000 })
  }

  const info = (message: string, title?: string) => {
    return addToast({ message, title, type: 'info' })
  }

  return {
    toasts,
    addToast,
    removeToast,
    success,
    error,
    warning,
    info,
  }
}

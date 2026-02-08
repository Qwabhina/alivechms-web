import { ref } from 'vue';

export type NotificationType = 'success' | 'error' | 'warning' | 'info';

interface NotificationOptions {
  message: string;
  type: NotificationType;
  duration?: number;
}

const notifications = ref<NotificationOptions[]>([]);

export function useNotification() {
  function show(message: string, type: NotificationType = 'info', duration: number = 3000) {
    const notification: NotificationOptions = { message, type, duration };
    notifications.value.push(notification);

    if (duration > 0) {
      setTimeout(() => {
        remove(notification);
      }, duration);
    }
  }

  function remove(notification: NotificationOptions) {
    const index = notifications.value.indexOf(notification);
    if (index > -1) {
      notifications.value.splice(index, 1);
    }
  }

  function success(message: string, duration?: number) {
    show(message, 'success', duration);
  }

  function error(message: string, duration?: number) {
    show(message, 'error', duration);
  }

  function warning(message: string, duration?: number) {
    show(message, 'warning', duration);
  }

  function info(message: string, duration?: number) {
    show(message, 'info', duration);
  }

  return {
    notifications,
    show,
    remove,
    success,
    error,
    warning,
    info,
  };
}

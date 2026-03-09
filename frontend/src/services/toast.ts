
export interface ToastMessage {
  severity: 'success' | 'info' | 'warn' | 'error' | 'secondary' | 'contrast';
  summary: string;
  detail?: string;
  life?: number;
  group?: string;
}

export const toast = {
  add(message: ToastMessage) {
    const event = new CustomEvent('show-toast', { detail: message });
    document.dispatchEvent(event);
  },
  
  success(summary: string, detail?: string, life = 3000) {
    this.add({ severity: 'success', summary, detail, life });
  },

  info(summary: string, detail?: string, life = 3000) {
    this.add({ severity: 'info', summary, detail, life });
  },

  warn(summary: string, detail?: string, life = 3000) {
    this.add({ severity: 'warn', summary, detail, life });
  },

  error(summary: string, detail?: string, life = 5000) {
    this.add({ severity: 'error', summary, detail, life });
  }
};

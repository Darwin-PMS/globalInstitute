/**
 * Toast Notification System
 */

export class ToastSystem {
    constructor() {
        this.container = document.getElementById('learning-toast-container');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'learning-toast-container';
            this.container.className = 'fixed top-5 right-5 z-50 flex flex-col gap-3 max-w-sm pointer-events-none';
            document.body.appendChild(this.container);
        }
    }

    show(message, type = 'info', title = '') {
        const toast = document.createElement('div');
        toast.className = `pointer-events-auto p-4 rounded-xl shadow-2xl border backdrop-blur-lg flex items-start gap-3 transform transition-all duration-300 translate-x-12 opacity-0 text-slate-100 ${this.getTypeStyles(type)}`;

        const iconName = this.getIcon(type);

        toast.innerHTML = `
            <span class="material-symbols-outlined text-xl flex-shrink-0 mt-0.5">${iconName}</span>
            <div class="flex-1 min-w-0">
                ${title ? `<h4 class="font-semibold text-sm leading-snug">${title}</h4>` : ''}
                <p class="text-xs opacity-90 leading-relaxed">${message}</p>
            </div>
            <button class="toast-close text-slate-400 hover:text-white transition-colors p-1 -mr-1 -mt-1 rounded-lg">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        `;

        this.container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-12', 'opacity-0');
        });

        // Close button handler
        toast.querySelector('.toast-close').addEventListener('click', () => {
            this.dismiss(toast);
        });

        // Auto dismiss after 4.5 seconds
        setTimeout(() => {
            this.dismiss(toast);
        }, 4500);
    }

    dismiss(toast) {
        if (!toast || !toast.parentNode) return;
        toast.classList.add('translate-x-12', 'opacity-0');
        setTimeout(() => {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 300);
    }

    getTypeStyles(type) {
        switch (type) {
            case 'success':
                return 'bg-emerald-950/90 border-emerald-500/30 text-emerald-100';
            case 'error':
                return 'bg-rose-950/90 border-rose-500/30 text-rose-100';
            case 'warning':
                return 'bg-amber-950/90 border-amber-500/30 text-amber-100';
            default:
                return 'bg-slate-900/90 border-slate-700 text-slate-100';
        }
    }

    getIcon(type) {
        switch (type) {
            case 'success': return 'check_circle';
            case 'error': return 'error';
            case 'warning': return 'warning';
            default: return 'info';
        }
    }

    success(message, title = 'Success') { this.show(message, 'success', title); }
    error(message, title = 'Error') { this.show(message, 'error', title); }
    warning(message, title = 'Warning') { this.show(message, 'warning', title); }
    info(message, title = 'Notice') { this.show(message, 'info', title); }
}

export const toast = new ToastSystem();
window.toast = toast;

/**
 * Reusable Right-Side Slide-Over Drawer Component
 */

export class Drawer {
    constructor(options = {}) {
        this.title = options.title || '';
        this.subtitle = options.subtitle || '';
        this.contentHTML = options.contentHTML || '';
        this.onClose = options.onClose || null;
        this.size = options.size || 'md'; // md = 500px, lg = 650px, xl = 800px
        this.element = null;
    }

    render() {
        const sizeClasses = {
            sm: 'max-w-md',
            md: 'max-w-xl',
            lg: 'max-w-2xl',
            xl: 'max-w-4xl'
        }[this.size] || 'max-w-xl';

        const wrapper = document.createElement('div');
        wrapper.className = 'fixed inset-0 z-50 overflow-hidden flex justify-end';

        wrapper.innerHTML = `
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity opacity-0 drawer-backdrop"></div>
            <div class="relative w-full ${sizeClasses} bg-slate-900 border-l border-slate-800 shadow-2xl flex flex-col h-full transform transition-transform translate-x-full drawer-container z-10">
                <div class="flex items-center justify-between px-6 py-5 border-b border-slate-800 bg-slate-900/90">
                    <div>
                        <h3 class="text-lg font-bold text-slate-100">${this.title}</h3>
                        ${this.subtitle ? `<p class="text-xs text-slate-400 mt-0.5">${this.subtitle}</p>` : ''}
                    </div>
                    <button class="drawer-close text-slate-400 hover:text-white p-2 rounded-lg hover:bg-slate-800 transition-colors">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-6 space-y-6 text-slate-200 drawer-body">
                    ${this.contentHTML}
                </div>
            </div>
        `;

        document.body.appendChild(wrapper);
        this.element = wrapper;
        const backdrop = wrapper.querySelector('.drawer-backdrop');
        const container = wrapper.querySelector('.drawer-container');

        requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            container.classList.remove('translate-x-full');
        });

        wrapper.querySelector('.drawer-close').addEventListener('click', () => this.close());
        backdrop.addEventListener('click', () => this.close());

        this.keyHandler = (e) => {
            if (e.key === 'Escape') this.close();
        };
        document.addEventListener('keydown', this.keyHandler);

        return this;
    }

    setContent(html) {
        if (this.element) {
            const body = this.element.querySelector('.drawer-body');
            if (body) body.innerHTML = html;
        }
    }

    close() {
        if (!this.element) return;
        const backdrop = this.element.querySelector('.drawer-backdrop');
        const container = this.element.querySelector('.drawer-container');

        backdrop.classList.add('opacity-0');
        container.classList.add('translate-x-full');

        document.removeEventListener('keydown', this.keyHandler);

        setTimeout(() => {
            if (this.element && this.element.parentNode) {
                this.element.parentNode.removeChild(this.element);
            }
            if (this.onClose) this.onClose();
        }, 300);
    }
}

/**
 * Accessible Modal Component
 */

export class Modal {
    constructor(options = {}) {
        this.title = options.title || '';
        this.contentHTML = options.contentHTML || '';
        this.size = options.size || 'md'; // sm, md, lg, xl
        this.onClose = options.onClose || null;
        this.element = null;
        this.backdrop = null;
    }

    render() {
        const sizeClasses = {
            sm: 'max-w-md',
            md: 'max-w-lg',
            lg: 'max-w-2xl',
            xl: 'max-w-4xl'
        }[this.size] || 'max-w-lg';

        const wrapper = document.createElement('div');
        wrapper.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto';

        wrapper.innerHTML = `
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity opacity-0 modal-backdrop"></div>
            <div class="relative bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl w-full ${sizeClasses} transform transition-all scale-95 opacity-0 modal-container overflow-hidden z-10 my-8">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-900/90">
                    <h3 class="text-lg font-semibold text-slate-100">${this.title}</h3>
                    <button class="modal-close text-slate-400 hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-800 transition-colors">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[75vh] text-slate-200 modal-body">
                    ${this.contentHTML}
                </div>
            </div>
        `;

        document.body.appendChild(wrapper);
        this.element = wrapper;
        this.backdrop = wrapper.querySelector('.modal-backdrop');
        const container = wrapper.querySelector('.modal-container');

        // Animate in
        requestAnimationFrame(() => {
            this.backdrop.classList.remove('opacity-0');
            container.classList.remove('scale-95', 'opacity-0');
        });

        // Event listeners
        const closeBtn = wrapper.querySelector('.modal-close');
        closeBtn.addEventListener('click', () => this.close());
        this.backdrop.addEventListener('click', () => this.close());

        this.keyHandler = (e) => {
            if (e.key === 'Escape') this.close();
        };
        document.addEventListener('keydown', this.keyHandler);

        return this;
    }

    setContent(html) {
        if (this.element) {
            const body = this.element.querySelector('.modal-body');
            if (body) body.innerHTML = html;
        }
    }

    close() {
        if (!this.element) return;
        const container = this.element.querySelector('.modal-container');
        this.backdrop.classList.add('opacity-0');
        container.classList.add('scale-95', 'opacity-0');

        document.removeEventListener('keydown', this.keyHandler);

        setTimeout(() => {
            if (this.element && this.element.parentNode) {
                this.element.parentNode.removeChild(this.element);
            }
            if (this.onClose) this.onClose();
        }, 200);
    }
}

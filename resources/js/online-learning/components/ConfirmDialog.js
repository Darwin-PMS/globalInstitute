import { Modal } from './Modal.js';

export function confirmAction(options = {}) {
    return new Promise((resolve) => {
        const title = options.title || 'Are you sure?';
        const message = options.message || 'This action cannot be undone.';
        const confirmText = options.confirmText || 'Confirm';
        const cancelText = options.cancelText || 'Cancel';
        const isDangerous = options.isDangerous !== false;

        const contentHTML = `
            <div class="space-y-4">
                <p class="text-xs text-slate-300 leading-relaxed">${message}</p>
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" id="confirm-dialog-cancel" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition-colors">
                        ${cancelText}
                    </button>
                    <button type="button" id="confirm-dialog-submit" class="px-4 py-2 ${isDangerous ? 'bg-rose-600 hover:bg-rose-500 shadow-rose-600/20' : 'bg-indigo-600 hover:bg-indigo-500 shadow-indigo-600/20'} text-white rounded-xl text-xs font-semibold shadow-lg transition-all">
                        ${confirmText}
                    </button>
                </div>
            </div>
        `;

        const modal = new Modal({
            title,
            contentHTML,
            size: 'sm',
            onClose: () => resolve(false)
        }).render();

        const submitBtn = modal.element.querySelector('#confirm-dialog-submit');
        const cancelBtn = modal.element.querySelector('#confirm-dialog-cancel');

        submitBtn.addEventListener('click', () => {
            modal.close();
            resolve(true);
        });

        cancelBtn.addEventListener('click', () => {
            modal.close();
            resolve(false);
        });
    });
}

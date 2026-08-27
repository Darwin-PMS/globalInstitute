import { classesApi } from '../../api/classes.js';
import { renderClassCard } from '../../components/ClassCard.js';
import { getCardsSkeletonHTML } from '../../components/LoadingState.js';
import { getEmptyStateHTML } from '../../components/EmptyState.js';

export class StudentClassesPage {
    constructor() {
        this.container = document.getElementById('learning-content');
        this.classes = [];
    }

    async render() {
        if (!this.container) return;

        this.container.innerHTML = getCardsSkeletonHTML(6);

        try {
            const res = await classesApi.getStudentClasses();
            this.classes = res.data || [];

            this.container.innerHTML = `
                <div class="space-y-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-100">Live Classes Schedule</h2>
                        <p class="text-xs text-slate-400">View upcoming and previous live online class sessions for your courses.</p>
                    </div>

                    ${this.classes.length > 0 ? `
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            ${this.classes.map(c => renderClassCard(c)).join('')}
                        </div>
                    ` : getEmptyStateHTML({
                        title: 'No classes scheduled',
                        description: 'There are no active or scheduled live classes found.'
                    })}
                </div>
            `;

            this.container.addEventListener('click', async (evt) => {
                const btn = evt.target.closest('[data-action="join-class"]');
                if (btn) {
                    const cId = parseInt(btn.dataset.id, 10);
                    try {
                        const res = await classesApi.joinClass(cId);
                        if (res.meeting_url) {
                            window.toast.success('Joining class room...');
                            window.open(res.meeting_url, '_blank');
                        }
                    } catch (err) {
                        window.toast.error(err.message || 'Unable to join class.');
                    }
                }
            });
        } catch (err) {
            console.error('Failed to load student classes:', err);
        }
    }
}

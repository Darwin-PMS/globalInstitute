/* ============================================
   GLOBAL INSTITUTE & CAD CAM - SPA Application
   Vanilla JS Single Page Application
   ============================================ */

(function () {
    'use strict';

    /* ------------------------------------------
       Data Store - API Cache
       ------------------------------------------ */
    const API_BASE = (() => {
        if (window.location.port === '8000') return '';
        const b = document.querySelector('base');
        const root = b ? b.href.replace(/\/$/, '') : window.location.origin;
        return root + '/global_institute_laravel/public';
    })();
    const DataStore = {
        courses: null, testimonials: null, faqs: null, gallery: null, settings: null,
        async fetchCourses() {
            if (this.courses) return this.courses;
            try { const r = await fetch(`${API_BASE}/api/courses`); const j = await r.json(); this.courses = j.data || []; } catch(e) { this.courses = []; }
            return this.courses;
        },
        async fetchTestimonials() {
            if (this.testimonials) return this.testimonials;
            try { const r = await fetch(`${API_BASE}/api/testimonials`); const j = await r.json(); this.testimonials = j.data || []; } catch(e) { this.testimonials = []; }
            return this.testimonials;
        },
        async fetchFaqs() {
            if (this.faqs) return this.faqs;
            try { const r = await fetch(`${API_BASE}/api/faqs`); const j = await r.json(); this.faqs = j.data || []; } catch(e) { this.faqs = []; }
            return this.faqs;
        },
        async fetchGallery() {
            if (this.gallery) return this.gallery;
            try { const r = await fetch(`${API_BASE}/api/gallery`); const j = await r.json(); this.gallery = j.data || []; } catch(e) { this.gallery = []; }
            return this.gallery;
        },
        async fetchSettings() {
            if (this.settings) return this.settings;
            try { const r = await fetch(`${API_BASE}/api/settings`); const j = await r.json(); this.settings = j.data || {}; } catch(e) { this.settings = {}; }
            return this.settings;
        }
    };

    /* ------------------------------------------
       SPA Router
       ------------------------------------------ */
    const Router = {
        routes: {},
        currentRoute: null,

        init() {
            window.addEventListener('hashchange', () => this.resolve());
            window.addEventListener('load', () => this.resolve());
            document.addEventListener('click', (e) => {
                const link = e.target.closest('[data-link]');
                if (link) {
                    const href = link.getAttribute('href');
                    if (href && href.startsWith('#')) {
                        this.closeMobileNav();
                    }
                }
            });
        },

        addRoute(path, handler) {
            this.routes[path] = handler;
            return this;
        },

        resolve() {
            const hash = window.location.hash.slice(1) || '/';
            const route = hash.split('?')[0];

            if (this.routes[route]) {
                this.currentRoute = route;
                this.updateActiveLinks(route);
                this.transitionTo(() => this.routes[route]());
            } else if (route.startsWith('/courses/') && route !== '/courses') {
                const slug = route.split('/courses/')[1];
                this.currentRoute = route;
                this.updateActiveLinks('/courses');
                this.transitionTo(() => Pages.courseDetail({ slug }));
            } else if (this.routes['/']) {
                this.currentRoute = '/404';
                this.updateActiveLinks('/');
                this.transitionTo(() => Pages.notFound());
            }

            this.closeMobileNav();
        },

        transitionTo(renderFn) {
            const app = document.getElementById('app');
            app.classList.add('page-exit');
            setTimeout(() => {
                renderFn();
                app.classList.remove('page-exit');
                app.classList.add('page-enter');
                setTimeout(() => app.classList.remove('page-enter'), 200);
                if (window.LazyLoad) LazyLoad.init();
            }, 150);
        },

        updateActiveLinks(route) {
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
                const page = link.dataset.page;
                if (page) {
                    if (route === '/' && page === 'home') link.classList.add('active');
                    else if (route.startsWith('/' + page)) link.classList.add('active');
                }
            });

            document.querySelectorAll('.mobile-nav-link').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + route) {
                    link.classList.add('active');
                }
            });
        },

        scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        closeMobileNav() {
            document.getElementById('mobile-nav-overlay')?.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    /* ------------------------------------------
       Toast Notifications
       ------------------------------------------ */
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const icon = type === 'success' ? 'check_circle' : 'error';
        toast.innerHTML = `<span class="material-symbols-outlined">${icon}</span><span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }

    /* ------------------------------------------
       Modal
       ------------------------------------------ */
    function openEnquiryModal() {
        document.getElementById('enquiry-modal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeEnquiryModal() {
        document.getElementById('enquiry-modal').classList.remove('active');
        document.body.style.overflow = '';
    }

    /* ------------------------------------------
       FAQ Accordion
       ------------------------------------------ */
    function initFAQ() {
        document.querySelectorAll('.faq-question').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = btn.closest('.faq-item');
                const isOpen = item.classList.contains('open');
                document.querySelectorAll('.faq-item.open').forEach(i => i.classList.remove('open'));
                if (!isOpen) item.classList.add('open');
            });
        });
    }

    /* ------------------------------------------
       Mobile Navigation
       ------------------------------------------ */
    function initMobileNav() {
        const menuBtn = document.getElementById('mobile-menu-btn');
        const overlay = document.getElementById('mobile-nav-overlay');
        const closeBtn = document.getElementById('mobile-close-btn');

        menuBtn?.addEventListener('click', () => {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        closeBtn?.addEventListener('click', () => {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });

        overlay?.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }

    /* ------------------------------------------
       Course Filter
       ------------------------------------------ */
    function initCourseFilter() {
        const searchInput = document.querySelector('.course-search-input');
        const checkboxes = document.querySelectorAll('.category-filter');
        const cards = document.querySelectorAll('.course-filter-card');

        if (!searchInput || !cards.length) return;

        function filterCourses() {
            const search = searchInput.value.toLowerCase();
            const activeCategories = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.dataset.category);

            cards.forEach(card => {
                const title = (card.dataset.title || '').toLowerCase();
                const category = card.dataset.category || '';
                const matchSearch = !search || title.includes(search);
                const matchCategory = activeCategories.length === 0 || activeCategories.includes('all') || activeCategories.includes(category);
                card.style.display = (matchSearch && matchCategory) ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', filterCourses);
        checkboxes.forEach(cb => cb.addEventListener('change', filterCourses));
    }

    /* ------------------------------------------
       Page Renderers
       ------------------------------------------ */

    const Pages = {

        /* ---- 404 ---- */
        notFound() {
            const app = document.getElementById('app');
            app.innerHTML = `
                <div style="min-height:60vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:80px 24px;">
                    <span class="material-symbols-outlined" style="font-size:80px;color:var(--outline-variant);margin-bottom:24px;">search_off</span>
                    <h1 style="font-family:var(--font-headline);font-size:32px;color:var(--primary);margin-bottom:8px;">Page Not Found</h1>
                    <p style="color:var(--on-surface-variant);margin-bottom:32px;max-width:400px;">The page you're looking for doesn't exist or has been moved.</p>
                    <a href="#/" class="btn btn-primary" data-link>Back to Home</a>
                </div>`;
        },

        /* ---- HOME ---- */
        async home() {
            const [courses, testimonials, faqs] = await Promise.all([
                DataStore.fetchCourses(), DataStore.fetchTestimonials(), DataStore.fetchFaqs()
            ]);
            const fallbackImg = 'https://lh3.googleusercontent.com/aida-public/AB6AXuBLj2Kf6ZGZV8LDP7C5AUQhYyWTGQ1WeyyNvZFpLFeB0pg6JYPV3k4axq4Ov1HnzPxEJ-FVySO3WtGfEkn5X2Lz63LbxAPlIqis9wbqsc0X9KSbPH8_ElkwjUO0nN2JvbeTBLW09tgZE3MFpUOQclq7n3KmOl9szN7KTLtDK3XzfNJsofgblkdEVC6kipAg5I7dFDxfMRqYEQR9pjl0HvMha6kom_xDZs5ArfZ0ocyXa6XPDTILWon7WQ';
            const featuredHtml = (courses.length ? courses.slice(0, 3) : [
                {title:'AutoCAD Masterclass',category_slug:'cad-cam',short_description:'Comprehensive training in 2D drafting and 3D modeling.',duration:'6 Weeks',level:'Beginner',image:fallbackImg,slug:'autocad-masterclass'},
                {title:'SolidWorks Professional',category_slug:'cad-cam',short_description:'Advanced parametric modeling and simulation.',duration:'8 Weeks',level:'Intermediate',image:'https://lh3.googleusercontent.com/aida-public/AB6AXuAUEcOJIvpM1N3s9EtbS36uUfpUrp0qDbTKybdcQ4TvT5L-6WwKkSIB7ZJaLq_tH5Ts2vuUzgkEp0gsiJe3h9BGpLZPKjbi7Fyg8ZUs6NoRlDRCFbe53zXS3nyJ2RC-uWqg8ORxNZ0u188WLUbrjIt-pRBD44Mn192Ef8gxR4yj0eeQMEVFsU5j66vY4qdSVbF3nTIfsWhcCLFmHvLyiHLZRXJs3D4V4Xb3Xh0EKyu7MeWmLaFW_AI9ng',slug:'solidworks-professional'},
                {title:'CNC/VMC Programming',category_slug:'cnc',short_description:'Hands-on G-code and M-code programming for VMC.',duration:'3 Months',level:'Advanced',image:'https://lh3.googleusercontent.com/aida-public/AB6AXuA6zw1FYrzMP34yVaZOjU7ggUYyO7pg_c_YuHKoagvM3msiXT-g_mh5s-xv9ZaA0Y04_bNPkouwOJnk71NYK75C3ec0NwJyUNgOmuGPpGSCk0ANBPihmym80hg9DsexWLGNuw7J0UoG7ISiymUw-Xo-kkNCSZNG31Q7ftft8O3DOvjVKCrCIdkABfwiDDmnDYd82YjkigsM6QpmeZOLs1xRg9j_XTdQqLZ8lc_vuu3uCFOH4qhOd8jOkQ',slug:'cnc-vmc-programming'}
            ]).map(c => Pages._courseCard(c.title, c.category_slug || 'cad-cam', c.short_description || c.description?.substring(0,100) || '', [c.duration || '6 Weeks', c.level || 'Beginner'].filter(Boolean), c.image || fallbackImg)).join('');
            const testimonialHtml = (testimonials.length ? testimonials : [
                {student_name:'Rahul Sharma',content:'The SolidWorks course completely transformed my career. I got placed within 2 months.',designation:'SolidWorks Professional'},
                {student_name:'Anita Desai',content:'Hands-on CNC training with real machines. The faculty understands what industry needs.',designation:'CNC/VMC Programming'},
                {student_name:'Vikram Singh',content:'Best institute in Jamshedpur for CAD/CAM training. World-class infrastructure.',designation:'AutoCAD Masterclass'}
            ]).map(t => `<div class="testimonial-card"><div style="color:var(--tertiary);font-size:18px;margin-bottom:12px;">&#9733;&#9733;&#9733;&#9733;&#9733;</div><p class="testimonial-text">"${t.content}"</p><div class="testimonial-author"><div class="testimonial-avatar">${(t.student_name||'S').charAt(0)}</div><div><div class="testimonial-name">${t.student_name}</div><div class="testimonial-course">${t.designation||''}</div></div></div></div>`).join('');
            const faqHtml = (faqs.length ? faqs.slice(0,5) : [
                {question:'What courses do you offer?',answer:'We offer AutoCAD, SolidWorks, CATIA V5, NX CAD, CNC/VMC Programming, and Industrial Training programs.'},
                {question:'Is there a hostel facility?',answer:'Yes, we provide separate hostel facilities for boys and girls with strict security and mess facilities.'},
                {question:'Do you provide placement assistance?',answer:'Yes, we provide dedicated placement assistance connecting graduates with leading employers.'},
                {question:'What is the eligibility criteria?',answer:'Minimum 10th Standard pass with Science & Mathematics. Age limit: 16 to 24 years.'}
            ]).map((f,i) => `<div class="faq-item" data-faq="${i}"><button class="faq-question" onclick="toggleFaq(${i})"><span>${f.question}</span><span class="faq-icon"><span class="material-symbols-outlined" style="font-size:20px;">expand_more</span></span></button><div class="faq-answer"><div class="faq-answer-inner">${f.answer}</div></div></div>`).join('');
            const app = document.getElementById('app');
            app.innerHTML = `
                <!-- Hero Section -->
                <section class="hero blueprint-dark" style="min-height:auto;padding:100px 0 60px;">
                    <div class="container" style="display:flex;flex-direction:column;gap:32px;align-items:center;text-align:center;position:relative;z-index:10;">
                        <div class="hero-content" style="display:flex;flex-direction:column;gap:var(--space-lg);align-items:center;text-align:center;">
                            <div class="hero-badge" style="background:rgba(255,255,255,0.1);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);border-radius:999px;padding:8px 20px;display:inline-flex;align-items:center;gap:10px;font-family:var(--font-label);font-size:var(--text-label);text-transform:uppercase;letter-spacing:0.05em;color:var(--secondary-fixed);width:fit-content;">
                                <span style="width:8px;height:8px;border-radius:50%;background:var(--secondary-fixed);animation:pulse 2s infinite;display:inline-block;"></span>
                                Industrial Technical Training
                            </div>
                            <h1 class="hero-title" style="font-family:var(--font-display);font-size:var(--text-display-lg-mobile);line-height:var(--lh-display-mobile);font-weight:700;color:var(--primary-fixed-dim);max-width:800px;">Build Job-Ready Technical Skills for Your Future</h1>
                            <p class="hero-desc" style="font-size:var(--text-body-lg);line-height:var(--lh-body-lg);color:var(--on-primary-container);max-width:600px;">Master industry-standard CAD/CAM, CNC, and industrial skills through hands-on training with expert faculty and modern equipment.</p>
                            <div class="hero-actions" style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
                                <a href="#/courses" class="btn btn-secondary" data-link>Explore Courses</a>
                                <a href="#/contact" class="btn btn-outline-light" data-link>Enquire Now</a>
                            </div>
                        </div>
                        <div class="hero-image-frame" style="position:relative;max-width:600px;width:100%;display:none;">
                            <div style="position:absolute;top:-16px;right:-16px;width:80px;height:80px;border:3px solid var(--tertiary);opacity:0.6;transform:rotate(12deg);border-radius:8px;z-index:0;"></div>
                            <div class="hero-image-inner" style="position:relative;z-index:1;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:8px;overflow:hidden;">
                                <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuCythYlnJpGxwRhHLon3XUJ-K4Wa05-lM4_FG6EdjnN0ksxHBdABnavkHz6BAtTljSNUov-9fxxzn2DUKf0DT9VCpx7BU7wV_yJnENjwcOSSY9UIrjTXkg7dwwanjdr6soUa-yHg9cezWv8a6B5a39TGTVJkU1d_IEl_ukUJxT9OxKZzAEpACRKrcNDRGLE9LqL54QurPhyVELLJL_cCRxlC928q_II-9q3JqjoDG4QBvgdipYW3SzQxA" alt="Student with CNC machine" style="border-radius:8px;width:100%;display:block;"/>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Trust Bar -->
                <section class="trust-bar">
                    <div class="trust-items">
                        <div class="trust-item"><span class="material-symbols-outlined">school</span> 500+ Students Trained</div>
                        <div class="trust-item"><span class="material-symbols-outlined">engineering</span> Industry-Ready Curriculum</div>
                        <div class="trust-item"><span class="material-symbols-outlined">workspace_premium</span> Certified Programs</div>
                        <div class="trust-item"><span class="material-symbols-outlined">handshake</span> Placement Assistance</div>
                        <div class="trust-item"><span class="material-symbols-outlined">location_on</span> Gamharia, Jamshedpur</div>
                    </div>
                </section>

                <!-- Training Categories -->
                <section class="section">
                    <div class="container">
                        <div style="border-left:3px solid var(--secondary);padding-left:16px;margin-bottom:32px;">
                            <div style="font-family:var(--font-label);font-size:12px;letter-spacing:0.05em;text-transform:uppercase;color:var(--secondary);display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <span class="material-symbols-outlined" style="font-size:16px;">category</span> Our Domains
                            </div>
                            <h2 style="font-family:var(--font-headline);font-size:28px;font-weight:600;color:var(--primary);">Training Categories</h2>
                        </div>
                        <div class="grid-4" style="margin-top:var(--space-lg);">
                            <div class="category-card" onclick="location.hash='#/courses'">
                                <span class="material-symbols-outlined">precision_manufacturing</span>
                                <h3>CAD/CAM</h3>
                                <p>AutoCAD, SolidWorks, CATIA, NX CAD</p>
                            </div>
                            <div class="category-card" onclick="location.hash='#/industrial-training'">
                                <span class="material-symbols-outlined">settings</span>
                                <h3>CNC Programming</h3>
                                <p>CNC, VMC, HMC Operations</p>
                            </div>
                            <div class="category-card" onclick="location.hash='#/industrial-training'">
                                <span class="material-symbols-outlined">build</span>
                                <h3>Industrial Skills</h3>
                                <p>Fabrication, Sheet Metal, Reverse Engineering</p>
                            </div>
                            <div class="category-card" onclick="location.hash='#/courses'">
                                <span class="material-symbols-outlined">computer</span>
                                <h3>Computer Skills</h3>
                                <p>MS Office, Tally, Programming</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Featured Courses -->
                <section class="section" style="background:var(--surface-container-low);">
                    <div class="container">
                        <div style="border-left:3px solid var(--secondary);padding-left:16px;margin-bottom:32px;">
                            <div style="font-family:var(--font-label);font-size:12px;letter-spacing:0.05em;text-transform:uppercase;color:var(--secondary);display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <span class="material-symbols-outlined" style="font-size:16px;">star</span> Most Popular
                            </div>
                            <h2 style="font-family:var(--font-headline);font-size:28px;font-weight:600;color:var(--primary);">Featured Courses</h2>
                        </div>
                        <div class="grid-3" style="margin-top:var(--space-lg);">
                            ${featuredHtml}
                        </div>
                        <div style="text-align:center; margin-top:var(--space-lg);">
                            <a href="#/courses" class="btn btn-outline" data-link>View All Courses</a>
                        </div>
                    </div>
                </section>

                <!-- Why Choose Us -->
                <section class="section">
                    <div class="container">
                        <div style="border-left:3px solid var(--secondary);padding-left:16px;margin-bottom:32px;">
                            <div style="font-family:var(--font-label);font-size:12px;letter-spacing:0.05em;text-transform:uppercase;color:var(--secondary);display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <span class="material-symbols-outlined" style="font-size:16px;">verified</span> Why Us
                            </div>
                            <h2 style="font-family:var(--font-headline);font-size:28px;font-weight:600;color:var(--primary);">Why Choose Global Institute?</h2>
                        </div>
                        <div class="grid-2" style="margin-top:var(--space-lg);">
                            <div class="why-card">
                                <div class="why-card-icon"><span class="material-symbols-outlined">factory</span></div>
                                <div><h3>Shop-Floor Simulation</h3><p>Training in environments mirroring modern production facilities, not standard classrooms.</p></div>
                            </div>
                            <div class="why-card">
                                <div class="why-card-icon"><span class="material-symbols-outlined">verified</span></div>
                                <div><h3>Industry Standard Tools</h3><p>Learn on the exact software and hardware used by top-tier employers.</p></div>
                            </div>
                            <div class="why-card">
                                <div class="why-card-icon"><span class="material-symbols-outlined">groups</span></div>
                                <div><h3>Expert Faculty</h3><p>Learn from industry professionals with real-world manufacturing experience.</p></div>
                            </div>
                            <div class="why-card">
                                <div class="why-card-icon"><span class="material-symbols-outlined">work</span></div>
                                <div><h3>Placement Assistance</h3><p>Dedicated career support connecting graduates with leading employers.</p></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Testimonials -->
                <section class="section" style="background:var(--surface-container-low);">
                    <div class="container">
                        <div style="border-left:3px solid var(--secondary);padding-left:16px;margin-bottom:32px;text-align:left;">
                            <div style="font-family:var(--font-label);font-size:12px;letter-spacing:0.05em;text-transform:uppercase;color:var(--secondary);display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <span class="material-symbols-outlined" style="font-size:16px;">reviews</span> Testimonials
                            </div>
                            <h2 style="font-family:var(--font-headline);font-size:28px;font-weight:600;color:var(--primary);">What Our Students Say</h2>
                        </div>
                        <div class="grid-3" style="margin-top:var(--space-lg);">
                            ${testimonialHtml}
                        </div>
                    </div>
                </section>

                <!-- FAQs -->
                <section class="section">
                    <div class="container" style="max-width:800px;">
                        <div style="border-left:3px solid var(--secondary);padding-left:16px;margin-bottom:32px;text-align:left;">
                            <div style="font-family:var(--font-label);font-size:12px;letter-spacing:0.05em;text-transform:uppercase;color:var(--secondary);display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <span class="material-symbols-outlined" style="font-size:16px;">help</span> FAQs
                            </div>
                            <h2 style="font-family:var(--font-headline);font-size:28px;font-weight:600;color:var(--primary);">Frequently Asked Questions</h2>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:12px;margin-top:var(--space-lg);">
                            ${faqHtml}
                        </div>
                    </div>
                </section>

                <!-- CTA Section -->
                <section class="section cta-section">
                    <div class="blueprint-dark" style="position:absolute;inset:0;opacity:0.3;"></div>
                    <div class="container">
                        <div class="cta-inner">
                            <h2 class="section-title" style="margin-bottom:var(--space-md);">Ready to Start Your Technical Career?</h2>
                            <p class="section-desc">Join the premier institution for advanced industrial training in Jamshedpur. Limited seats available for 2025 batch.</p>
                            <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-top:var(--space-lg);">
                                <a href="#/admissions" class="btn btn-secondary btn-lg" data-link>Apply Now</a>
                                <a href="#/contact" class="btn btn-outline-light btn-lg" data-link>Contact Us</a>
                            </div>
                        </div>
                    </div>
                </section>
            `;
            initFAQ();
        },

        /* ---- COURSES ---- */
        async courses() {
            const courses = await DataStore.fetchCourses();
            const fallbackImg = 'https://lh3.googleusercontent.com/aida-public/AB6AXuBLj2Kf6ZGZV8LDP7C5AUQhYyWTGQ1WeyyNvZFpLFeB0pg6JYPV3k4axq4Ov1HnzPxEJ-FVySO3WtGfEkn5X2Lz63LbxAPlIqis9wbqsc0X9KSbPH8_ElkwjUO0nN2JvbeTBLW09tgZE3MFpUOQclq7n3KmOl9szN7KTLtDK3XzfNJsofgblkdEVC6kipAg5I7dFDxfMRqYEQR9pjl0HvMha6kom_xDZs5ArfZ0ocyXa6XPDTILWon7WQ';
            const gridHtml = courses.length
                ? courses.map(c => Pages._courseCard(c.title, c.category_slug || 'cad-cam', c.short_description || c.description?.substring(0,120) || '', [c.duration || '6 Weeks', c.level || 'Beginner'].filter(Boolean), c.image || fallbackImg)).join('')
                : ['AutoCAD Masterclass','SolidWorks Professional','CNC/VMC Programming','CATIA Aerospace Design','NX CAD Design','Industrial Automation'].map((t,i) => Pages._courseCard(t, i<4?'cad-cam':i===4?'cad-cam':'industrial', 'Comprehensive hands-on training program.', ['Hands-on','Certified'], fallbackImg)).join('');
            const app = document.getElementById('app');
            app.innerHTML = `
                <div class="container" style="padding-top:var(--space-section);padding-bottom:var(--space-section);">
                    <div class="page-header">
                        <h1>Our Courses</h1>
                        <p>Industry-standard CAD/CAM, CNC, and technical skill development programs</p>
                    </div>
                    <div class="courses-layout">
                        <!-- Sidebar -->
                        <aside class="courses-sidebar">
                            <div class="sidebar-card">
                                <label class="filter-label">Search Courses</label>
                                <div class="search-input-wrapper">
                                    <span class="material-symbols-outlined">search</span>
                                    <input type="text" class="form-input course-search-input" placeholder="Enter keyword..." style="padding-left:40px;"/>
                                </div>
                            </div>
                            <div class="sidebar-card">
                                <label class="filter-label">Categories</label>
                                <div class="filter-group">
                                    <label class="filter-item"><input type="checkbox" class="category-filter" data-category="all" checked/> All Courses</label>
                                    <label class="filter-item"><input type="checkbox" class="category-filter" data-category="cad-cam"/> CAD/CAM</label>
                                    <label class="filter-item"><input type="checkbox" class="category-filter" data-category="cnc"/> CNC</label>
                                    <label class="filter-item"><input type="checkbox" class="category-filter" data-category="industrial"/> Industrial</label>
                                </div>
                            </div>
                            <div class="lead-magnet-card">
                                <span class="material-symbols-outlined" style="font-size:40px;color:var(--secondary-fixed);margin-bottom:12px;">description</span>
                                <h3>Download Brochure</h3>
                                <p>Get complete details on curriculum, duration, and fees.</p>
                                <button class="btn btn-green btn-full" style="margin-top:16px;" onclick="showToast('Brochure download coming soon!')">Get PDF</button>
                            </div>
                            <div class="counselling-card">
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                    <span class="material-symbols-outlined" style="color:var(--secondary);">support_agent</span>
                                    <h3>Free Counselling</h3>
                                </div>
                                <p>Not sure which path? Speak to our experts.</p>
                                <button class="btn btn-outline btn-full" onclick="openEnquiryModal()">Book Call</button>
                            </div>
                        </aside>

                        <!-- Course Grid -->
                        <div class="courses-grid">
                            ${gridHtml}
                        </div>
                    </div>
                </div>
            `;
            initCourseFilter();
        },

        _courseCard(title, category, desc, tags, img) {
            const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-');
            const catLabel = category === 'cad-cam' ? 'CAD/CAM' : category === 'cnc' ? 'CNC' : 'Industrial';
            const tagHtml = tags.map(t => `<span class="course-tag">${t}</span>`).join('');
            const accentColor = category === 'cnc' ? 'var(--primary)' : category === 'industrial' ? 'var(--tertiary-container)' : 'var(--secondary-fixed)';
            const duration = category === 'cnc' ? '3 Months' : category === 'industrial' ? '2 Months' : '6 Weeks';
            const level = category === 'cnc' ? 'Advanced' : category === 'industrial' ? 'Intermediate' : 'Beginner';
            return `
                <div class="course-card course-filter-card" data-title="${title}" data-category="${category}" style="--card-accent:${accentColor};">
                    <div class="course-card-image">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='250'%3E%3Crect fill='%23f1f3f5' width='400' height='250'/%3E%3C/svg%3E" data-src="${img}" alt="${title}" loading="lazy" decoding="async" class="lazy-img"/>
                        <span class="course-card-badge" style="background:${accentColor};color:${category === 'cnc' ? 'var(--on-primary)' : 'var(--primary)'};">${catLabel}</span>
                        <div class="course-card-image-overlay"></div>
                    </div>
                    <div class="course-card-body">
                        <h3 class="course-card-title">${title}</h3>
                        <p class="course-card-desc">${desc}</p>
                        <div class="course-card-tags">${tagHtml}</div>
                        <div class="course-card-meta">
                            <div class="course-meta-item">
                                <span class="material-symbols-outlined">schedule</span> ${duration}
                            </div>
                            <div class="course-meta-item">
                                <span class="material-symbols-outlined">signal_cellular_alt</span> ${level}
                            </div>
                            <div class="course-meta-item">
                                <span class="material-symbols-outlined">location_on</span> In-Person
                            </div>
                        </div>
                        <div class="course-card-actions">
                            <a href="#/courses/${slug}" class="btn btn-primary btn-sm" data-link style="flex:1;">View Details</a>
                            <button class="btn btn-outline btn-sm" style="flex:1;" onclick="event.stopPropagation();openEnquiryModal()">Enquire</button>
                        </div>
                    </div>
                </div>`;
        },

        /* ---- COURSE DETAIL ---- */
        async courseDetail(params) {
            const app = document.getElementById('app');
            const slug = params.slug || 'solidworks-professional';
            let courseData = Pages._getCourseData(slug);
            try {
                const res = await fetch(`${API_BASE}/api/courses/${slug}`);
                if (res.ok) { const j = await res.json(); if (j.data) courseData = { title: j.data.title, category: j.data.category || 'CAD/CAM', shortDesc: j.data.short_description || j.data.description?.substring(0,150) || '', desc: j.data.description || '', duration: j.data.duration || '6 Weeks', level: j.data.level || 'Beginner', fees: j.data.fees || 'Contact Us', mode: j.data.mode || 'In-Person', image: j.data.image }; }
            } catch(e) {}

            app.innerHTML = `
                <section class="course-hero">
                    <div class="blueprint-grid" style="position:absolute;inset:0;opacity:0.5;z-index:0;"></div>
                    <div class="container">
                        <div class="course-hero-grid" style="position:relative;z-index:10;">
                            <div style="display:flex;flex-direction:column;gap:var(--space-lg);">
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <span class="badge badge-primary">${courseData.category}</span>
                                    <span style="font-family:var(--font-label);font-size:12px;color:var(--secondary);display:flex;align-items:center;gap:4px;">
                                        <span class="material-symbols-outlined" style="font-size:16px;">verified</span> Industry Certified
                                    </span>
                                </div>
                                <h1 class="font-display-lg-mobile" style="font-family:var(--font-display);font-size:var(--text-display-lg-mobile);line-height:var(--lh-display-mobile);font-weight:700;color:var(--primary);">${courseData.title}</h1>
                                <p style="font-size:var(--text-body-lg);line-height:var(--lh-body-lg);color:var(--on-surface-variant);max-width:600px;">${courseData.shortDesc}</p>
                                <div class="course-meta-grid">
                                    <div class="course-meta-item"><span class="course-meta-label">Duration</span><span class="course-meta-value">${courseData.duration}</span></div>
                                    <div class="course-meta-item"><span class="course-meta-label">Level</span><span class="course-meta-value">${courseData.level}</span></div>
                                    <div class="course-meta-item"><span class="course-meta-label">Format</span><span class="course-meta-value">${courseData.format}</span></div>
                                    <div class="course-meta-item"><span class="course-meta-label">Batch</span><span class="course-meta-value">${courseData.batch}</span></div>
                                </div>
                                <div style="display:flex;gap:16px;flex-wrap:wrap;padding-top:8px;">
                                    <button class="btn btn-primary" onclick="showToast('Brochure download coming soon!')">Download Syllabus <span class="material-symbols-outlined" style="font-size:18px;">download</span></button>
                                    <button class="btn btn-outline" onclick="openEnquiryModal()">Request Demo</button>
                                </div>
                            </div>
                            <div style="position:relative;margin-top:48px;">
                                <div style="position:absolute;inset:-16px;background:var(--tertiary-fixed);opacity:0.2;border-radius:8px;transform:rotate(2deg);"></div>
                                <div style="background:var(--surface-container-lowest);border:1px solid var(--outline-variant);padding:8px;border-radius:8px;position:relative;z-index:10;box-shadow:var(--shadow-card);">
                                    <div class="lazy-bg" data-bg="${courseData.image||''}" style="background-size:cover;background-position:center;width:100%;aspect-ratio:16/9;border-radius:4px;border:1px solid var(--outline-variant);"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section style="padding:var(--space-section) 0;background:var(--surface-bright);">
                    <div class="container">
                        <div class="course-detail-layout">
                            <div class="course-content" style="display:flex;flex-direction:column;gap:var(--space-section);">
                                <div style="display:flex;flex-direction:column;gap:var(--space-md);">
                                    <h2><span class="material-symbols-outlined" style="color:var(--secondary);">info</span> Course Overview</h2>
                                    <p>${courseData.overview}</p>
                                </div>

                                <div style="display:flex;flex-direction:column;gap:var(--space-md);">
                                    <h2><span class="material-symbols-outlined" style="color:var(--secondary);">menu_book</span> Curriculum Highlights</h2>
                                    <div class="curriculum-grid">
                                        ${courseData.curriculum.map(c => `
                                            <div class="curriculum-card">
                                                <span class="material-symbols-outlined" style="font-size:32px;color:var(--primary);margin-bottom:12px;">${c.icon}</span>
                                                <h3>${c.title}</h3>
                                                <p>${c.desc}</p>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>

                                <div class="career-section">
                                    <h2 style="font-family:var(--font-headline);font-size:var(--text-headline-md);color:var(--primary);display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                                        <span class="material-symbols-outlined" style="color:var(--secondary);">work</span> Career Pathways
                                    </h2>
                                    <p style="color:var(--on-surface-variant);margin-bottom:16px;">${courseData.career}</p>
                                    <div class="career-tags">
                                        ${courseData.careerTags.map(t => `<span class="career-tag">${t}</span>`).join('')}
                                    </div>
                                </div>
                            </div>

                            <div class="course-sidebar">
                                <div class="fee-card">
                                    <div class="fee-label">Course Investment</div>
                                    <div class="fee-amount">Call for Details</div>
                                    <div style="border-bottom:1px solid var(--outline-variant);margin:16px 0;"></div>
                                    <ul class="fee-list">
                                        <li class="fee-list-item"><span class="material-symbols-outlined">check_circle</span> Includes Certification</li>
                                        <li class="fee-list-item"><span class="material-symbols-outlined">check_circle</span> 1-on-1 Mentorship Sessions</li>
                                        <li class="fee-list-item"><span class="material-symbols-outlined">check_circle</span> Lifetime Access to Resources</li>
                                    </ul>
                                    <button class="btn btn-primary btn-full" onclick="openEnquiryModal()">Enrol Now</button>
                                    <button class="btn btn-outline btn-full" style="margin-top:8px;" onclick="openEnquiryModal()">
                                        <span class="material-symbols-outlined" style="font-size:18px;">call</span> Request Callback
                                    </button>
                                </div>

                                <div class="enquiry-card">
                                    <h3><span class="material-symbols-outlined">mail</span> Quick Enquiry</h3>
                                    <form class="course-enquiry-form" style="display:flex;flex-direction:column;gap:12px;" onsubmit="submitCourseEnquiry(event, '${courseData.title}')">
                                        <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-input" placeholder="John Doe" required/></div>
                                        <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input" placeholder="john@example.com"/></div>
                                        <div class="form-group"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-input" placeholder="+91 00000 00000" required/></div>
                                        <button type="submit" class="btn btn-secondary btn-full">Submit Enquiry</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            `;
        },

        _getCourseData(slug) {
            const courses = {
                'autocad-masterclass': {
                    title: 'AutoCAD Masterclass', category: 'CAD/CAM', shortDesc: 'Comprehensive training in 2D drafting and 3D modeling for mechanical and civil engineering applications.',
                    duration: '8 Weeks', level: 'Beginner', format: 'In-Person', batch: 'Rolling',
                    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBLj2Kf6ZGZV8LDP7C5AUQhYyWTGQ1WeyyNvZFpLFeB0pg6JYPV3k4axq4Ov1HnzPxEJ-FVySO3WtGfEkn5X2Lz63LbxAPlIqis9wbqsc0X9KSbPH8_ElkwjUO0nN2JvbeTBLW09tgZE3MFpUOQclq7n3KmOl9szN7KTLtDK3XzfNJsofgblkdEVC6kipAg5I7dFDxfMRqYEQR9pjl0HvMha6kom_xDZs5ArfZ0ocyXa6XPDTILWon7WQ',
                    overview: 'The AutoCAD Masterclass provides comprehensive training in industry-standard 2D drafting and 3D modeling. Students learn to create precise technical drawings, generate 3D models, and produce professional documentation.',
                    curriculum: [{icon:'draw',title:'2D Drafting',desc:'Master layers, blocks, dimensions, and annotation.'},{icon:'view_in_ar',title:'3D Modeling',desc:'Create 3D solids, surfaces, and mesh objects.'},{icon:'palette',title:'Rendering',desc:'Generate photorealistic renders and presentations.'},{icon:'description',title:'Documentation',desc:'Prepare production-ready drawings and layouts.'}],
                    career: 'Proficiency in AutoCAD opens doors to careers in architecture, civil engineering, mechanical design, and interior design.',
                    careerTags: ['CAD Draughtsman','Design Engineer','Architecture Assistant','Interior Designer']
                },
                'solidworks-professional': {
                    title: 'SolidWorks Professional', category: 'CAD/CAM', shortDesc: 'Master advanced 3D parametric design, complex assembly modeling, and engineering drawings.',
                    duration: '12 Weeks', level: 'Intermediate', format: 'Hybrid', batch: 'Rolling',
                    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAUEcOJIvpM1N3s9EtbS36uUfpUrp0qDbTKybdcQ4TvT5L-6WwKkSIB7ZJaLq_tH5Ts2vuUzgkEp0gsiJe3h9BGpLZPKjbi7Fyg8ZUs6NoRlDRCFbe53zXS3nyJ2RC-uWqg8ORxNZ0u188WLUbrjIt-pRBD44Mn192Ef8gxR4yj0eeQMEVFsU5j66vY4qdSVbF3nTIfsWhcCLFmHvLyiHLZRXJs3D4V4Xb3Xh0EKyu7MeWmLaFW_AI9ng',
                    overview: 'The SolidWorks Professional course is designed for individuals who have a foundational understanding of 3D CAD and wish to elevate their skills to an industry-standard level. This intensive program covers advanced part modeling, complex surface creation, and intricate assembly design techniques.',
                    curriculum: [{icon:'precision_manufacturing',title:'Advanced Part Modeling',desc:'Master multi-body solids, sweeps, lofts, and advanced shaping techniques.'},{icon:'account_tree',title:'Complex Assemblies',desc:'Manage large assembly performance and create top-down design structures.'},{icon:'architecture',title:'Sheet Metal & Weldments',desc:'Design industry-standard sheet metal parts and generate flat patterns.'},{icon:'analytics',title:'Simulation Basics',desc:'Introduction to static analysis, stress, strain, and displacement.'}],
                    career: 'Proficiency in SolidWorks is a highly sought-after skill in manufacturing, automotive, aerospace, and product design sectors.',
                    careerTags: ['Mechanical Design Engineer','CAD Draftsman','Product Development Specialist','Tooling Engineer']
                },
                'cnc-vmc-programming': {
                    title: 'CNC/VMC Programming', category: 'CNC', shortDesc: 'Hands-on G-code and M-code programming for advanced Vertical Machining Centers.',
                    duration: '10 Weeks', level: 'Intermediate', format: 'In-Person', batch: 'Monthly',
                    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA6zw1FYrzMP34yVaZOjU7ggUYyO7pg_c_YuHKoagvM3msiXT-g_mh5s-xv9ZaA0Y04_bNPkouwOJnk71NYK75C3ec0NwJyUNgOmuGPpGSCk0ANBPihmym80hg9DsexWLGNuw7J0UoG7ISiymUw-Xo-kkNCSZNG31Q7ftft8O3DOvjVKCrCIdkABfwiDDmnDYd82YjkigsM6QpmeZOLs1xRg9j_XTdQqLZ8lc_vuu3uCFOH4qhOd8jOkQ',
                    overview: 'This course provides hands-on training in CNC and VMC operations, covering G-code and M-code programming, tool path generation, and machine setup procedures for modern manufacturing.',
                    curriculum: [{icon:'code',title:'G-Code Programming',desc:'Master CNC programming language and syntax.'},{icon:'settings',title:'VMC Operations',desc:'Learn Vertical Machining Center setup and operation.'},{icon:'precision_manufacturing',title:'Tool Selection',desc:'Understand cutting tools, speeds, feeds, and optimization.'},{icon:'analytics',title:'Quality Control',desc:'Measure and verify dimensional accuracy of machined parts.'}],
                    career: 'CNC programmers and operators are in high demand across automotive, aerospace, and general manufacturing industries.',
                    careerTags: ['CNC Programmer','VMC Operator','Tooling Engineer','Production Engineer']
                },
                'catia-aerospace-design': {
                    title: 'CATIA Aerospace Design', category: 'CAD/CAM', shortDesc: 'Industry-standard surface modeling and complex assembly design for automotive and aerospace.',
                    duration: '14 Weeks', level: 'Advanced', format: 'Hybrid', batch: 'Rolling',
                    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBjSNNFtUi4Le_7WgBc4DYtrFeL7SG0-mTi77YzIB1XN3T_p2t3t-JRCPj6mcptifBbZEFRjn0_yNdABqp61pLJclvaYlJMRtB7PFzbi6fi_xlKPcCbVq-msQJ6Akco6frgiXoY4Y8YjHvhwk0OaZ1Fh6L7oQJYZBrsyHis98un_MSnbsVpANaMRNpmzoqwCHzYkxEug1sM4JxaoneWlxzNDTV26P8ieT2sejv9UERan89HYwnoPlw1Nw',
                    overview: 'CATIA V5 is the leading CAD/CAM software in aerospace and automotive industries. This course covers advanced surface modeling, complex assembly design, and industry-specific workflows.',
                    curriculum: [{icon:'view_in_ar',title:'Surface Modeling',desc:'Create complex A-class and B-class surfaces.'},{icon:'flight',title:'Aerospace Assembly',desc:'Design multi-component aircraft assemblies.'},{icon:'architecture',title:'Generative Design',desc:'Use generative shape design for organic forms.'},{icon:'description',title:'Drawing Generation',desc:'Create manufacturing-ready engineering drawings.'}],
                    career: 'CATIA expertise is essential for aerospace engineers, automotive designers, and advanced manufacturing professionals.',
                    careerTags: ['Aerospace Designer','Automotive CAD Engineer','Surface Modeler','CATIA Specialist']
                },
                'nx-cad-design': {
                    title: 'NX CAD Design', category: 'CAD/CAM', shortDesc: 'Industry-grade parametric and surface modeling with Siemens NX for advanced manufacturing.',
                    duration: '12 Weeks', level: 'Advanced', format: 'Hybrid', batch: 'Rolling',
                    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBjSNNFtUi4Le_7WgBc4DYtrFeL7SG0-mTi77YzIB1XN3T_p2t3t-JRCPj6mcptifBbZEFRjn0_yNdABqp61pLJclvaYlJMRtB7PFzbi6fi_xlKPcCbVq-msQJ6Akco6frgiXoY4Y8YjHvhwk0OaZ1Fh6L7oQJYZBrsyHis98un_MSnbsVpANaMRNpmzoqwCHzYkxEug1sM4JxaoneWlxzNDTV26P8ieT2sejv9UERan89HYwnoPlw1Nw',
                    overview: 'Siemens NX is a leading CAD/CAM/CAE solution for product design and manufacturing. This course covers advanced parametric modeling, surface design, assembly management, and drafting within the NX environment.',
                    curriculum: [{icon:'precision_manufacturing',title:'Parametric Modeling',desc:'Create robust parametric parts using advanced feature-based modeling.'},{icon:'view_in_ar',title:'Surface Design',desc:'Master surface modeling techniques for complex geometries.'},{icon:'account_tree',title:'Assembly Design',desc:'Build and manage large-scale assemblies with top-down approaches.'},{icon:'description',title:'Drafting & Documentation',desc:'Generate production-ready 2D drawings from 3D models.'}],
                    career: 'NX CAD expertise is highly valued in automotive, aerospace, heavy machinery, and consumer product industries.',
                    careerTags: ['NX CAD Engineer','Design Specialist','Product Designer','Manufacturing Engineer']
                },
                'industrial-automation': {
                    title: 'Industrial Automation', category: 'Industrial', shortDesc: 'PLC programming, SCADA systems, and industrial electronics for modern smart factories.',
                    duration: '10 Weeks', level: 'Intermediate', format: 'In-Person', batch: 'Monthly',
                    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAC_4z3HyHv-lMeeYlR7UKdaIdR2jUTq5iZFOgwHVZoBjKjajfHFESGtTno5jvvrBmk-SxWr4nHnqqTGl3UTNi1r_WioRmmSA0JlmIsxy2DOwgsSjTxPM-krX9gRHNFpevqENkM1fUyVSo4MvbkmQuCE2IRM4Z9t1N-oJ7t87cA6UMr3CtvOPIljsZTXpKi3yav96wOeSVjAlTGlRgro8oZuOg2StoN3_7fzDqexDs2OjGjV5_OKRVydw',
                    overview: 'This course provides comprehensive training in industrial automation including PLC programming (Siemens, Allen-Bradley), SCADA system design, HMI development, and industrial communication protocols used in modern smart factories.',
                    curriculum: [{icon:'electrical_services',title:'PLC Programming',desc:'Master ladder logic, function blocks, and structured text for Siemens and Allen-Bradley PLCs.'},{icon:'monitor',title:'SCADA Systems',desc:'Design and configure supervisory control and data acquisition systems.'},{icon:'settings',title:'HMI Development',desc:'Create intuitive human-machine interface screens for industrial processes.'},{icon:'cell_tower',title:'Industrial Networking',desc:'Understand Profinet, Modbus, Ethernet/IP, and other industrial protocols.'}],
                    career: 'Industrial automation engineers are in high demand across manufacturing, oil & gas, power plants, and process industries.',
                    careerTags: ['Automation Engineer','PLC Programmer','SCADA Engineer','Controls Technician']
                },
            };
            return courses[slug] || courses['solidworks-professional'];
        },

        /* ---- INDUSTRIAL TRAINING ---- */
        industrialTraining() {
            const app = document.getElementById('app');
            app.innerHTML = `
                <div class="container" style="padding-top:var(--space-section);padding-bottom:var(--space-section);">
                    <!-- Hero -->
                    <section class="training-hero" style="margin-bottom:var(--space-section);">
                        <div class="blueprint-grid" style="position:absolute;inset:0;opacity:0.5;z-index:0;"></div>
                        <div class="training-hero-grid">
                            <div style="display:flex;flex-direction:column;justify-content:center;gap:var(--space-md);">
                                <div class="section-label"><span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">precision_manufacturing</span> Advanced Industrial Training</div>
                                <h1 class="font-display-lg-mobile" style="font-family:var(--font-display);font-size:var(--text-display-lg-mobile);line-height:var(--lh-display-mobile);font-weight:700;color:var(--primary);">Engineer Your Career in Modern Manufacturing</h1>
                                <p style="font-size:var(--text-body-lg);line-height:var(--lh-body-lg);color:var(--on-surface-variant);max-width:550px;">Master technical precision with industry-grade curriculum focusing on Fabrication Drawing, Reverse Engineering, Sheet Metal Design, and Advanced CNC Operations.</p>
                                <div style="display:flex;gap:16px;flex-wrap:wrap;">
                                    <button class="btn btn-primary" onclick="document.querySelector('.section').scrollIntoView({behavior:'smooth'})">Explore Curriculum</button>
                                    <button class="btn btn-outline" onclick="showToast('Brochure download coming soon!')">Download Brochure</button>
                                </div>
                            </div>
                            <div class="training-hero-image">
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='600' height='400'%3E%3Crect fill='%23f1f3f5' width='600' height='400'/%3E%3C/svg%3E" data-src="https://lh3.googleusercontent.com/aida-public/AB6AXuAC_4z3HyHv-lMeeYlR7UKdaIdR2jUTq5iZFOgwHVZoBjKjajfHFESGtTno5jvvrBmk-SxWr4nHnqqTGl3UTNi1r_WioRmmSA0JlmIsxy2DOwgsSjTxPM-krX9gRHNFpevqENkM1fUyVSo4MvbkmQuCE2IRM4Z9t1N-oJ7t87cA6UMr3CtvOPIljsZTXpKi3yav96wOeSVjAlTGlRgro8oZuOg2StoN3_7fzDqexDs2OjGjV5_OKRVydw" alt="CNC Training" loading="lazy" decoding="async" class="lazy-img" style="width:100%;height:100%;object-fit:cover;"/>
                                <div style="position:absolute;top:16px;left:16px;background:rgba(251,248,252,0.9);backdrop-filter:blur(8px);border:1px solid var(--outline-variant);padding:8px 12px;border-radius:var(--radius-sm);font-family:var(--font-label);font-size:12px;color:var(--primary);display:flex;align-items:center;gap:8px;">
                                    <span style="width:8px;height:8px;border-radius:50%;background:var(--secondary-fixed);animation:pulse 2s infinite;"></span>
                                    SYSTEM ONLINE
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Disciplines -->
                    <section style="margin-bottom:var(--space-section);">
                        <div style="border-left:3px solid var(--secondary);padding-left:16px;margin-bottom:32px;">
                            <div style="font-family:var(--font-label);font-size:12px;letter-spacing:0.05em;text-transform:uppercase;color:var(--secondary);display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <span class="material-symbols-outlined" style="font-size:16px;">precision_manufacturing</span> Core Disciplines
                            </div>
                            <h2 style="font-family:var(--font-headline);font-size:28px;font-weight:600;color:var(--primary);">Core Disciplines</h2>
                        </div>
                        <div class="grid-2">
                            <div class="discipline-card" style="grid-column:span 2;">
                                <span class="badge badge-outline" style="margin-bottom:12px;">Drafting</span>
                                <h3 style="color:var(--primary);">Fabrication Drawing</h3>
                                <p>Master industry-standard blueprint reading, GD&T, and creation of complex assembly diagrams for heavy machinery and structural components.</p>
                            </div>
                            <div class="discipline-card discipline-card-dark">
                                <span class="badge" style="margin-bottom:12px;">Operations</span>
                                <h3>Advanced CNC Operations</h3>
                                <p>Hands-on programming and operation of multi-axis milling and turning centers using latest G-code standards.</p>
                                <button style="color:var(--tertiary-fixed);font-family:var(--font-button);font-size:var(--text-button);text-transform:uppercase;display:flex;align-items:center;gap:8px;margin-top:16px;background:none;border:none;cursor:pointer;">View Module <span class="material-symbols-outlined" style="font-size:16px;">arrow_forward</span></button>
                            </div>
                            <div class="discipline-card">
                                <span class="badge badge-outline" style="margin-bottom:12px;">Analysis</span>
                                <h3 style="color:var(--primary);">Reverse Engineering</h3>
                                <p>Utilize 3D laser scanning and metrology tools to reconstruct CAD models from physical prototypes.</p>
                            </div>
                            <div class="discipline-card" style="display:flex;gap:var(--space-gutter);align-items:center;flex-wrap:wrap;">
                                <div style="flex:1;min-width:200px;">
                                    <span class="badge badge-outline" style="margin-bottom:12px;">Design</span>
                                    <h3 style="color:var(--primary);margin-bottom:8px;">Sheet Metal Design</h3>
                                    <p style="margin-bottom:12px;">Advanced CAD modeling for bend allowances, K-factors, and flat pattern generation.</p>
                                    <ul style="display:flex;flex-direction:column;gap:4px;">
                                        <li style="display:flex;align-items:center;gap:8px;font-family:var(--font-label);font-size:12px;color:var(--on-surface-variant);"><span class="material-symbols-outlined" style="font-size:14px;color:var(--secondary-fixed);">check_circle</span> Formability Analysis</li>
                                        <li style="display:flex;align-items:center;gap:8px;font-family:var(--font-label);font-size:12px;color:var(--on-surface-variant);"><span class="material-symbols-outlined" style="font-size:14px;color:var(--secondary-fixed);">check_circle</span> Weldment Detailing</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Why Section -->
                    <section style="padding:var(--space-lg);border:1px solid var(--outline-variant);border-radius:var(--radius-xl);background:var(--surface-container);margin-bottom:var(--space-section);">
                        <div style="display:grid;grid-template-columns:1fr;gap:var(--space-gutter);align-items:center;">
                            <div>
                                <h2 class="font-headline-md" style="font-family:var(--font-headline);font-size:var(--text-headline-md);color:var(--primary);margin-bottom:16px;">Why Industry-Ready Training?</h2>
                                <p style="color:var(--on-surface-variant);margin-bottom:24px;font-size:18px;">The gap between academic theory and shop-floor reality is significant. Our curriculum is developed in partnership with leading manufacturing firms.</p>
                                <div style="display:flex;flex-direction:column;gap:16px;">
                                    <div style="background:var(--surface-container-lowest);padding:16px;border:1px solid var(--outline-variant);border-radius:var(--radius-md);display:flex;gap:16px;align-items:flex-start;">
                                        <span class="material-symbols-outlined" style="background:rgba(0,8,30,0.1);padding:8px;border-radius:var(--radius-md);">factory</span>
                                        <div><h4 style="font-weight:700;color:var(--primary);">Shop-Floor Simulation</h4><p style="font-size:14px;color:var(--on-surface-variant);margin-top:4px;">Training in environments mirroring modern production facilities.</p></div>
                                    </div>
                                    <div style="background:var(--surface-container-lowest);padding:16px;border:1px solid var(--outline-variant);border-radius:var(--radius-md);display:flex;gap:16px;align-items:flex-start;">
                                        <span class="material-symbols-outlined" style="background:rgba(0,8,30,0.1);padding:8px;border-radius:var(--radius-md);">verified</span>
                                        <div><h4 style="font-weight:700;color:var(--primary);">Industry Standard Tooling</h4><p style="font-size:14px;color:var(--on-surface-variant);margin-top:4px;">Learn on the exact software and hardware used by top-tier employers.</p></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Enquiry Form -->
                    <section style="max-width:700px;margin:0 auto;">
                        <div style="background:var(--surface-container-lowest);border:1px solid var(--outline-variant);border-radius:var(--radius-xl);padding:var(--space-lg);box-shadow:var(--shadow-card);text-align:center;">
                            <span class="material-symbols-outlined" style="font-size:48px;color:var(--primary);margin-bottom:8px;">assignment</span>
                            <h2 class="font-headline-md" style="font-family:var(--font-headline);font-size:var(--text-headline-md);color:var(--primary);margin-bottom:8px;">Program Enquiry</h2>
                            <p style="color:var(--on-surface-variant);margin-bottom:var(--space-lg);">Request detailed syllabus and admission criteria for our industrial programs.</p>
                            <form style="display:flex;flex-direction:column;gap:16px;text-align:left;" onsubmit="event.preventDefault();showToast('Enquiry submitted successfully!');">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                    <div class="form-group"><label class="form-label">Full Name</label><input type="text" class="form-input" placeholder="e.g. John Doe" required/></div>
                                    <div class="form-group"><label class="form-label">Contact Number</label><input type="tel" class="form-input" placeholder="+91 00000 00000" required/></div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Program of Interest</label>
                                    <select class="form-input"><option>Fabrication Drawing Masterclass</option><option>Reverse Engineering & Metrology</option><option>Advanced Sheet Metal Design</option><option>CNC Operations & Programming</option></select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Experience Level</label>
                                    <div style="display:flex;gap:16px;">
                                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;"><input type="radio" name="exp" class="text-primary"/> Beginner</label>
                                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;"><input type="radio" name="exp" class="text-primary"/> Intermediate</label>
                                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;"><input type="radio" name="exp" class="text-primary"/> Professional</label>
                                    </div>
                                </div>
                                <div class="form-group"><label class="form-label">Additional Questions</label><textarea class="form-input" rows="3" placeholder="Specific areas of interest..."></textarea></div>
                                <button type="submit" class="btn btn-primary btn-full" style="padding:16px;">Submit Enquiry <span class="material-symbols-outlined" style="font-size:16px;">send</span></button>
                            </form>
                        </div>
                    </section>
                </div>
            `;
        },

        /* ---- ADMISSIONS ---- */
        admissions() {
            const app = document.getElementById('app');
            app.innerHTML = `
                <section class="admission-hero">
                    <div class="technical-grid" style="position:absolute;inset:0;z-index:0;"></div>
                    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(251,248,252,0.8),var(--surface-bright));z-index:0;"></div>
                    <div class="container" style="position:relative;z-index:10;">
                        <div class="admission-grid">
                            <div style="display:flex;flex-direction:column;gap:var(--space-lg);padding-top:32px;">
                                <div>
                                    <span style="display:inline-flex;align-items:center;gap:8px;background:var(--surface-container);border:1px solid var(--outline-variant);padding:4px 12px;border-radius:50px;font-family:var(--font-label);font-size:12px;color:var(--primary);margin-bottom:24px;">
                                        <span style="width:8px;height:8px;border-radius:50%;background:var(--secondary-fixed);animation:pulse 2s infinite;"></span>
                                        Admissions Open 2025-26
                                    </span>
                                    <h1 class="font-display-lg-mobile" style="font-family:var(--font-display);font-size:var(--text-display-lg-mobile);line-height:var(--lh-display-mobile);font-weight:700;color:var(--primary);margin-bottom:24px;">Engineer Your Future.<br/>Apply Today.</h1>
                                    <p style="font-size:var(--text-body-lg);line-height:var(--lh-body-lg);color:var(--on-surface-variant);max-width:550px;">Join the premier institution for advanced industrial training. We build technical leaders equipped for modern manufacturing challenges.</p>
                                </div>
                                <div style="background:rgba(255,255,255,0.8);backdrop-filter:blur(12px);border:1px solid var(--outline-variant);padding:24px;border-radius:var(--radius-lg);position:relative;overflow:hidden;box-shadow:var(--shadow-sm);">
                                    <div style="position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--secondary-fixed);"></div>
                                    <h3 style="font-family:var(--font-headline);font-size:var(--text-headline-sm);color:var(--primary);margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                                        <span class="material-symbols-outlined" style="color:var(--outline);">verified_user</span> Basic Eligibility
                                    </h3>
                                    <ul style="display:flex;flex-direction:column;gap:16px;font-size:15px;color:var(--on-surface-variant);">
                                        <li style="display:flex;align-items:flex-start;gap:12px;"><span class="material-symbols-outlined" style="color:var(--secondary);font-size:20px;margin-top:2px;">check_circle</span><span><strong>Age Limit:</strong> 16 to 24 years.</span></li>
                                        <li style="display:flex;align-items:flex-start;gap:12px;"><span class="material-symbols-outlined" style="color:var(--secondary);font-size:20px;margin-top:2px;">check_circle</span><span><strong>Education:</strong> Minimum 10th Standard pass with Science & Mathematics.</span></li>
                                        <li style="display:flex;align-items:flex-start;gap:12px;"><span class="material-symbols-outlined" style="color:var(--secondary);font-size:20px;margin-top:2px;">check_circle</span><span><strong>Physical Fitness:</strong> Must meet standard medical criteria.</span></li>
                                    </ul>
                                </div>
                            </div>
                            <div style="position:relative;margin-top:48px;">
                                <div style="position:absolute;top:-16px;right:-16px;width:96px;height:96px;border-top:2px solid var(--outline-variant);border-right:2px solid var(--outline-variant);opacity:0.5;"></div>
                                <div style="background:var(--surface-container-lowest);border:1px solid var(--outline-variant);border-radius:var(--radius-xl);padding:var(--space-lg);box-shadow:0 10px 40px rgba(10,31,68,0.08);position:relative;z-index:10;">
                                    <div style="margin-bottom:24px;">
                                        <h2 style="font-family:var(--font-headline);font-size:var(--text-headline-sm);color:var(--primary);">Free Counselling Session</h2>
                                        <p style="color:var(--on-surface-variant);margin-top:8px;">Speak with our admission experts to find the right technical path.</p>
                                    </div>
                                    <form id="admissions-form" style="display:flex;flex-direction:column;gap:16px;" onsubmit="submitAdmissionsEnquiry(event)">
                                        <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-input" placeholder="John Doe" required/></div>
                                        <div class="form-group"><label class="form-label">Phone Number</label><input type="tel" name="phone" class="form-input" placeholder="+91 00000 00000" required/></div>
                                        <div class="form-group">
                                            <label class="form-label">Trade Interest</label>
                                            <select name="subject" class="form-input" required><option value="">Select a trade...</option><option>AutoCAD</option><option>SolidWorks</option><option>CATIA</option><option>CNC Programming</option><option>Industrial Training</option></select>
                                        </div>
                                        <button type="submit" class="btn btn-secondary btn-full" style="margin-top:8px;">Request Callback <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span></button>
                                        <p style="font-size:12px;color:var(--on-surface-variant);text-align:center;margin-top:16px;">By submitting, you agree to our Privacy Policy.</p>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Process -->
                <section style="padding:var(--space-section) 0;background:var(--surface-container-low);border-top:1px solid var(--outline-variant);border-bottom:1px solid var(--outline-variant);">
                    <div class="container">
                        <div style="text-align:center;margin-bottom:64px;">
                            <span class="font-label" style="color:var(--primary);letter-spacing:0.1em;margin-bottom:8px;display:block;">Simplified Path</span>
                            <h2 class="font-headline-md" style="font-family:var(--font-headline);font-size:var(--text-headline-md);color:var(--primary);">Admission Process</h2>
                        </div>
                        <div class="grid-3">
                            <div class="process-card">
                                <span class="process-number">01</span>
                                <div class="process-icon"><span class="material-symbols-outlined" style="font-size:32px;font-variation-settings:'FILL' 1;">description</span></div>
                                <h3>Application Submission</h3>
                                <p>Complete the online application form and upload scanned copies of your academic and identity documents.</p>
                            </div>
                            <div class="process-card">
                                <span class="process-number">02</span>
                                <div class="process-icon"><span class="material-symbols-outlined" style="font-size:32px;font-variation-settings:'FILL' 1;">assignment</span></div>
                                <h3>Entrance Assessment</h3>
                                <p>Appear for the technical aptitude test. Shortlisted candidates will be called for a brief personal interview.</p>
                            </div>
                            <div class="process-card">
                                <span class="process-number">03</span>
                                <div class="process-icon"><span class="material-symbols-outlined" style="font-size:32px;font-variation-settings:'FILL' 1;">task_alt</span></div>
                                <h3>Enrollment & Fees</h3>
                                <p>Upon selection, complete your enrollment by submitting original documents and paying the term fee.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- FAQ -->
                <section class="section">
                    <div class="container" style="max-width:800px;">
                        <div style="text-align:center;margin-bottom:48px;">
                            <h2 class="font-headline-md" style="font-family:var(--font-headline);font-size:var(--text-headline-md);color:var(--primary);">Admissions FAQ</h2>
                            <p style="color:var(--on-surface-variant);margin-top:8px;">Answers to common questions regarding our admission process.</p>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:16px;">
                            <div class="faq-item">
                                <button class="faq-question">Is there a hostel facility available?<span class="faq-icon"><span class="material-symbols-outlined" style="font-size:20px;">expand_more</span></span></button>
                                <div class="faq-answer"><div class="faq-answer-inner">Yes, we provide separate hostel facilities for boys and girls with strict security, mess facilities, and high-speed internet access.</div></div>
                            </div>
                            <div class="faq-item">
                                <button class="faq-question">What is the fee structure?<span class="faq-icon"><span class="material-symbols-outlined" style="font-size:20px;">expand_more</span></span></button>
                                <div class="faq-answer"><div class="faq-answer-inner">The fee structure varies depending on the specific trade. Detailed fee breakdowns are provided during the counselling session.</div></div>
                            </div>
                            <div class="faq-item">
                                <button class="faq-question">Are there any scholarships available?<span class="faq-icon"><span class="material-symbols-outlined" style="font-size:20px;">expand_more</span></span></button>
                                <div class="faq-answer"><div class="faq-answer-inner">Yes, we offer merit-based scholarships for students demonstrating exceptional performance in their board exams.</div></div>
                            </div>
                        </div>
                    </div>
                </section>
            `;
            initFAQ();
        },

        /* ---- GALLERY ---- */
        async gallery() {
            const apiGallery = await DataStore.fetchGallery();
            const fallbackGallery = [
                { title: 'CNC Training Center', category: 'CNC', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCOmpaB4xIjW3Qwv6S3K13ZfcKGlzqGUhvp9UBFn62945tiVvtgKhSmynUJHb0Y-xIiAk4LLpmpZeKsqY7J6ecPNeNX2zmZ7ZvgoFHolYHiEfQzumeW27wyk6lhRikZ67ShCE1n5Yf_hpwVn2Ss2TDGWQwvpCAoNfUDWeI15wUSEU6dczPn23kmui56VBXkEYUbjFVi9IDcH7alXTOaOUWGldhiFTB-mNtb4u90IUl53jAjZERrW_k3mg' },
                { title: 'CAD Design Suites', category: 'CAD', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBXuqjvOC-jh5OC6M-upDH1nIoYn3fz8mQRJervO0K628T63eKWuOsC1ISp_dbQ3FU-vf4-bRQanVtPiocbYttovLzedIw7-MfdoCl7pOl2pT3Dev4k9OmB3x6VOILibSSJLJO49DDd2ihTmjO0sLyfVbfKkcrCNnj2OIAAiS2LMmVFbSfHilwbDtprkHgHxiX6TGtmuR-DmzoQV8uR3DxeJCv4ztD-se0Boz-_W__wI4JS8c2VdngC9g' },
                { title: 'Main Campus', category: 'Campus', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAvS46g0SUFVGpYZeL8StwuqTyE5mLDvVNOAK45Dq0KKd2Ztx5YqJ1obtZVV4NolXL9e31ipzq5M07Sx1MTq_sMRHxedVg6FKH3yZ8I48NLUPWJgGWC4NeR4lkHdys7asGSfyYDwpzvTjPcLj0AawLNLWfdhvBkGiFwxdk7Bu4InwJYNtwSSM8eIJkQ1nVDIzMwMUCaL5y-WM2T1H2JekKrNtAi_GZtdzjwnpYDssI_ymaf2njpj4RJZA' },
                { title: 'Robotics Workshop', category: 'Events', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD7UHZPgVK9BcnL4QUIwkO1VUnUWIKZHK8vUeuBeGE4pxVJcKHf_S_sgzLx_UM57ot8IaJ2eB4zB8vHP33TeNEsRdgGVxPxxa9oxfYkX-l_Q23c5N4R7lSRGJ2xXoHLw5M5xhUoYw9wELec2BlewF3oPXE41-Ng8aGEFzRaY039H1YU4It9z3Nw6NkX-u1EfGPScyLRRgvDwNdlxGwya7dd9QvCUSnAi-9jjjkRQ_d3BvOUSFTEM_gcCA' },
            ];
            const items = apiGallery.length ? apiGallery : fallbackGallery;
            const app = document.getElementById('app');
            app.innerHTML = `
                <div class="container" style="padding-top:var(--space-section);padding-bottom:var(--space-section);">
                    <header class="page-header">
                        <h1>Facility & Gallery</h1>
                        <p>Explore our state-of-the-art technical training facilities.</p>
                    </header>
                    <section style="margin-bottom:var(--space-section);">
                        <div style="border-left:3px solid var(--secondary);padding-left:16px;margin-bottom:32px;">
                            <div style="font-family:var(--font-label);font-size:12px;letter-spacing:0.05em;text-transform:uppercase;color:var(--secondary);display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <span class="material-symbols-outlined" style="font-size:16px;">photo_library</span> Campus Gallery
                            </div>
                            <h2 style="font-family:var(--font-headline);font-size:28px;font-weight:600;color:var(--primary);">Campus Gallery</h2>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);grid-template-rows:repeat(2,250px);gap:16px;" class="gallery-bento">
                            <div class="lazy-bg" data-bg="${items[0]?.image||''}" style="grid-column:span 2;grid-row:span 2;background-size:cover;background-position:center;border-radius:12px;overflow:hidden;position:relative;cursor:pointer;">
                                <div style="position:absolute;inset:0;background:linear-gradient(transparent 40%, rgba(0,8,30,0.8));"></div>
                                <div style="position:absolute;bottom:16px;left:16px;z-index:10;"><span style="display:inline-block;padding:4px 10px;background:var(--primary);color:var(--on-primary);border-radius:4px;font-size:11px;font-family:var(--font-label);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">${items[0]?.category||'Campus'}</span><h3 style="color:white;font-family:var(--font-headline);font-size:20px;">${items[0]?.title||'Featured'}</h3></div>
                            </div>
                            ${items.slice(1,3).map(g => `<div class="lazy-bg" data-bg="${g.image||''}" style="background-size:cover;background-position:center;border-radius:12px;overflow:hidden;position:relative;cursor:pointer;"><div style="position:absolute;inset:0;background:linear-gradient(transparent 40%, rgba(0,8,30,0.8));"></div><div style="position:absolute;bottom:16px;left:16px;z-index:10;"><span style="display:inline-block;padding:4px 10px;background:var(--primary);color:var(--on-primary);border-radius:4px;font-size:11px;font-family:var(--font-label);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">${g.category||'Campus'}</span><h3 style="color:white;font-family:var(--font-headline);font-size:16px;">${g.title}</h3></div></div>`).join('')}
                            <div class="lazy-bg" data-bg="${items[3]?.image||items[1]?.image||''}" style="grid-column:span 2;background-size:cover;background-position:center;border-radius:12px;overflow:hidden;position:relative;cursor:pointer;"><div style="position:absolute;inset:0;background:linear-gradient(transparent 40%, rgba(0,8,30,0.8));"></div><div style="position:absolute;bottom:16px;left:16px;z-index:10;"><span style="display:inline-block;padding:4px 10px;background:var(--primary);color:var(--on-primary);border-radius:4px;font-size:11px;font-family:var(--font-label);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">${items[3]?.category||items[1]?.category||'Campus'}</span><h3 style="color:white;font-family:var(--font-headline);font-size:16px;">${items[3]?.title||items[1]?.title||'Gallery'}</h3></div></div>
                        </div>
                    </section>
                </div>
                ${Pages._contactSection()}
                ${Pages._mapSection()}
            `;
        },

        /* ---- CONTACT ---- */
        contact() {
            const app = document.getElementById('app');
            app.innerHTML = `
                <div class="container">
                    <header class="page-header">
                        <h1>Contact Us</h1>
                        <p>Get in touch with our admissions team to start your journey in advanced manufacturing.</p>
                    </header>
                </div>
                ${Pages._contactSection()}
                ${Pages._mapSection()}
            `;
        },

        _contactSection() {
            return `
                <section style="padding:var(--space-section) 0;background:var(--surface-container-low);border-top:1px solid var(--outline-variant);border-bottom:1px solid var(--outline-variant);">
                    <div class="container">
                        <div class="contact-layout">
                            <div class="contact-info-card">
                                <h2 class="font-headline-md" style="font-family:var(--font-headline);font-size:var(--text-headline-md);color:var(--primary);margin-bottom:var(--space-lg);">Get in Touch</h2>
                                <div style="display:flex;flex-direction:column;gap:var(--space-lg);">
                                    <div class="contact-detail">
                                        <div class="contact-detail-icon"><span class="material-symbols-outlined" style="color:var(--primary);">location_on</span></div>
                                        <div><h4>Visit Us</h4><p>Gamharia Chowk</p><span>Jamshedpur, Jharkhand</span></div>
                                    </div>
                                    <div class="contact-detail">
                                        <div class="contact-detail-icon"><span class="material-symbols-outlined" style="color:var(--primary);">call</span></div>
                                        <div><h4>Call Us</h4><p><a href="tel:+918789961355">8789961355</a></p><span>Mon-Sat, 9AM - 6PM</span></div>
                                    </div>
                                    <div class="contact-detail">
                                        <div class="contact-detail-icon green"><span class="material-symbols-outlined">chat</span></div>
                                        <div><h4>Message Us</h4><p>WhatsApp Support</p><span>Quick replies within 1 hr</span></div>
                                    </div>
                                </div>
                                <div style="margin-top:var(--space-lg);padding-top:var(--space-lg);border-top:1px solid var(--outline-variant);">
                                    <p style="font-family:var(--font-label);font-size:12px;letter-spacing:0.05em;text-transform:uppercase;color:var(--on-surface-variant);margin-bottom:16px;">Follow Our Progress</p>
                                    <div style="display:flex;gap:16px;">
                                        <a href="javascript:void(0)" style="width:40px;height:40px;border-radius:var(--radius-md);border:1px solid var(--outline-variant);display:flex;align-items:center;justify-content:center;color:var(--primary);transition:background 0.2s;"><span class="material-symbols-outlined">language</span></a>
                                        <a href="javascript:void(0)" style="width:40px;height:40px;border-radius:var(--radius-md);border:1px solid var(--outline-variant);display:flex;align-items:center;justify-content:center;color:var(--primary);transition:background 0.2s;"><span class="material-symbols-outlined">photo_camera</span></a>
                                        <a href="javascript:void(0)" style="width:40px;height:40px;border-radius:var(--radius-md);border:1px solid var(--outline-variant);display:flex;align-items:center;justify-content:center;color:var(--primary);transition:background 0.2s;"><span class="material-symbols-outlined">video_library</span></a>
                                    </div>
                                </div>
                            </div>
                            <div class="contact-form-card">
                                <h3 style="font-family:var(--font-headline);font-size:var(--text-headline-sm);color:var(--primary);margin-bottom:8px;">General Enquiry</h3>
                                <p style="color:var(--on-surface-variant);margin-bottom:var(--space-lg);">Fill out the form and our admissions team will contact you shortly.</p>
                                <form id="contact-enquiry-form" style="display:flex;flex-direction:column;gap:16px;" onsubmit="submitContactEnquiry(event)">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                        <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-input" placeholder="John Doe" required/></div>
                                        <div class="form-group"><label class="form-label">Phone Number *</label><input type="tel" name="phone" class="form-input" placeholder="+91" required/></div>
                                    </div>
                                    <div class="form-group"><label class="form-label">Email Address</label><input type="email" name="email" class="form-input" placeholder="student@example.com"/></div>
                                    <div class="form-group">
                                        <label class="form-label">Course of Interest</label>
                                        <select name="course_slug" class="form-input"><option value="">Select a course...</option><option value="cnc-lathe-programming">CNC Machining</option><option value="industrial-safety-quality">Industrial Automation</option><option value="autocad-fundamentals">CAD/CAM Design</option><option value="other">Other / Not Sure</option></select>
                                    </div>
                                    <div class="form-group"><label class="form-label">Message</label><textarea name="message" class="form-input" rows="4" placeholder="How can we help you?"></textarea></div>
                                    <button type="submit" class="btn btn-primary btn-full" style="padding:16px;">Send Enquiry <span class="material-symbols-outlined" style="font-size:16px;">arrow_forward</span></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>`;
        },

        _mapSection() {
            return `
                <section style="width:100%;height:400px;position:relative;background:var(--surface-variant);">
                    <div style="position:absolute;inset:0;background:linear-gradient(135deg,#e4e2e5 0%,#dbd9dd 100%);opacity:0.8;"></div>
                    <div style="position:absolute;inset:0;background:rgba(0,8,30,0.05);"></div>
                    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);display:flex;flex-direction:column;align-items:center;gap:8px;z-index:10;">
                        <div style="background:var(--surface);padding:8px 16px;border-radius:var(--radius-md);box-shadow:var(--shadow-lg);border:1px solid var(--outline-variant);">
                            <p style="font-family:var(--font-label);font-size:12px;color:var(--primary);font-weight:700;">Global Institute</p>
                            <p style="font-family:var(--font-label);font-size:10px;color:var(--on-surface-variant);">Gamharia Chowk, Jamshedpur</p>
                        </div>
                        <span class="material-symbols-outlined" style="font-size:48px;color:var(--error);filter:drop-shadow(0 2px 4px rgba(0,0,0,0.3));font-variation-settings:'FILL' 1;">location_on</span>
                    </div>
                </section>`;
        },

        /* ---- ABOUT ---- */
        about() {
            const app = document.getElementById('app');
            app.innerHTML = `
                <div class="container">
                    <header class="page-header">
                        <h1>About Global Institute</h1>
                        <p>Youth Skill Development & Research Institute in Gamharia, Jamshedpur, Jharkhand</p>
                    </header>

                    <!-- Mission & Vision -->
                    <section style="margin-bottom:var(--space-section);">
                        <div style="display:grid;grid-template-columns:1fr;gap:var(--space-gutter);">
                            <div style="background:var(--primary);color:var(--on-primary);border-radius:var(--radius-xl);padding:var(--space-lg);position:relative;overflow:hidden;">
                                <div class="blueprint-dark" style="position:absolute;inset:0;opacity:0.3;"></div>
                                <div style="position:relative;z-index:10;">
                                    <span class="material-symbols-outlined" style="font-size:48px;color:var(--secondary-fixed);margin-bottom:16px;">flag</span>
                                    <h2 style="font-family:var(--font-headline);font-size:var(--text-headline-md);margin-bottom:12px;">Our Mission</h2>
                                    <p style="opacity:0.9;line-height:1.7;">To bridge the gap between academic education and industrial requirements by providing world-class technical training that empowers youth with job-ready skills for the modern manufacturing sector.</p>
                                </div>
                            </div>
                            <div style="background:var(--surface-container-lowest);border:1px solid var(--outline-variant);border-radius:var(--radius-xl);padding:var(--space-lg);position:relative;overflow:hidden;">
                                <span class="material-symbols-outlined" style="font-size:48px;color:var(--primary);margin-bottom:16px;">visibility</span>
                                <h2 style="font-family:var(--font-headline);font-size:var(--text-headline-md);color:var(--primary);margin-bottom:12px;">Our Vision</h2>
                                <p style="color:var(--on-surface-variant);line-height:1.7;">To become the leading skill development institute in Eastern India, producing industry-ready professionals who contribute to India's manufacturing and engineering excellence.</p>
                            </div>
                        </div>
                    </section>

                    <!-- Values -->
                    <section style="margin-bottom:var(--space-section);">
                        <div style="border-left:3px solid var(--secondary);padding-left:16px;margin-bottom:32px;text-align:left;">
                            <div style="font-family:var(--font-label);font-size:12px;letter-spacing:0.05em;text-transform:uppercase;color:var(--secondary);display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <span class="material-symbols-outlined" style="font-size:16px;">favorite</span> Our Values
                            </div>
                            <h2 style="font-family:var(--font-headline);font-size:28px;font-weight:600;color:var(--primary);">What We Stand For</h2>
                        </div>
                        <div class="about-values-grid" style="margin-top:var(--space-lg);">
                            <div class="value-card">
                                <span class="material-symbols-outlined">precision_manufacturing</span>
                                <h3>Technical Excellence</h3>
                                <p>Industry-grade equipment and curriculum developed with manufacturing partners.</p>
                            </div>
                            <div class="value-card">
                                <span class="material-symbols-outlined">groups</span>
                                <h3>Student-Centered</h3>
                                <p>Small batch sizes, personalized attention, and mentorship-driven learning.</p>
                            </div>
                            <div class="value-card">
                                <span class="material-symbols-outlined">handshake</span>
                                <h3>Industry Partnership</h3>
                                <p>Direct collaboration with manufacturing companies for curriculum and placement.</p>
                            </div>
                            <div class="value-card">
                                <span class="material-symbols-outlined">emoji_events</span>
                                <h3>Results Oriented</h3>
                                <p>Measurable outcomes with placement tracking and career support.</p>
                            </div>
                            <div class="value-card">
                                <span class="material-symbols-outlined">security</span>
                                <h3>Safety First</h3>
                                <p>World-class safety standards in all practical training environments.</p>
                            </div>
                            <div class="value-card">
                                <span class="material-symbols-outlined">eco</span>
                                <h3>Sustainability</h3>
                                <p>Environmentally responsible practices in our training methodologies.</p>
                            </div>
                        </div>
                    </section>

                    <!-- Trainers -->
                    <section style="margin-bottom:var(--space-section);">
                        <div style="border-left:3px solid var(--secondary);padding-left:16px;margin-bottom:32px;text-align:left;">
                            <div style="font-family:var(--font-label);font-size:12px;letter-spacing:0.05em;text-transform:uppercase;color:var(--secondary);display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <span class="material-symbols-outlined" style="font-size:16px;">school</span> Our Team
                            </div>
                            <h2 style="font-family:var(--font-headline);font-size:28px;font-weight:600;color:var(--primary);">Expert Trainers</h2>
                        </div>
                        <div class="grid-3" style="margin-top:var(--space-lg);">
                            <div class="trainer-card">
                                <div class="trainer-photo"><span class="material-symbols-outlined">person</span></div>
                                <div class="trainer-info">
                                    <h3>Industry Expert</h3>
                                    <div class="designation">Senior CAD Trainer</div>
                                    <p>15+ years of experience in mechanical design and CAD/CAM training.</p>
                                </div>
                            </div>
                            <div class="trainer-card">
                                <div class="trainer-photo"><span class="material-symbols-outlined">person</span></div>
                                <div class="trainer-info">
                                    <h3>CNC Specialist</h3>
                                    <div class="designation">CNC Programming Lead</div>
                                    <p>Expert in multi-axis CNC programming with industry deployment experience.</p>
                                </div>
                            </div>
                            <div class="trainer-card">
                                <div class="trainer-photo"><span class="material-symbols-outlined">person</span></div>
                                <div class="trainer-info">
                                    <h3>Industrial Engineer</h3>
                                    <div class="designation">Industrial Training Head</div>
                                    <p>Specialist in fabrication, reverse engineering, and industrial processes.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Infrastructure -->
                    <section style="margin-bottom:var(--space-section);">
                        <div style="border-left:3px solid var(--secondary);padding-left:16px;margin-bottom:32px;">
                            <div style="font-family:var(--font-label);font-size:12px;letter-spacing:0.05em;text-transform:uppercase;color:var(--secondary);display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <span class="material-symbols-outlined" style="font-size:16px;">building</span> Infrastructure
                            </div>
                            <h2 style="font-family:var(--font-headline);font-size:28px;font-weight:600;color:var(--primary);">World-Class Facilities</h2>
                        </div>
                        <div class="grid-4" style="margin-top:var(--space-lg);">
                            <div class="why-card" style="flex-direction:column;text-align:center;align-items:center;">
                                <span class="material-symbols-outlined" style="font-size:40px;color:var(--primary);">precision_manufacturing</span>
                                <h3>CNC Lab</h3>
                                <p>Latest CNC, VMC, and HMC machines for hands-on training.</p>
                            </div>
                            <div class="why-card" style="flex-direction:column;text-align:center;align-items:center;">
                                <span class="material-symbols-outlined" style="font-size:40px;color:var(--primary);">computer</span>
                                <h3>CAD Lab</h3>
                                <p>High-performance workstations with licensed CAD software.</p>
                            </div>
                            <div class="why-card" style="flex-direction:column;text-align:center;align-items:center;">
                                <span class="material-symbols-outlined" style="font-size:40px;color:var(--primary);">settings_suggest</span>
                                <h3>Workshop</h3>
                                <p>Full-scale industrial workshop for practical training.</p>
                            </div>
                            <div class="why-card" style="flex-direction:column;text-align:center;align-items:center;">
                                <span class="material-symbols-outlined" style="font-size:40px;color:var(--primary);">library_books</span>
                                <h3>Library</h3>
                                <p>Technical reference books, journals, and digital resources.</p>
                            </div>
                        </div>
                    </section>
                </div>
            `;
        },
    };

    /* ------------------------------------------
       Initialize Application
       ------------------------------------------ */
    function init() {
        // Setup mobile navigation
        initMobileNav();

        // Setup enquiry modal close on overlay click
        document.getElementById('enquiry-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'enquiry-modal') closeEnquiryModal();
        });

        // Setup enquiry form
        document.getElementById('enquiry-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.textContent;
            btn.textContent = 'Submitting...';
            btn.disabled = true;
            try {
                const csrfRes = await fetch(`${API_BASE}/api/csrf-token`);
                const { token } = await csrfRes.json();
                const formData = new FormData(form);
                formData.set('_token', token);
                const res = await fetch(`${API_BASE}/api/enquiries`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    closeEnquiryModal();
                    form.reset();
                    showToast('Enquiry submitted successfully! We will contact you soon.');
                } else {
                    showToast(data.message || 'Something went wrong. Please try again.', 'error');
                }
            } catch (err) {
                showToast('Network error. Please try again.', 'error');
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });

        // Setup routes
        Router
            .addRoute('/', Pages.home)
            .addRoute('/courses', Pages.courses)
            .addRoute('/courses/:slug', Pages.courseDetail)
            .addRoute('/industrial-training', Pages.industrialTraining)
            .addRoute('/admissions', Pages.admissions)
            .addRoute('/gallery', Pages.gallery)
            .addRoute('/contact', Pages.contact)
            .addRoute('/about', Pages.about);

        // Start router
        Router.init();
    }

    // Expose globals for inline handlers
    window.openEnquiryModal = openEnquiryModal;
    window.closeEnquiryModal = closeEnquiryModal;
    window.showToast = showToast;
    window.toggleFaq = function(i) {
        document.querySelectorAll('.faq-item').forEach((el, idx) => {
            if (idx === i) el.classList.toggle('open');
            else el.classList.remove('open');
        });
    };
    window.submitContactEnquiry = async function(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        const orig = btn.textContent;
        btn.textContent = 'Sending...'; btn.disabled = true;
        try {
            const csrfRes = await fetch(`${API_BASE}/api/csrf-token`);
            const { token } = await csrfRes.json();
            const fd = new FormData(form);
            fd.set('_token', token);
            const res = await fetch(`${API_BASE}/api/enquiries`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd });
            const data = await res.json();
            if (res.ok && data.success) { form.reset(); showToast('Enquiry submitted successfully!'); }
            else showToast(data.message || 'Something went wrong.', 'error');
        } catch (err) { showToast('Network error.', 'error'); }
        finally { btn.textContent = orig; btn.disabled = false; }
    };

    window.submitCourseEnquiry = async function(e, courseTitle) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        const orig = btn.textContent;
        btn.textContent = 'Submitting...'; btn.disabled = true;
        try {
            const csrfRes = await fetch(`${API_BASE}/api/csrf-token`);
            const { token } = await csrfRes.json();
            const fd = new FormData(form);
            fd.set('_token', token);
            fd.set('subject', courseTitle);
            fd.set('source', 'course_detail');
            const res = await fetch(`${API_BASE}/api/enquiries`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd });
            const data = await res.json();
            if (res.ok && data.success) { form.reset(); showToast('Enquiry submitted! We will contact you shortly.'); }
            else showToast(data.message || 'Something went wrong.', 'error');
        } catch (err) { showToast('Network error.', 'error'); }
        finally { btn.textContent = orig; btn.disabled = false; }
    };

    window.submitAdmissionsEnquiry = async function(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        const orig = btn.innerHTML;
        btn.innerHTML = 'Submitting...'; btn.disabled = true;
        try {
            const csrfRes = await fetch(`${API_BASE}/api/csrf-token`);
            const { token } = await csrfRes.json();
            const fd = new FormData(form);
            fd.set('_token', token);
            fd.set('source', 'admissions');
            const res = await fetch(`${API_BASE}/api/enquiries`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd });
            const data = await res.json();
            if (res.ok && data.success) { form.reset(); showToast('Counselling request submitted! Our team will call you shortly.'); }
            else showToast(data.message || 'Something went wrong.', 'error');
        } catch (err) { showToast('Network error.', 'error'); }
        finally { btn.innerHTML = orig; btn.disabled = false; }
    };

    // Boot
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

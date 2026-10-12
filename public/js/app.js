/* ============================================================
   GLOBAL INSTITUTE & CAD CAM — Public SPA
   Vanilla JS single page application.
   Data comes from the public JSON API; nothing is hard-coded
   that the institute cannot back up with real records.
   ============================================================ */

(function () {
    'use strict';

    /* --------------------------------------------------------
       API base (public/ is the doc root when served by Laravel)
       -------------------------------------------------------- */
    const API_BASE = (() => {
        const base = document.querySelector('base');
        if (base && base.href) return base.href.replace(/\/$/, '');
        const src = document.currentScript && document.currentScript.src;
        if (src) {
            const path = new URL(src).pathname;
            const root = path.replace(/\/js\/[^/]*$/, '');
            if (root) return root;
        }
        if (window.location.port === '8000') return '';
        return window.location.origin;
    })();

    const IMG_PLACEHOLDER = 'data:image/svg+xml,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" fill="#eef1f6">' +
        '<rect width="600" height="400"/></svg>'
    );

    const CATEGORY_META = {
        'cad-cam':            { label: 'CAD/CAM',          icon: 'precision_manufacturing' },
        'cnc-programming':    { label: 'CNC Programming',  icon: 'settings' },
        'industrial-skills':  { label: 'Industrial Skills', icon: 'build' },
        'computer-skills':    { label: 'Computer Skills',  icon: 'computer' },
    };

    const FACILITY_META = {
        computer_lab: { label: 'Computer Lab',  icon: 'computer' },
        cad_lab:      { label: 'CAD Lab',       icon: 'architecture' },
        cnc_lab:      { label: 'CNC Lab',       icon: 'precision_manufacturing' },
        workshop:     { label: 'Workshop',      icon: 'build' },
        classroom:    { label: 'Classroom',     icon: 'meeting_room' },
        other:        { label: 'Facility',      icon: 'apartment' },
    };

    /* --------------------------------------------------------
       Small helpers
       -------------------------------------------------------- */
    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function categoryMeta(slug) {
        return CATEGORY_META[slug] || { label: 'Program', icon: 'school' };
    }

    function facilityMeta(type) {
        return FACILITY_META[type] || FACILITY_META.other;
    }

    function formatFee(fees) {
        if (fees == null || fees === '' || fees === '0.00' || Number(fees) === 0) return 'Contact us';
        return '\u20B9' + Number(fees).toLocaleString('en-IN');
    }

    function initials(name) {
        return String(name || '?').trim().charAt(0).toUpperCase();
    }

    function api(path, options) {
        return fetch(API_BASE + path, Object.assign({ headers: { Accept: 'application/json' } }, options || {}))
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            });
    }

    /* --------------------------------------------------------
       Data store (dedupes in-flight requests, memoises results)
       -------------------------------------------------------- */
    const Store = {
        _cache: {},
        _load(key, path, fallback) {
            if (!(key in this._cache)) {
                this._cache[key] = api(path)
                    .then(json => json.data != null ? json.data : fallback)
                    .catch(() => fallback);
            }
            return this._cache[key];
        },
        courses()      { return this._load('courses', '/api/courses', []); },
        faculty()      { return this._load('faculty', '/api/faculty', []); },
        facilities()   { return this._load('facilities', '/api/facilities', []); },
        testimonials() { return this._load('testimonials', '/api/testimonials', []); },
        faqs()         { return this._load('faqs', '/api/faqs', []); },
        gallery()      { return this._load('gallery', '/api/gallery', []); },
        announcements(){ return this._load('announcements', '/api/announcements', []); },
        settings()     { return this._load('settings', '/api/settings', {}); },
        course(slug)   { return this._load('course:' + slug, '/api/courses/' + encodeURIComponent(slug), null); }
    };

    /* --------------------------------------------------------
       Toast
       -------------------------------------------------------- */
    function showToast(message, type) {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const el = document.createElement('div');
        el.className = 'toast toast-' + (type || 'success');
        const icon = type === 'error' ? 'error' : 'check_circle';
        el.innerHTML = '<span class="material-symbols-outlined">' + icon + '</span><span>' + esc(message) + '</span>';
        container.appendChild(el);
        setTimeout(() => el.remove(), 3600);
    }

    /* --------------------------------------------------------
       Enquiry modal
       -------------------------------------------------------- */
    function openEnquiryModal(courseSlug) {
        const modal = document.getElementById('enquiry-modal');
        if (!modal) return;
        const select = document.getElementById('enquiry-course');
        if (select && courseSlug) {
            const match = Array.from(select.options).find(o => o.value === courseSlug);
            if (match) select.value = courseSlug;
        }
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        const first = document.getElementById('enquiry-name');
        if (first) setTimeout(() => first.focus(), 150);
    }

    function closeEnquiryModal() {
        const modal = document.getElementById('enquiry-modal');
        if (modal) modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    /* --------------------------------------------------------
       CSRF + enquiry submission
       -------------------------------------------------------- */
    let csrfToken = null;
    function getCsrf(force) {
        if (csrfToken && !force) return Promise.resolve(csrfToken);
        return api('/api/csrf-token')
            .then(j => { csrfToken = j.token; return csrfToken; })
            .catch(() => null);
    }

    function validationMessage(data) {
        if (data && data.errors) {
            const first = Object.values(data.errors)[0];
            if (Array.isArray(first) && first.length) return first[0];
        }
        return (data && data.message) || 'Something went wrong. Please try again.';
    }

    function submitEnquiry(form, extra) {
        return getCsrf().then(token => {
            const fd = new FormData(form);
            if (token) fd.set('_token', token);
            Object.keys(extra || {}).forEach(k => { if (extra[k] != null && extra[k] !== '') fd.set(k, extra[k]); });
            return fetch(API_BASE + '/api/enquiries', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                body: fd
            });
        }).then(res => {
            if (res.status === 419) { csrfToken = null; throw new Error('Your session expired. Please try again.'); }
            return res.json().catch(() => ({})).then(data => {
                if (!res.ok || !data.success) throw new Error(validationMessage(data));
                return data;
            });
        });
    }

    function wireForm(formId, options) {
        const form = document.getElementById(formId);
        if (!form) return;
        options = options || {};
        form.addEventListener('submit', e => {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"], button:not([type])');
            const original = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.dataset.busy = '1'; btn.innerHTML = 'Sending\u2026'; }
            submitEnquiry(form, options.extra ? options.extra(form) : null)
                .then(() => {
                    form.reset();
                    if (options.onSuccess) options.onSuccess();
                    showToast(options.successMessage || 'Enquiry submitted. We will contact you shortly.');
                })
                .catch(err => showToast(err.message || 'Network error. Please try again.', 'error'))
                .finally(() => {
                    if (btn) { btn.disabled = false; btn.innerHTML = original; delete btn.dataset.busy; }
                });
        });
    }

    /* --------------------------------------------------------
       Router
       -------------------------------------------------------- */
    const Routes = [
        { pattern: /^\/$/,                     page: 'home' },
        { pattern: /^\/courses$/,              page: 'courses' },
        { pattern: /^\/courses\/([^/]+)$/,     page: 'courseDetail', params: ['slug'] },
        { pattern: /^\/faculty$/,              page: 'faculty' },
        { pattern: /^\/facilities$/,           page: 'facilities' },
        { pattern: /^\/about$/,                page: 'about' },
        { pattern: /^\/admissions$/,           page: 'admissions' },
        { pattern: /^\/gallery$/,              page: 'gallery' },
        { pattern: /^\/contact$/,              page: 'contact' },
    ];

    const ACTIVE_PAGES = {
        home: 'home', courses: 'courses', courseDetail: 'courses',
        faculty: 'faculty', facilities: 'facilities', about: 'about',
        admissions: 'admissions', gallery: 'gallery', contact: 'contact'
    };

    const Router = {
        current: null,
        resolve() {
            const hash = (window.location.hash.slice(1) || '/').split('?')[0];
            let match = null;
            for (const route of Routes) {
                const m = hash.match(route.pattern);
                if (m) { match = { page: route.page, args: m.slice(1).map(decodeURIComponent) }; break; }
            }
            if (!match) match = { page: 'notFound', args: [] };

            this.current = match.page;
            setActiveNav(ACTIVE_PAGES[match.page] || '');
            const render = Pages[match.page] || Pages.notFound;
            this.transition(() => render.apply(Pages, match.args));
        },
        transition(renderFn) {
            const app = document.getElementById('app');
            app.classList.add('page-exit');
            window.setTimeout(() => {
                renderFn();
                app.classList.remove('page-exit');
                app.classList.add('page-enter');
                window.setTimeout(() => app.classList.remove('page-enter'), 260);
                if (window.LazyLoad) window.LazyLoad.init(app);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 140);
        }
    };

    function setActiveNav(page) {
        document.querySelectorAll('[data-page]').forEach(el => {
            el.classList.toggle('active', el.dataset.page === page);
        });
    }

    /* --------------------------------------------------------
       Reusable partials
       -------------------------------------------------------- */
    function courseMedia(course) {
        if (course.image) {
            return '<img class="lazy-img" src="' + IMG_PLACEHOLDER + '" data-src="' + esc(course.image) + '" alt="' + esc(course.title) + '" loading="lazy" decoding="async"/>';
        }
        const meta = categoryMeta(course.category_slug);
        return '<div class="media-placeholder"><span class="material-symbols-outlined">' + meta.icon + '</span></div>';
    }

    function courseCard(course) {
        const meta = categoryMeta(course.category_slug);
        const skills = (course.skills || []).slice(0, 3);
        const tags = skills.map(s => '<span class="course-tag">' + esc(s) + '</span>').join('')
            || '<span class="course-tag">' + esc(meta.label) + '</span>';
        return '' +
        '<article class="course-card course-filter-card reveal" data-title="' + esc(course.title) + '" data-category="' + esc(course.category_slug || '') + '">' +
            '<a class="course-card-image" href="#/courses/' + esc(course.slug) + '" data-link aria-label="' + esc(course.title) + '">' +
                courseMedia(course) +
                '<span class="course-card-badge">' + esc(meta.label) + '</span>' +
                '<div class="course-card-image-overlay"></div>' +
            '</a>' +
            '<div class="course-card-body">' +
                '<div class="course-card-cat"><span class="material-symbols-outlined">' + meta.icon + '</span>' + esc(meta.label) + '</div>' +
                '<h3 class="course-card-title"><a href="#/courses/' + esc(course.slug) + '" data-link>' + esc(course.title) + '</a></h3>' +
                '<p class="course-card-desc">' + esc(course.short_description || course.description || '') + '</p>' +
                '<div class="course-card-tags">' + tags + '</div>' +
                '<div class="course-card-meta">' +
                    '<span class="course-meta-item"><span class="material-symbols-outlined">schedule</span>' + esc(course.duration || 'Flexible') + '</span>' +
                    '<span class="course-meta-item"><span class="material-symbols-outlined">payments</span>' + formatFee(course.fees) + '</span>' +
                '</div>' +
                '<div class="course-card-actions">' +
                    '<a href="#/courses/' + esc(course.slug) + '" class="btn btn-primary btn-sm" data-link>View Details</a>' +
                    '<button type="button" class="btn btn-outline btn-sm" data-open-enquiry data-course="' + esc(course.slug) + '">Enquire</button>' +
                '</div>' +
            '</div>' +
        '</article>';
    }

    function facultyCard(person) {
        const exp = person.experience ? person.experience + '+ yrs experience' : '';
        return '' +
        '<article class="faculty-card reveal">' +
            '<div class="faculty-avatar">' +
                (person.photo
                    ? '<img class="lazy-img" src="' + IMG_PLACEHOLDER + '" data-src="' + esc(person.photo) + '" alt="' + esc(person.name) + '"/>'
                    : '<span>' + esc(initials(person.name)) + '</span>') +
            '</div>' +
            '<h3 class="faculty-name">' + esc(person.name) + '</h3>' +
            (person.designation ? '<div class="faculty-role">' + esc(person.designation) + '</div>' : '') +
            '<div class="faculty-meta">' +
                (person.specialization ? '<span>' + esc(person.specialization) + '</span>' : '') +
                (exp ? '<span>' + esc(exp) + '</span>' : '') +
            '</div>' +
            (person.qualification ? '<p class="faculty-qual">' + esc(person.qualification) + '</p>' : '') +
            (person.bio ? '<p class="faculty-bio">' + esc(person.bio) + '</p>' : '') +
        '</article>';
    }

    function testimonialCard(t) {
        const rating = Math.max(1, Math.min(5, Number(t.rating) || 5));
        let stars = '';
        for (let i = 0; i < rating; i++) stars += '<span class="material-symbols-outlined">star</span>';
        return '' +
        '<figure class="testimonial-card reveal">' +
            '<div class="testimonial-stars">' + stars + '</div>' +
            '<blockquote class="testimonial-text">' + esc(t.content) + '</blockquote>' +
            '<figcaption class="testimonial-author">' +
                '<div class="testimonial-avatar">' + esc(initials(t.student_name)) + '</div>' +
                '<div>' +
                    '<div class="testimonial-name">' + esc(t.student_name) + '</div>' +
                    (t.designation ? '<div class="testimonial-course">' + esc(t.designation) + '</div>' : '') +
                '</div>' +
            '</figcaption>' +
        '</figure>';
    }

    function faqItem(f, index) {
        return '' +
        '<div class="faq-item" data-faq="' + index + '">' +
            '<button type="button" class="faq-question" aria-expanded="false">' +
                '<span>' + esc(f.question) + '</span>' +
                '<span class="faq-icon"><span class="material-symbols-outlined">expand_more</span></span>' +
            '</button>' +
            '<div class="faq-answer"><div class="faq-answer-inner">' + esc(f.answer) + '</div></div>' +
        '</div>';
    }

    function sectionHeader(label, title, desc, center) {
        return '' +
        '<div class="section-header reveal' + (center ? ' center' : '') + '">' +
            '<div class="section-label">' + esc(label) + '</div>' +
            '<h2 class="section-title">' + esc(title) + '</h2>' +
            (desc ? '<p class="section-desc">' + esc(desc) + '</p>' : '') +
        '</div>';
    }

    function emptyState(icon, message) {
        return '<div class="empty-state"><span class="material-symbols-outlined">' + icon + '</span><p>' + esc(message) + '</p></div>';
    }

    function contactSection() {
        return '' +
        '<section class="section section-alt">' +
            '<div class="container">' +
                '<div class="contact-layout">' +
                    '<div class="contact-info-card">' +
                        '<h2 class="contact-heading">Get in Touch</h2>' +
                        '<div class="contact-detail">' +
                            '<div class="contact-detail-icon"><span class="material-symbols-outlined">location_on</span></div>' +
                            '<div><h4>Visit Us</h4><p data-setting="contact_address">Gamharia Chowk, Jamshedpur, Jharkhand</p></div>' +
                        '</div>' +
                        '<div class="contact-detail">' +
                            '<div class="contact-detail-icon"><span class="material-symbols-outlined">call</span></div>' +
                            '<div><h4>Call Us</h4><p><a data-setting-href="tel:contact_phone" data-setting-text="contact_phone" href="tel:+918789961355">+91 87899 61355</a></p><span>Mon&ndash;Sat, 9AM &ndash; 6PM</span></div>' +
                        '</div>' +
                        '<div class="contact-detail">' +
                            '<div class="contact-detail-icon green"><span class="material-symbols-outlined">chat</span></div>' +
                            '<div><h4>Message Us</h4><p><a data-setting-href="https://wa.me/whatsapp_number" href="https://wa.me/918789961355" target="_blank" rel="noopener">WhatsApp Support</a></p><span>Quick replies within 1 hr</span></div>' +
                        '</div>' +
                        '<div class="contact-detail">' +
                            '<div class="contact-detail-icon"><span class="material-symbols-outlined">mail</span></div>' +
                            '<div><h4>Email Us</h4><p><a data-setting-href="mailto:contact_email" data-setting-text="contact_email" href="mailto:info@globalinstitute.edu.in">info@globalinstitute.edu.in</a></p></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="contact-form-card">' +
                        '<h3 class="contact-heading sm">Send an Enquiry</h3>' +
                        '<p class="contact-sub-text">Fill in the form and our admissions team will get back to you.</p>' +
                        '<form id="contact-enquiry-form" class="form-stack">' +
                            '<div class="form-row">' +
                                '<div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-input" placeholder="Your name" required maxlength="255"/></div>' +
                                '<div class="form-group"><label class="form-label">Phone Number *</label><input type="tel" name="phone" class="form-input" placeholder="+91 00000 00000" required maxlength="20"/></div>' +
                            '</div>' +
                            '<div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input" placeholder="you@example.com" maxlength="255"/></div>' +
                            '<div class="form-group"><label class="form-label">Course of Interest</label><select name="course_slug" class="form-input" data-course-select><option value="">Select a course&hellip;</option><option value="other">Other / Not Sure</option></select></div>' +
                            '<div class="form-group"><label class="form-label">Message</label><textarea name="message" class="form-input" rows="4" placeholder="How can we help you?" maxlength="1000"></textarea></div>' +
                            '<button type="submit" class="btn btn-primary btn-full">Send Enquiry</button>' +
                        '</form>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</section>';
    }

    function mapSection() {
        return '' +
        '<section class="map-section">' +
            '<div class="map-canvas">' +
                '<div class="map-pin">' +
                    '<div class="map-card"><strong data-setting="site_name">Global Institute</strong><span data-setting="contact_address">Gamharia Chowk, Jamshedpur</span></div>' +
                    '<span class="material-symbols-outlined">location_on</span>' +
                '</div>' +
            '</div>' +
        '</section>';
    }

    /* --------------------------------------------------------
       Pages
       -------------------------------------------------------- */
    const Pages = {

        notFound() {
            document.title = 'Page Not Found | Global Institute & CAD CAM';
            document.getElementById('app').innerHTML = '' +
            '<div class="state-page">' +
                '<span class="material-symbols-outlined state-icon">search_off</span>' +
                '<h1>Page Not Found</h1>' +
                '<p>The page you are looking for does not exist or has been moved.</p>' +
                '<a href="#/" class="btn btn-primary" data-link>Back to Home</a>' +
            '</div>';
        },

        async home() {
            document.title = 'Global Institute & CAD CAM | Industrial Training & Professional Certification';
            const [courses, faculty, facilities, testimonials, faqs, announcements] = await Promise.all([
                Store.courses(), Store.faculty(), Store.facilities(),
                Store.testimonials(), Store.faqs(), Store.announcements()
            ]);

            const labCount = facilities.filter(f => /lab|workshop/.test(f.type)).length || facilities.length;
            const featured = (courses.filter(c => c.featured).length ? courses.filter(c => c.featured) : courses).slice(0, 6);

            const categories = [];
            courses.forEach(c => {
                if (c.category_slug && !categories.some(x => x.slug === c.category_slug)) {
                    categories.push({ slug: c.category_slug, name: c.category, count: courses.filter(x => x.category_slug === c.category_slug).length });
                }
            });

            const heroStats = [
                { value: courses.length, label: 'Programs' },
                { value: faculty.length, label: 'Expert Faculty' },
                { value: labCount, label: 'Training Labs' },
                { value: categories.length, label: 'Skill Domains' },
            ];

            const trustItems = [
                ['factory', 'Industry-Aligned Curriculum'],
                ['construction', 'Hands-On Training'],
                ['workspace_premium', 'Certification'],
                ['trending_up', 'Placement Assistance'],
            ];

            const categoryCards = categories.map(c => {
                const meta = categoryMeta(c.slug);
                return '<a class="category-card reveal" href="#/courses?category=' + esc(c.slug) + '">' +
                    '<div class="category-card-icon"><span class="material-symbols-outlined">' + meta.icon + '</span></div>' +
                    '<h3>' + esc(c.name) + '</h3>' +
                    '<p>' + c.count + ' program' + (c.count === 1 ? '' : 's') + '</p>' +
                    '<span class="category-card-arrow"><span class="material-symbols-outlined">arrow_forward</span></span>' +
                '</a>';
            }).join('');

            const whyCards = [
                ['precision_manufacturing', 'Industry-Oriented Curriculum', 'Training built around practical industry requirements, not just theory.'],
                ['build', 'Hands-On Learning', 'Practical sessions on industry-standard software and equipment.'],
                ['groups', 'Experienced Trainers', 'Learn from instructors with real design and manufacturing experience.'],
                ['workspace_premium', 'Industry Certification', 'Build credible proof of your skills that employers recognise.'],
                ['trending_up', 'Career Focus', 'Training aligned with employment opportunities and professional growth.'],
                ['schedule', 'Flexible Batches', 'Morning and evening batch timings designed for students and professionals.'],
            ].map(w => '<div class="why-card reveal"><div class="why-card-icon"><span class="material-symbols-outlined">' + w[0] + '</span></div><div><h3>' + esc(w[1]) + '</h3><p>' + esc(w[2]) + '</p></div></div>').join('');

            const steps = [
                ['menu_book', 'Learn', 'Understand the fundamentals with expert trainers.'],
                ['science', 'Practice', 'Work with industry-standard tools and machines.'],
                ['construction', 'Build', 'Complete practical, real-world projects.'],
                ['workspace_premium', 'Certify', 'Earn industry-recognised certification.'],
                ['trending_up', 'Grow', 'Advance your career with in-demand skills.'],
            ].map((s, i) => '<div class="journey-step reveal reveal-delay-' + (i + 1) + '"><span class="material-symbols-outlined">' + s[0] + '</span><div class="journey-step-number">0' + (i + 1) + '</div><div class="journey-step-line"></div><h3>' + esc(s[1]) + '</h3><p>' + esc(s[2]) + '</p></div>').join('');

            const newsItems = announcements.length
                ? announcements.map(a => '<div class="news-item reveal"><div class="news-date"><span class="material-symbols-outlined">campaign</span>' + (a.date ? esc(a.date) : 'Update') + '</div><h3>' + esc(a.title) + '</h3><p>' + esc(a.message) + '</p>' + (a.button_url ? '<a class="news-link" href="' + esc(a.button_url) + '">' + esc(a.button_text || 'Read more') + ' <span class="material-symbols-outlined">arrow_forward</span></a>' : '') + '</div>').join('')
                : '';

            const facultyPreview = faculty.slice(0, 4).map(facultyCard).join('');
            const facilityPreview = facilities.slice(0, 4).map(f => {
                const meta = facilityMeta(f.type);
                return '<div class="facility-chip reveal"><span class="material-symbols-outlined">' + meta.icon + '</span><div><strong>' + esc(meta.label) + '</strong><span>' + esc(f.capacity ? 'Capacity ' + f.capacity : 'Available') + '</span></div></div>';
            }).join('');

            const app = document.getElementById('app');
            app.innerHTML = '' +
            '<section class="hero">' +
                '<div class="hero-bg-pattern"></div>' +
                '<div class="container hero-grid">' +
                    '<div class="hero-content">' +
                        '<div class="hero-badge"><span class="hero-badge-dot"></span>Industrial Technical Training</div>' +
                        '<h1 class="hero-title">Build <span class="hero-title-accent">industry-ready skills.</span> Build your career.</h1>' +
                        '<p class="hero-desc">Practical, hands-on training in CAD/CAM, CNC programming and industrial skills &mdash; delivered at our Gamharia, Jamshedpur campus with expert faculty and industry-standard labs.</p>' +
                        '<div class="hero-actions">' +
                            '<a href="#/courses" class="btn btn-secondary btn-lg" data-link>Explore Programs</a>' +
                            '<button type="button" class="btn btn-outline-light btn-lg" data-open-enquiry>Talk to an Advisor</button>' +
                        '</div>' +
                        '<div class="hero-stats-row">' +
                            heroStats.map(s => '<div><div class="hero-stat-value">' + s.value + '</div><div class="hero-stat-label">' + esc(s.label) + '</div></div>').join('') +
                        '</div>' +
                    '</div>' +
                    '<div class="hero-visual">' +
                        '<div class="hero-panel">' +
                            '<div class="hero-panel-grid blueprint-grid"></div>' +
                            '<div class="hero-panel-head"><span class="material-symbols-outlined">precision_manufacturing</span><span>Training Environment</span></div>' +
                            '<div class="hero-panel-chips">' +
                                facilities.slice(0, 6).map(f => '<span class="hero-chip"><span class="material-symbols-outlined">' + facilityMeta(f.type).icon + '</span>' + esc(f.name) + '</span>').join('') +
                            '</div>' +
                        '</div>' +
                        '<div class="hero-visual-float hero-visual-float-1"><span class="material-symbols-outlined">workspace_premium</span><div><strong>Certified Programs</strong><span>Industry-recognised</span></div></div>' +
                        '<div class="hero-visual-float hero-visual-float-2"><span class="material-symbols-outlined">engineering</span><div><strong>Hands-On Learning</strong><span>Practical training</span></div></div>' +
                    '</div>' +
                '</div>' +
            '</section>' +

            '<section class="trust-bar"><div class="trust-items">' +
                trustItems.map(t => '<div class="trust-item"><span class="material-symbols-outlined">' + t[0] + '</span><span>' + esc(t[1]) + '</span></div>').join('') +
            '</div></section>' +

            (newsItems ? '<section class="section"><div class="container">' + sectionHeader('What\'s New', 'News & Announcements', 'The latest updates from the institute.') + '<div class="news-grid">' + newsItems + '</div></div></section>' : '') +

            '<section class="section">' +
                '<div class="container">' +
                    sectionHeader('Our Domains', 'Training built around real industry skills', 'Explore our range of technical programs designed to make you job-ready.') +
                    '<div class="grid-4">' + categoryCards + '</div>' +
                '</div>' +
            '</section>' +

            '<section class="section section-alt">' +
                '<div class="container">' +
                    sectionHeader('Most Popular', 'Featured programs', 'Our most sought-after courses, ready for enrolment.') +
                    '<div class="grid-3">' + featured.map(courseCard).join('') + '</div>' +
                    '<div class="section-cta"><a href="#/courses" class="btn btn-primary" data-link>View all programs <span class="material-symbols-outlined">arrow_forward</span></a></div>' +
                '</div>' +
            '</section>' +

            '<section class="section">' +
                '<div class="container">' +
                    sectionHeader('Your Path', 'The learning journey', 'A structured path from learning to career growth.', true) +
                    '<div class="journey-timeline">' + steps + '</div>' +
                '</div>' +
            '</section>' +

            '<section class="section section-alt">' +
                '<div class="container">' +
                    sectionHeader('Why Us', 'Why students choose our training', 'We combine industry expertise with practical learning to deliver real outcomes.') +
                    '<div class="grid-2">' + whyCards + '</div>' +
                '</div>' +
            '</section>' +

            '<section class="section">' +
                '<div class="container">' +
                    sectionHeader('Our People', 'Learn from experienced faculty', 'Qualified trainers with real design and manufacturing experience.') +
                    '<div class="grid-4">' + facultyPreview + '</div>' +
                    '<div class="section-cta"><a href="#/faculty" class="btn btn-outline" data-link>Meet the full faculty <span class="material-symbols-outlined">arrow_forward</span></a></div>' +
                '</div>' +
            '</section>' +

            '<section class="section section-alt">' +
                '<div class="container">' +
                    '<div class="split-feature">' +
                        '<div>' +
                            sectionHeader('Campus', 'Purpose-built training facilities', 'Learn in labs and workshops that mirror modern production environments.') +
                            '<div class="facility-chips">' + (facilityPreview || emptyState('apartment', 'Facilities coming soon.')) + '</div>' +
                            '<div class="section-cta left"><a href="#/facilities" class="btn btn-outline" data-link>Explore facilities <span class="material-symbols-outlined">arrow_forward</span></a></div>' +
                        '</div>' +
                        '<div class="split-feature-media">' +
                            '<div class="media-placeholder tall"><span class="material-symbols-outlined">precision_manufacturing</span></div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</section>' +

            (testimonials.length ?
            '<section class="section">' +
                '<div class="container">' +
                    sectionHeader('Testimonials', 'What our students say', 'Real experiences from students who trained with us.') +
                    '<div class="grid-3">' + testimonials.slice(0, 6).map(testimonialCard).join('') + '</div>' +
                '</div>' +
            '</section>' : '') +

            (faqs.length ?
            '<section class="section section-alt">' +
                '<div class="container container-narrow">' +
                    sectionHeader('FAQs', 'Frequently asked questions', 'Answers to common questions about our programs.', true) +
                    '<div class="faq-list">' + faqs.slice(0, 6).map(faqItem).join('') + '</div>' +
                '</div>' +
            '</section>' : '') +

            '<section class="cta-section">' +
                '<div class="container cta-inner">' +
                    '<h2 class="section-title">Ready to build industry-ready skills?</h2>' +
                    '<p class="section-desc">Choose the right program and take the next step toward your professional goals.</p>' +
                    '<div class="section-cta"><a href="#/courses" class="btn btn-secondary btn-lg" data-link>Explore Programs</a><button type="button" class="btn btn-outline-light btn-lg" data-open-enquiry>Enquire Now</button></div>' +
                '</div>' +
            '</section>';

            initFaq();
            initReveal();
        },

        async courses() {
            document.title = 'Courses | Global Institute & CAD CAM';
            const courses = await Store.courses();
            const categories = [];
            courses.forEach(c => {
                if (c.category_slug && !categories.some(x => x.slug === c.category_slug)) {
                    categories.push({ slug: c.category_slug, name: c.category, icon: categoryMeta(c.category_slug).icon });
                }
            });

            const filterHtml = categories.map(c =>
                '<label class="filter-item"><input type="checkbox" class="category-filter" data-category="' + esc(c.slug) + '"/><span class="material-symbols-outlined">' + c.icon + '</span>' + esc(c.name) + '</label>'
            ).join('');

            const app = document.getElementById('app');
            app.innerHTML = '' +
            '<div class="container page-wrap">' +
                '<nav class="breadcrumb"><a href="#/" data-link>Home</a><span class="material-symbols-outlined">chevron_right</span><span>Courses</span></nav>' +
                '<header class="page-header">' +
                    '<h1>Our Courses</h1>' +
                    '<p>Industry-standard CAD/CAM, CNC, industrial and computer skill programs &mdash; ' + courses.length + ' programs available.</p>' +
                '</header>' +
                '<div class="courses-layout">' +
                    '<aside class="courses-sidebar">' +
                        '<div class="sidebar-card">' +
                            '<label class="filter-label" for="course-search">Search Courses</label>' +
                            '<div class="search-input-wrapper"><span class="material-symbols-outlined">search</span>' +
                            '<input type="text" id="course-search" class="form-input course-search-input" placeholder="Enter keyword&hellip;"/></div>' +
                        '</div>' +
                        (categories.length ?
                        '<div class="sidebar-card"><label class="filter-label">Categories</label><div class="filter-group">' +
                            '<label class="filter-item"><input type="checkbox" class="category-filter" data-category="all"/><span class="material-symbols-outlined">apps</span>All Courses</label>' +
                            filterHtml +
                        '</div></div>' : '') +
                        '<div class="counselling-card">' +
                            '<div class="counselling-head"><span class="material-symbols-outlined">support_agent</span><h3>Free Counselling</h3></div>' +
                            '<p>Not sure which program suits you? Speak to our advisors.</p>' +
                            '<button type="button" class="btn btn-outline btn-full" data-open-enquiry>Book a Call</button>' +
                        '</div>' +
                        '<div class="lead-magnet-card">' +
                            '<span class="material-symbols-outlined">description</span>' +
                            '<h3>Need details?</h3>' +
                            '<p>Request the syllabus, fees and batch details for any program.</p>' +
                            '<button type="button" class="btn btn-secondary btn-full" data-open-enquiry>Request Syllabus</button>' +
                        '</div>' +
                    '</aside>' +
                    '<div class="courses-grid" id="courses-grid">' +
                        (courses.length ? courses.map(courseCard).join('') : emptyState('school', 'Courses will be published soon.')) +
                    '</div>' +
                '</div>' +
                '<div class="empty-state courses-empty" id="courses-empty" hidden><span class="material-symbols-outlined">search_off</span><p>No courses match your filters.</p></div>' +
            '</div>';

            initCourseFilter();
            initReveal();
        },

        async courseDetail(slug) {
            const course = await Store.course(slug);
            if (!course) return Pages.notFound();

            document.title = (course.title || 'Course') + ' | Global Institute & CAD CAM';
            const meta = categoryMeta(course.category_slug);
            const skills = course.skills || [];

            const curriculum = skills.map((s, i) =>
                '<div class="curriculum-card reveal"><span class="material-symbols-outlined">' + (i % 2 ? 'verified' : 'menu_book') + '</span><h3>' + esc(s) + '</h3></div>'
            ).join('');

            const career = (course.career_information || '')
                .split(/[,;]\s*/).map(s => s.trim()).filter(Boolean)
                .map(c => '<span class="career-tag">' + esc(c) + '</span>').join('');

            const app = document.getElementById('app');
            app.innerHTML = '' +
            '<div class="container page-wrap">' +
                '<nav class="breadcrumb"><a href="#/" data-link>Home</a><span class="material-symbols-outlined">chevron_right</span><a href="#/courses" data-link>Courses</a><span class="material-symbols-outlined">chevron_right</span><span>' + esc(course.title) + '</span></nav>' +
                '<section class="course-hero">' +
                    '<div class="blueprint-grid course-hero-grid-bg"></div>' +
                    '<div class="container course-hero-grid">' +
                        '<div class="course-hero-info">' +
                            '<span class="badge badge-primary"><span class="material-symbols-outlined">' + meta.icon + '</span>' + esc(meta.label) + '</span>' +
                            '<h1>' + esc(course.title) + '</h1>' +
                            '<p>' + esc(course.short_description || '') + '</p>' +
                            '<div class="course-meta-grid">' +
                                '<div class="course-meta-item"><span class="course-meta-label">Duration</span><span class="course-meta-value">' + esc(course.duration || 'Flexible') + '</span></div>' +
                                '<div class="course-meta-item"><span class="course-meta-label">Category</span><span class="course-meta-value">' + esc(meta.label) + '</span></div>' +
                                '<div class="course-meta-item"><span class="course-meta-label">Investment</span><span class="course-meta-value">' + formatFee(course.fees) + '</span></div>' +
                                '<div class="course-meta-item"><span class="course-meta-label">Certificate</span><span class="course-meta-value">Included</span></div>' +
                            '</div>' +
                            '<div class="course-hero-actions">' +
                                '<button type="button" class="btn btn-secondary" data-open-enquiry data-course="' + esc(course.slug) + '">Request Syllabus <span class="material-symbols-outlined">download</span></button>' +
                                '<button type="button" class="btn btn-outline" data-open-enquiry data-course="' + esc(course.slug) + '">Enquire Now</button>' +
                            '</div>' +
                        '</div>' +
                        '<div class="course-hero-media">' + courseMedia(course) + '</div>' +
                    '</div>' +
                '</section>' +

                '<section class="section">' +
                    '<div class="course-detail-layout">' +
                        '<div class="course-content">' +
                            (course.overview ? '<div class="course-block"><h2><span class="material-symbols-outlined">info</span> Course Overview</h2><p>' + esc(course.overview) + '</p></div>' : '') +
                            (course.description ? '<div class="course-block"><h2><span class="material-symbols-outlined">description</span> About this program</h2><p>' + esc(course.description) + '</p></div>' : '') +
                            (curriculum ? '<div class="course-block"><h2><span class="material-symbols-outlined">menu_book</span> What you will learn</h2><div class="curriculum-grid">' + curriculum + '</div></div>' : '') +
                            (career ? '<div class="course-block"><h2><span class="material-symbols-outlined">work</span> Career pathways</h2><p>Roles our graduates are prepared for:</p><div class="career-tags">' + career + '</div></div>' : '') +
                        '</div>' +
                        '<aside class="course-sidebar">' +
                            '<div class="fee-card">' +
                                '<div class="fee-label">Course Investment</div>' +
                                '<div class="fee-amount">' + formatFee(course.fees) + '</div>' +
                                '<ul class="fee-list">' +
                                    (course.duration ? '<li class="fee-list-item"><span class="material-symbols-outlined">schedule</span>Duration: ' + esc(course.duration) + '</li>' : '') +
                                    (course.eligibility ? '<li class="fee-list-item"><span class="material-symbols-outlined">school</span>Eligibility: ' + esc(course.eligibility) + '</li>' : '') +
                                    '<li class="fee-list-item"><span class="material-symbols-outlined">workspace_premium</span>Industry certificate included</li>' +
                                '</ul>' +
                                '<button type="button" class="btn btn-primary btn-full" data-open-enquiry data-course="' + esc(course.slug) + '">Enrol Now</button>' +
                                '<button type="button" class="btn btn-outline btn-full" data-open-enquiry data-course="' + esc(course.slug) + '"><span class="material-symbols-outlined">call</span> Request Callback</button>' +
                            '</div>' +
                            (course.batch_information ? '<div class="batch-card"><h3><span class="material-symbols-outlined">calendar_month</span> Batches</h3><p>' + esc(course.batch_information) + '</p></div>' : '') +
                            (course.eligibility ? '<div class="batch-card"><h3><span class="material-symbols-outlined">how_to_reg</span> Eligibility</h3><p>' + esc(course.eligibility) + '</p></div>' : '') +
                        '</aside>' +
                    '</div>' +
                '</section>' +
            '</div>' +
            contactSection();

            initReveal();
        },

        async faculty() {
            document.title = 'Faculty | Global Institute & CAD CAM';
            const faculty = await Store.faculty();
            const app = document.getElementById('app');
            app.innerHTML = '' +
            '<div class="container page-wrap">' +
                '<nav class="breadcrumb"><a href="#/" data-link>Home</a><span class="material-symbols-outlined">chevron_right</span><span>Faculty</span></nav>' +
                '<header class="page-header"><h1>Our Faculty</h1><p>Qualified trainers and industry professionals who guide every batch.</p></header>' +
                (faculty.length ? '<div class="grid-4">' + faculty.map(facultyCard).join('') + '</div>' : emptyState('groups', 'Faculty profiles will be published soon.')) +
                '<section class="cta-banner">' +
                    '<h2 class="section-title">Want to learn from our experts?</h2>' +
                    '<p class="section-desc">Explore the programs they teach and reserve your seat.</p>' +
                    '<div class="section-cta"><a href="#/courses" class="btn btn-secondary" data-link>View Programs</a><button type="button" class="btn btn-outline-light" data-open-enquiry>Enquire Now</button></div>' +
                '</section>' +
            '</div>';
            initReveal();
        },

        async facilities() {
            document.title = 'Facilities | Global Institute & CAD CAM';
            const facilities = await Store.facilities();
            const grouped = {};
            facilities.forEach(f => { (grouped[f.type] = grouped[f.type] || []).push(f); });

            const groupsHtml = Object.keys(grouped).map(type => {
                const meta = facilityMeta(type);
                const rooms = grouped[type];
                const total = rooms.reduce((sum, r) => sum + (r.capacity || 0), 0);
                return '<div class="facility-group reveal">' +
                    '<div class="facility-group-head"><span class="material-symbols-outlined">' + meta.icon + '</span><div><h3>' + esc(meta.label) + '</h3><span>' + rooms.length + ' space' + (rooms.length === 1 ? '' : 's') + (total ? ' &middot; ' + total + ' seats' : '') + '</span></div></div>' +
                    '<ul class="facility-list">' + rooms.map(r => '<li><span class="material-symbols-outlined">check</span>' + esc(r.name) + (r.capacity ? ' <em>(' + r.capacity + ' capacity)</em>' : '') + '</li>').join('') + '</ul>' +
                '</div>';
            }).join('');

            const app = document.getElementById('app');
            app.innerHTML = '' +
            '<div class="container page-wrap">' +
                '<nav class="breadcrumb"><a href="#/" data-link>Home</a><span class="material-symbols-outlined">chevron_right</span><span>Facilities</span></nav>' +
                '<header class="page-header"><h1>Campus Facilities</h1><p>Purpose-built labs and workshops that mirror modern production environments.</p></header>' +
                (facilities.length ?
                    '<div class="grid-3">' + groupsHtml + '</div>' +
                    '<section class="facility-highlights">' +
                        sectionHeader('Highlights', 'Learning by doing', 'Our training spaces support extensive practical sessions across every program.') +
                        '<div class="grid-3">' +
                            '<div class="why-card reveal"><div class="why-card-icon"><span class="material-symbols-outlined">precision_manufacturing</span></div><div><h3>Industry-Standard Equipment</h3><p>Train on the software and machines used by leading employers.</p></div></div>' +
                            '<div class="why-card reveal"><div class="why-card-icon"><span class="material-symbols-outlined">groups</span></div><div><h3>Small Batch Sizes</h3><p>Focused attention for every learner in every practical session.</p></div></div>' +
                            '<div class="why-card reveal"><div class="why-card-icon"><span class="material-symbols-outlined">health_and_safety</span></div><div><h3>Safe Environment</h3><p>Safety standards maintained across all training areas.</p></div></div>' +
                        '</div>' +
                    '</section>'
                : emptyState('apartment', 'Facility details will be published soon.')) +
            '</div>';
            initReveal();
        },

        async about() {
            document.title = 'About Us | Global Institute & CAD CAM';
            const [faculty, facilities, courses] = await Promise.all([Store.faculty(), Store.facilities(), Store.courses()]);
            const values = [
                ['precision_manufacturing', 'Technical Excellence', 'Industry-grade equipment and curriculum developed with manufacturing partners.'],
                ['groups', 'Student-Centred', 'Small batch sizes, personal attention and mentorship-driven learning.'],
                ['handshake', 'Industry Partnership', 'Collaboration with manufacturing firms for curriculum and placements.'],
                ['emoji_events', 'Results Oriented', 'Measurable outcomes supported by placement assistance.'],
                ['security', 'Safety First', 'Strong safety standards across every practical environment.'],
                ['eco', 'Sustainability', 'Responsible, forward-looking training practices.'],
            ].map(v => '<div class="value-card reveal"><span class="material-symbols-outlined">' + v[0] + '</span><h3>' + esc(v[1]) + '</h3><p>' + esc(v[2]) + '</p></div>').join('');

            const app = document.getElementById('app');
            app.innerHTML = '' +
            '<div class="container page-wrap">' +
                '<nav class="breadcrumb"><a href="#/" data-link>Home</a><span class="material-symbols-outlined">chevron_right</span><span>About</span></nav>' +
                '<header class="page-header"><h1>About Global Institute</h1><p>A technical training institute in Gamharia, Jamshedpur, Jharkhand.</p></header>' +

                '<section class="about-mission">' +
                    '<div class="mission-card primary">' +
                        '<div class="blueprint-dark mission-bg"></div>' +
                        '<span class="material-symbols-outlined">flag</span>' +
                        '<h2>Our Mission</h2>' +
                        '<p>To bridge the gap between academic education and industry requirements by providing practical technical training that equips youth with job-ready skills for the modern manufacturing sector.</p>' +
                    '</div>' +
                    '<div class="mission-card">' +
                        '<span class="material-symbols-outlined">visibility</span>' +
                        '<h2>Our Vision</h2>' +
                        '<p>To be a leading skill-development institute in Eastern India, producing industry-ready professionals who contribute to India\u2019s manufacturing and engineering excellence.</p>' +
                    '</div>' +
                '</section>' +

                '<section class="stats-row">' +
                    '<div class="stat-card"><div class="stat-value">' + courses.length + '</div><div class="stat-label">Programs</div></div>' +
                    '<div class="stat-card"><div class="stat-value">' + faculty.length + '</div><div class="stat-label">Faculty</div></div>' +
                    '<div class="stat-card"><div class="stat-value">' + facilities.length + '</div><div class="stat-label">Labs & Rooms</div></div>' +
                    '<div class="stat-card"><div class="stat-value">100%</div><div class="stat-label">Placement Assistance</div></div>' +
                '</section>' +

                '<section class="section">' + sectionHeader('Our Values', 'What we stand for', 'The principles behind every training program we run.') + '<div class="about-values-grid">' + values + '</div></section>' +
                '<section class="section">' + sectionHeader('Infrastructure', 'Where you will train', '') + '<div class="facility-chips">' + facilities.map(f => '<div class="facility-chip"><span class="material-symbols-outlined">' + facilityMeta(f.type).icon + '</span><div><strong>' + esc(f.name) + '</strong><span>' + esc(facilityMeta(f.type).label) + (f.capacity ? ' &middot; ' + f.capacity : '') + '</span></div></div>').join('') + '</div></section>' +
            '</div>';
            initReveal();
        },

        async admissions() {
            document.title = 'Admissions | Global Institute & CAD CAM';
            const faqs = await Store.faqs();
            const admissionsFaqs = faqs.filter(f => /admission|fee|eligib/i.test(f.category || '') || /eligib|fee|admission/i.test(f.question || ''));
            const faqSource = admissionsFaqs.length ? admissionsFaqs : faqs;

            const process = [
                ['edit_note', 'Application', 'Complete the enquiry form and share your details.'],
                ['support_agent', 'Counselling', 'Our advisors help you choose the right program and batch.'],
                ['task_alt', 'Enrollment', 'Confirm your seat and begin your training.'],
            ].map((p, i) => '<div class="process-card reveal"><span class="process-number">0' + (i + 1) + '</span><div class="process-icon"><span class="material-symbols-outlined">' + p[0] + '</span></div><h3>' + esc(p[1]) + '</h3><p>' + esc(p[2]) + '</p></div>').join('');

            const app = document.getElementById('app');
            app.innerHTML = '' +
            '<div class="container page-wrap">' +
                '<nav class="breadcrumb"><a href="#/" data-link>Home</a><span class="material-symbols-outlined">chevron_right</span><span>Admissions</span></nav>' +
                '<header class="page-header"><h1>Admissions</h1><p data-setting="enrollment_message">Admissions are now open for upcoming batches. Contact us today!</p></header>' +
                '<section class="admission-grid">' +
                    '<div class="admission-info">' +
                        '<span class="badge badge-secondary"><span class="material-symbols-outlined">event_available</span>Admissions Open</span>' +
                        '<h2>Start your training journey</h2>' +
                        '<ul class="check-list">' +
                            '<li><span class="material-symbols-outlined">check_circle</span>Programs for students, diploma holders, ITI graduates and working professionals.</li>' +
                            '<li><span class="material-symbols-outlined">check_circle</span>Hands-on training with industry-standard software and equipment.</li>' +
                            '<li><span class="material-symbols-outlined">check_circle</span>Flexible morning and evening batch timings.</li>' +
                            '<li><span class="material-symbols-outlined">check_circle</span>Placement assistance, resume help and interview preparation.</li>' +
                        '</ul>' +
                    '</div>' +
                    '<div class="admission-form-card">' +
                        '<h3>Free Counselling Session</h3>' +
                        '<p>Speak with our admission advisors to find the right technical path.</p>' +
                        '<form id="admissions-form" class="form-stack">' +
                            '<div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-input" placeholder="Your name" required maxlength="255"/></div>' +
                            '<div class="form-group"><label class="form-label">Phone Number *</label><input type="tel" name="phone" class="form-input" placeholder="+91 00000 00000" required maxlength="20"/></div>' +
                            '<div class="form-group"><label class="form-label">Course of Interest</label><select name="course_slug" class="form-input" data-course-select><option value="">Select a course&hellip;</option><option value="other">Other / Not Sure</option></select></div>' +
                            '<div class="form-group"><label class="form-label">Message</label><textarea name="message" class="form-input" rows="3" placeholder="Tell us about your goals" maxlength="1000"></textarea></div>' +
                            '<button type="submit" class="btn btn-secondary btn-full">Request Callback</button>' +
                        '</form>' +
                    '</div>' +
                '</section>' +
                '<section class="section">' + sectionHeader('Simplified Path', 'Admission process', '', true) + '<div class="grid-3">' + process + '</div></section>' +
                (faqSource.length ? '<section class="section section-alt"><div class="container container-narrow">' + sectionHeader('FAQs', 'Admissions FAQ', '', true) + '<div class="faq-list">' + faqSource.map(faqItem).join('') + '</div></div></section>' : '') +
            '</div>';

            wireForm('admissions-form', { extra: () => ({ source: 'admissions' }), successMessage: 'Counselling request submitted. We will call you shortly.' });
            initFaq();
            initReveal();
        },

        async gallery() {
            document.title = 'Gallery | Global Institute & CAD CAM';
            const images = await Store.gallery();
            const categories = [];
            images.forEach(g => { if (g.category && !categories.includes(g.category)) categories.push(g.category); });

            const filters = ['All'].concat(categories).map((c, i) =>
                '<button type="button" class="gallery-filter-btn' + (i === 0 ? ' active' : '') + '" data-gallery-filter="' + esc(c) + '">' + esc(c) + '</button>'
            ).join('');

            const items = images.map((g, i) =>
                '<figure class="gallery-item reveal" data-gallery-index="' + i + '" data-gallery-cat="' + esc(g.category) + '">' +
                    '<img class="lazy-img" src="' + IMG_PLACEHOLDER + '" data-src="' + esc(g.image) + '" alt="' + esc(g.alt_text || g.title || 'Campus image') + '" loading="lazy"/>' +
                    '<figcaption class="gallery-item-overlay"><span class="gallery-item-cat">' + esc(g.category) + '</span><span class="gallery-item-title">' + esc(g.title) + '</span></figcaption>' +
                '</figure>'
            ).join('');

            const app = document.getElementById('app');
            app.innerHTML = '' +
            '<div class="container page-wrap">' +
                '<nav class="breadcrumb"><a href="#/" data-link>Home</a><span class="material-symbols-outlined">chevron_right</span><span>Gallery</span></nav>' +
                '<header class="page-header"><h1>Gallery</h1><p>A look at our campus, labs and training sessions.</p></header>' +
                (images.length ?
                    (categories.length > 1 ? '<div class="gallery-filters">' + filters + '</div>' : '') +
                    '<div class="gallery-grid">' + items + '</div>'
                : emptyState('photo_library', 'Gallery images will be added soon.')) +
            '</div>' +
            '<div class="lightbox" id="lightbox" aria-hidden="true"><button class="lightbox-close" aria-label="Close"><span class="material-symbols-outlined">close</span></button><img alt=""/></div>';

            initGallery(images);
            initReveal();
        },

        async contact() {
            document.title = 'Contact | Global Institute & CAD CAM';
            document.getElementById('app').innerHTML = '' +
            '<div class="container page-wrap">' +
                '<nav class="breadcrumb"><a href="#/" data-link>Home</a><span class="material-symbols-outlined">chevron_right</span><span>Contact</span></nav>' +
                '<header class="page-header"><h1>Contact Us</h1><p>Get in touch with our admissions team to start your journey in technical training.</p></header>' +
            '</div>' +
            contactSection() +
            mapSection();

            wireForm('contact-enquiry-form');
        }
    };

    /* --------------------------------------------------------
       Page behaviours
       -------------------------------------------------------- */
    function initReveal() {
        const els = document.querySelectorAll('.reveal:not(.visible)');
        if (!('IntersectionObserver' in window)) { els.forEach(e => e.classList.add('visible')); return; }
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) { entry.target.classList.add('visible'); obs.unobserve(entry.target); }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
        els.forEach(el => observer.observe(el));
    }

    function initFaq() {
        document.querySelectorAll('.faq-question').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = btn.closest('.faq-item');
                const isOpen = item.classList.contains('open');
                document.querySelectorAll('.faq-item.open').forEach(i => { i.classList.remove('open'); const q = i.querySelector('.faq-question'); if (q) q.setAttribute('aria-expanded', 'false'); });
                if (!isOpen) { item.classList.add('open'); btn.setAttribute('aria-expanded', 'true'); }
            });
        });
    }

    function initCourseFilter() {
        const searchInput = document.querySelector('.course-search-input');
        const checkboxes = document.querySelectorAll('.category-filter');
        const cards = document.querySelectorAll('.course-filter-card');
        const emptyEl = document.getElementById('courses-empty');
        if (!cards.length) return;

        function apply() {
            const search = (searchInput ? searchInput.value : '').toLowerCase().trim();
            const active = Array.from(checkboxes).filter(cb => cb.checked && cb.dataset.category !== 'all').map(cb => cb.dataset.category);
            const allChecked = Array.from(checkboxes).some(cb => cb.dataset.category === 'all' && cb.checked);
            let visible = 0;
            cards.forEach(card => {
                const title = (card.dataset.title || '').toLowerCase();
                const category = card.dataset.category || '';
                const matchSearch = !search || title.includes(search);
                const matchCategory = active.length === 0 || allChecked || active.includes(category);
                const show = matchSearch && matchCategory;
                card.hidden = !show;
                if (show) visible++;
            });
            if (emptyEl) emptyEl.hidden = visible !== 0;
        }

        if (searchInput) searchInput.addEventListener('input', apply);
        checkboxes.forEach(cb => cb.addEventListener('change', () => {
            if (cb.dataset.category === 'all' && cb.checked) {
                checkboxes.forEach(other => { if (other !== cb) other.checked = false; });
            } else if (cb.checked) {
                checkboxes.forEach(other => { if (other.dataset.category === 'all') other.checked = false; });
            }
            apply();
        }));
        apply();
    }

    function initGallery(images) {
        const filters = document.querySelectorAll('[data-gallery-filter]');
        const items = document.querySelectorAll('.gallery-item');
        filters.forEach(btn => btn.addEventListener('click', () => {
            filters.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const cat = btn.dataset.galleryFilter;
            items.forEach(item => { item.hidden = !(cat === 'All' || item.dataset.galleryCat === cat); });
        }));

        const lightbox = document.getElementById('lightbox');
        if (!lightbox) return;
        const img = lightbox.querySelector('img');
        document.querySelectorAll('.gallery-item').forEach(item => {
            item.addEventListener('click', () => {
                const data = images[Number(item.dataset.galleryIndex)];
                if (!data) return;
                img.src = data.image;
                img.alt = data.alt_text || data.title || '';
                lightbox.classList.add('active');
                lightbox.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            });
        });
        function close() { lightbox.classList.remove('active'); lightbox.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; }
        lightbox.addEventListener('click', e => { if (e.target === lightbox || e.target.closest('.lightbox-close')) close(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    }

    /* --------------------------------------------------------
       Navigation shell wiring
       -------------------------------------------------------- */
    function initMobileNav() {
        const menuBtn = document.getElementById('mobile-menu-btn');
        const overlay = document.getElementById('mobile-nav-overlay');
        const closeBtn = document.getElementById('mobile-close-btn');
        if (!menuBtn || !overlay) return;
        const open = () => { overlay.classList.add('active'); document.body.style.overflow = 'hidden'; menuBtn.setAttribute('aria-expanded', 'true'); };
        const close = () => { overlay.classList.remove('active'); document.body.style.overflow = ''; menuBtn.setAttribute('aria-expanded', 'false'); };
        menuBtn.addEventListener('click', open);
        closeBtn && closeBtn.addEventListener('click', close);
        overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
        overlay.querySelectorAll('[data-link]').forEach(a => a.addEventListener('click', close));
        window.closeMobileNav = close;
    }

    function initCompactNav() {
        const nav = document.getElementById('main-nav');
        if (!nav) return;
        const onScroll = () => nav.classList.toggle('compact', window.scrollY > 40);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    function initGlobalHandlers() {
        document.addEventListener('click', e => {
            const enquiry = e.target.closest('[data-open-enquiry]');
            if (enquiry) {
                e.preventDefault();
                openEnquiryModal(enquiry.dataset.course || '');
                window.closeMobileNav && window.closeMobileNav();
                return;
            }
            const toast = e.target.closest('[data-toast]');
            if (toast) { e.preventDefault(); showToast(toast.dataset.toast); return; }
            if (e.target.classList && e.target.classList.contains('modal-overlay') && e.target.id === 'enquiry-modal') {
                closeEnquiryModal();
            }
        });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEnquiryModal(); });
    }

    /* --------------------------------------------------------
       Settings hydration + footer programs
       -------------------------------------------------------- */
    function hydrateSettings(settings) {
        if (!settings) return;
        document.querySelectorAll('[data-setting]').forEach(el => {
            const key = el.dataset.setting;
            if (settings[key]) el.textContent = settings[key];
        });
        document.querySelectorAll('[data-setting-text]').forEach(el => {
            const key = el.dataset.settingText;
            if (settings[key]) el.textContent = settings[key];
        });
        document.querySelectorAll('[data-setting-href]').forEach(el => {
            const spec = el.dataset.settingHref;
            const key = spec.split(/[:/]/).pop();
            const value = settings[key];
            if (!value) return;
            if (spec.startsWith('tel:')) el.href = 'tel:' + String(value).replace(/[^+\d]/g, '');
            else el.href = spec.replace(key, value);
        });
        document.querySelectorAll('[data-social]').forEach(el => {
            const url = settings[el.dataset.social];
            if (url) { el.href = url; el.style.display = ''; } else { el.style.display = 'none'; }
        });
        const announcementText = document.getElementById('announcement-text');
        if (announcementText && settings.enrollment_message) announcementText.textContent = settings.enrollment_message;
        const announcementBar = document.getElementById('announcement-bar');
        if (announcementBar && String(settings.enrollment_open) === '0') announcementBar.style.display = 'none';
    }

    function hydrateFooterPrograms(courses) {
        const list = document.getElementById('footer-programs');
        if (!list || !courses.length) return;
        list.innerHTML = courses.slice(0, 6).map(c =>
            '<li><a href="#/courses/' + esc(c.slug) + '" data-link><span class="material-symbols-outlined link-icon">' + categoryMeta(c.category_slug).icon + '</span>' + esc(c.title) + '</a></li>'
        ).join('');
    }

    function hydrateCourseSelects(courses) {
        document.querySelectorAll('[data-course-select], #enquiry-course').forEach(select => {
            const existing = Array.from(select.options).map(o => o.value);
            courses.forEach(c => {
                if (existing.includes(c.slug)) return;
                const opt = document.createElement('option');
                opt.value = c.slug;
                opt.textContent = c.title;
                const other = select.querySelector('option[value="other"]');
                if (other) select.insertBefore(opt, other); else select.appendChild(opt);
            });
        });
    }

    function initNewsletter() {
        const form = document.getElementById('newsletter-form');
        if (!form) return;
        form.addEventListener('submit', e => {
            e.preventDefault();
            form.reset();
            showToast('Thanks! We will keep you updated.');
        });
    }

    /* --------------------------------------------------------
       Boot
       -------------------------------------------------------- */
    function init() {
        initMobileNav();
        initCompactNav();
        initGlobalHandlers();
        initNewsletter();
        wireForm('enquiry-form', { successMessage: 'Enquiry submitted. We will contact you soon.' });

        window.addEventListener('hashchange', () => Router.resolve());

        // Hydrate shell from API without blocking first paint.
        Promise.all([Store.settings(), Store.courses()]).then(([settings, courses]) => {
            hydrateSettings(settings);
            hydrateFooterPrograms(courses);
            hydrateCourseSelects(courses);
        });

        Router.resolve();
    }

    window.openEnquiryModal = openEnquiryModal;
    window.closeEnquiryModal = closeEnquiryModal;
    window.showToast = showToast;

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();

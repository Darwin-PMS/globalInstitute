/**
 * Standalone loading bundle.
 *
 * For shells that do not load resources/js/app.js — the admin SPA serves its
 * engine as a plain script from public/ and reaches these utilities through
 * window.Loading, which importing this publishes.
 */
import './loading.js';
import './blade-loading.js';

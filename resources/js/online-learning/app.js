/**
 * Online Learning SPA Application Entry Point
 * Global Institute & CAD CAM
 */

import { Router } from './router.js';
import './components/Toast.js';

document.addEventListener('DOMContentLoaded', () => {
    // Instantiate Client Router
    window.spaRouter = new Router();
});

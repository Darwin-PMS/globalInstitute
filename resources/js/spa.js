/**
 * SPA functionality for admin panel
 */

// Handle navigation links
document.addEventListener('click', function (e) {
    // Check if clicked element is a link or inside a link
    const link = e.target.closest('a');
    
    if (!link) {
        return;
    }

    // Let the browser handle anything that is not a plain in-app navigation.
    if (link.hasAttribute('download') ||
        link.target === '_blank' ||
        link.host !== window.location.host ||
        link.hasAttribute('data-method') ||
        link.getAttribute('data-ajax') === 'false') {
        return;
    }

    // Modified clicks are how people open things in a new tab or window.
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) {
        return;
    }

    const url = link.getAttribute('href');

    // No href, an in-page anchor, or a non-http scheme: nothing to fetch.
    if (!url || url === '#' || url.startsWith('#') ||
        url.startsWith('mailto:') || url.startsWith('tel:') ||
        url.startsWith('javascript:')) {
        return;
    }

    // Partial navigation only understands the admin panel, which is the only
    // place that exposes the regions loadPage() swaps.
    if (!document.querySelector('.admin-content')) {
        return;
    }

    e.preventDefault();

    window.history.pushState({ url }, '', url);
    loadPage(url);
});

// Handle form submissions via AJAX
document.addEventListener('submit', function (e) {
    const form = e.target.closest('form');
    
    if (!form) {
        return;
    }

    // Skip if form has data-ajax="false"
    if (form.getAttribute('data-ajax') === 'false') {
        return;
    }

    // Prevent default submission
    e.preventDefault();

    // Get form details
    const action = form.getAttribute('action') || window.location.pathname;
    const method = form.getAttribute('method') || 'GET';
    const formData = new FormData(form);

    // Submit via AJAX
    axios({
        url: action,
        method: method,
        data: formData,
        headers: {
            'Content-Type': 'multipart/form-data',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        // If the response is a redirect (status 3xx), follow it
        if (response.status >= 300 && response.status < 400) {
            const redirectUrl = response.headers.location;
            window.history.pushState({ url: redirectUrl }, '', redirectUrl);
            return loadPage(redirectUrl);
        }

        // If response data contains a redirect property (for JSON responses)
        if (response.data && response.data.redirect) {
            window.history.pushState({ url: response.data.redirect }, '', response.data.redirect);
            return loadPage(response.data.redirect);
        }

        // Otherwise, reload the current page
        loadPage(window.location.pathname + window.location.search);
    })
    .catch(error => {
        console.error('Form submission error:', error);
        // Show error message (you can implement a notification system)
        alert('An error occurred while submitting the form.');
    });
});

// Handle popstate (back/forward buttons)
window.addEventListener('popstate', function (e) {
    if (e.state && e.state.url) {
        loadPage(e.state.url);
    }
});

/**
 * Load a page via AJAX and update the content
 * @param {string} url - The URL to load
 */
function loadPage(url) {
    // Show loading indicator (optional)
    // You can add a loading spinner here

    // Fetch the page
    axios.get(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        }
    })
    .then(response => {
        // Handle redirect
        if (response.status >= 300 && response.status < 400) {
            const redirectUrl = response.headers.location;
            window.history.pushState({ url: redirectUrl }, '', redirectUrl);
            return loadPage(redirectUrl);
        }

        // Parse the HTML response
        const parser = new DOMParser();
        const doc = parser.parseFromString(response.data, 'text/html');

        // Extract title
        const titleElement = doc.querySelector('title');
        if (titleElement) {
            document.title = titleElement.textContent;
        }

        // Header page title. It sits outside .admin-content, so it has to be
        // swapped explicitly — otherwise the header keeps the previous page's
        // name until a full reload.
        const nextTitle = doc.querySelector('[data-page-title]');
        const currentTitle = document.querySelector('[data-page-title]');
        if (nextTitle && currentTitle) {
            currentTitle.innerHTML = nextTitle.innerHTML;
        }

        // Header action buttons (Add FAQ, Add Image, …), same reasoning.
        const actionsElement = doc.querySelector('[data-actions]');
        const currentActions = document.querySelector('[data-actions]');
        if (currentActions) {
            // Clear it even when the next page has none, so a stale button from
            // the previous screen cannot linger.
            currentActions.innerHTML = actionsElement ? actionsElement.innerHTML : '';
        }

        // Update main content
        const contentElement = doc.querySelector('.admin-content');
        const currentContent = document.querySelector('.admin-content');
        if (contentElement && currentContent) {
            currentContent.innerHTML = contentElement.innerHTML;
        }

        // A new screen should start at the top, as a real navigation would.
        const main = document.querySelector('.admin-main');
        if (main) main.scrollTop = 0;
        window.scrollTo({ top: 0 });

        // Update active state in sidebar
        updateActiveSidebar(url);

        // Re-initialize any JS components if needed
        // For example, if you have dropdowns, modals, etc., you may need to re-initialize them
        // We'll trigger a custom event that other scripts can listen for
        document.dispatchEvent(new CustomEvent('spa:load'));
    })
    .catch(error => {
        console.error('Page load error:', error);
        // Fallback to full page load
        window.location.href = url;
    });
}

/**
 * Update the active state in the sidebar based on the URL
 * @param {string} url - The current URL
 */
function updateActiveSidebar(url) {
    // Remove active class from all nav links
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        link.classList.remove('active');
    });

    // Find the link that matches the URL (or is a parent of the URL)
    const links = document.querySelectorAll('.sidebar-nav .nav-link');
    links.forEach(link => {
        const href = link.getAttribute('href');
        if (href && url.startsWith(href)) {
            link.classList.add('active');
        }
    });
}
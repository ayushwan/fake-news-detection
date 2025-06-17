document.addEventListener('DOMContentLoaded', function () {
    const themeToggle = document.getElementById('theme-toggle');
    const currentTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : null;
    const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');

    function applyTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
            if (themeToggle) themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-bs-theme', 'light');
            if (themeToggle) themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            localStorage.setItem('theme', 'light');
        }
        // Dispatch a custom event when theme changes for Chart.js or other libraries to listen
        document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: theme } }));
    }

    if (currentTheme) {
        applyTheme(currentTheme);
    } else {
        if (prefersDarkScheme.matches) {
            applyTheme('dark');
        } else {
            applyTheme('light');
        }
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            let theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                applyTheme('light');
            } else {
                applyTheme('dark');
            }
        });
    }

    // Handle prefers-color-scheme changes
    prefersDarkScheme.addEventListener('change', (e) => {
        // Only change if no theme is manually set by the user
        if (!localStorage.getItem('theme')) {
            applyTheme(e.matches ? 'dark' : 'light');
        }
    });

    // Handle form submissions that might take time (e.g., news submission)
    const formsWithSpinner = document.querySelectorAll('form.needs-spinner');
    formsWithSpinner.forEach(form => {
        form.addEventListener('submit', function() {
            const spinner = document.getElementById('loading-spinner');
            if (spinner) {
                spinner.style.display = 'block';
            }
            // Optionally disable submit button to prevent multiple submissions
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = 
                    `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...`;
            }
        });
    });

    // Reset spinner and button if page is reloaded (e.g. due to validation error server-side)
    // This is a basic way, more robust solutions might be needed depending on app flow
    window.addEventListener('pageshow', function(event) {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.style.display = 'none';
        }
        const forms = document.querySelectorAll('form.needs-spinner');
        forms.forEach(form => {
            const submitButton = form.querySelector('button[type="submit"][disabled]');
            if (submitButton) {
                submitButton.disabled = false;
                // Restore original button text (you might need to store it or have a default)
                // For simplicity, assuming a generic text or icon
                const originalButtonText = submitButton.dataset.originalText || 'Submit'; // Store original in data-original-text
                submitButton.innerHTML = originalButtonText;
            }
        });
    });

});

// Function to show SweetAlert2 notifications
function showNotification(type, title, text) {
    Swal.fire({
        icon: type, // 'success', 'error', 'warning', 'info', 'question'
        title: title,
        text: text,
        timer: type === 'success' ? 2000 : 3000, // Shorter for success
        timerProgressBar: true,
        showConfirmButton: false // No confirm button for simple notifications
    });
}

// Example: If you have flash messages rendered from Flask and want to show them via SweetAlert
// You would need to embed the messages in a script tag in your HTML template
// and then call this function.
// e.g., in base.html, after including this script.js:
// {% with messages = get_flashed_messages(with_categories=true) %}
//   {% if messages %}
//     <script>
//       document.addEventListener('DOMContentLoaded', function() {
//         {% for category, message in messages %}
//           showNotification('{{ category }}', '{{ category.title() }}', '{{ message }}');
//         {% endfor %}
//       });
//     </script>
//   {% endif %}
// {% endwith %}
// Note: The above example for flash messages is better handled by the _flash_messages.html partial directly.
// This showNotification function is more for dynamic JS-triggered notifications.
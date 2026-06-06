// assets/js/theme.js
document.addEventListener('DOMContentLoaded', () => {
    const htmlElement = document.documentElement;

    // Load saved theme from localStorage or default to dark
    const savedTheme = localStorage.getItem('chatus_theme') || (htmlElement.getAttribute('data-bs-theme') || 'dark');
    setTheme(savedTheme);

    // Use event delegation for theme toggles to support multiple instances (labeled or icon-only)
    document.addEventListener('click', (e) => {
        const toggleBtn = e.target.closest('#theme-toggle');
        if (toggleBtn) {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
            updateThemeInDatabase(newTheme);
        }
    });

    function setTheme(theme) {
        htmlElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('chatus_theme', theme);

        // Update all theme toggle buttons (icons and labels)
        document.querySelectorAll('#theme-toggle').forEach(btn => {
            const icon = btn.querySelector('i');
            const span = btn.querySelector('span');

            if (theme === 'dark') {
                if (icon) {
                    icon.className = 'bi bi-sun-fill';
                    // Main chat header toggle uses text-warning for sun
                    if (btn.classList.contains('btn-icon')) {
                        btn.classList.remove('text-dark');
                        btn.classList.add('text-warning');
                    }
                }
                if (span) span.textContent = 'Light Mode';
            } else {
                if (icon) {
                    icon.className = 'bi bi-moon-stars-fill';
                    // Main chat header toggle uses text-dark for moon
                    if (btn.classList.contains('btn-icon')) {
                        btn.classList.remove('text-warning');
                        btn.classList.add('text-dark');
                    }
                }
                if (span) span.textContent = 'Dark Mode';
            }
        });
    }

    function updateThemeInDatabase(theme) {
        // Only update if we're in a scope that supports it
        fetch('controllers/ProfileController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_theme&theme=${theme}`
        }).catch(err => console.debug('Not on a page with ProfileController or error updating theme:', err));
    }
});

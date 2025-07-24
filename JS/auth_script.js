// ===============================================
// StudyCore - Authentication Page Scripts
// ===============================================
document.addEventListener('DOMContentLoaded', () => {

    // 1. Dark Mode Toggle Functionality
    const toggleButton = document.getElementById('darkModeToggle');

    if (toggleButton) {
        // Function to apply the selected theme (light or dark)
        function applyTheme(theme) {
            const body = document.body;
            if (theme === 'dark') {
                body.classList.add('dark-mode');
                toggleButton.textContent = '🌙';
            } else {
                body.classList.remove('dark-mode');
                toggleButton.textContent = '☀️';
            }
        }

        // Check for a saved theme preference in localStorage
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            applyTheme(savedTheme);
        } else {
            // If no preference, check the user's system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            applyTheme(prefersDark ? 'dark' : 'light');
        }

        // Add event listener to the dark mode toggle button
        toggleButton.addEventListener('click', () => {
            const newTheme = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
            localStorage.setItem('theme', newTheme);
            applyTheme(newTheme);
        });
    }

    // 2. Client-side form validation can be added here later if needed
    // Example for registration page is kept inside register.php for simplicity
});
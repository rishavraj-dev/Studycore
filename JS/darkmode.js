// Function to apply the selected theme (light or dark)
function applyTheme(theme) {
    const body = document.body;
    const toggleButton = document.getElementById('darkModeToggle');

    if (theme === 'dark') {
        body.classList.add('dark-mode');
        toggleButton.textContent = '🌙'; // Moon icon for dark mode
    } else {
        body.classList.remove('dark-mode');
        toggleButton.textContent = '☀️'; // Sun icon for light mode
    }
}

// Event listener for when the DOM content is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    const toggleButton = document.getElementById('darkModeToggle');

    // 1. Check for a saved theme preference in localStorage
    const savedTheme = localStorage.getItem('theme');

    if (savedTheme) {
        // If a preference is found, apply it
        applyTheme(savedTheme);
    } else {
        // If no preference is found, check the user's system preference
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(prefersDark ? 'dark' : 'light');
    }

    // 2. Add event listener to the toggle button
    toggleButton.addEventListener('click', () => {
        // Determine the new theme based on the current body class
        const newTheme = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
        
        // Save the new theme preference to localStorage
        localStorage.setItem('theme', newTheme);
        
        // Apply the new theme to the page
        applyTheme(newTheme);
    });
});

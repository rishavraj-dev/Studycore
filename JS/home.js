// This file contains JavaScript functionality specific to the student homepage.

document.addEventListener('DOMContentLoaded', () => {

    // ===============================================
    //  DARK MODE TOGGLE LOGIC
    // ===============================================
    const toggleButton = document.getElementById('darkModeToggle');

    // Check if the toggle button actually exists on the page
    if (toggleButton) {
        
        // A function to apply the chosen theme by adding/removing a class on the body
        const applyTheme = (theme) => {
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
                toggleButton.textContent = '🌙'; // Set icon to moon
            } else {
                document.body.classList.remove('dark-mode');
                toggleButton.textContent = '☀️'; // Set icon to sun
            }
        };

        // Check if a theme preference is already saved in the browser's local storage
        const savedTheme = localStorage.getItem('theme');

        if (savedTheme) {
            // If a saved theme is found, apply it
            applyTheme(savedTheme);
        } else {
            // If no theme is saved, check the user's operating system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            applyTheme(prefersDark ? 'dark' : 'light');
        }

        // Add a click event listener to the toggle button
        toggleButton.addEventListener('click', () => {
            // Check the current theme to determine the new theme
            const newTheme = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
            // Save the user's new preference to local storage
            localStorage.setItem('theme', newTheme);
            // Apply the new theme to the page
            applyTheme(newTheme);
        });
    }

    // You can add other homepage-specific JavaScript here in the future,
    // for example, code to handle animations or interactive elements on the feature cards.

});
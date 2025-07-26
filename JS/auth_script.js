// Wait for the entire HTML document to be loaded before running the script
document.addEventListener('DOMContentLoaded', () => {

    // ===============================================
    //  1. DARK MODE TOGGLE FUNCTIONALITY
    // ===============================================
    const darkModeToggle = document.getElementById('darkModeToggle');

    // Check if the toggle button actually exists on the page
    if (darkModeToggle) {
        
        // A function to apply the chosen theme by adding/removing a class on the body
        const applyTheme = (theme) => {
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
                darkModeToggle.textContent = '🌙'; // Set icon to moon
            } else {
                document.body.classList.remove('dark-mode');
                darkModeToggle.textContent = '☀️'; // Set icon to sun
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
        darkModeToggle.addEventListener('click', () => {
            // Check the current theme to determine the new theme
            const newTheme = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
            // Save the user's new preference to local storage
            localStorage.setItem('theme', newTheme);
            // Apply the new theme to the page
            applyTheme(newTheme);
        });
    }


    // ===============================================
    //  2. FORGOT PASSWORD MODAL FUNCTIONALITY
    // ===============================================
    const modal = document.getElementById('forgotPasswordModal');
    const trigger = document.getElementById('forgotPasswordTrigger');
    const closeBtn = document.getElementById('modalCloseBtn');

    // Function to open the modal by adding the 'active' class
    const openModal = () => {
        if (modal) {
            modal.classList.add('active');
        }
    };

    // Function to close the modal by removing the 'active' class
    const closeModal = () => {
        if (modal) {
            modal.classList.remove('active');
        }
    };

    // Add click event listener to the "Forgot Password?" link
    if (trigger) {
        trigger.addEventListener('click', (event) => {
            event.preventDefault(); // Prevent the link from trying to navigate away
            openModal();
        });
    }

    // Add click event listener to the modal's close (X) button
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    // Add click event listener to the dark overlay area of the modal
    if (modal) {
        modal.addEventListener('click', (event) => {
            // If the user clicks on the overlay itself (not the content box), close the modal
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    // Optional: Also close the modal if the user presses the 'Escape' key
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && modal.classList.contains('active')) {
            closeModal();
        }
    });

});
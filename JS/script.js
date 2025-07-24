// Wait for the DOM to be fully loaded before running scripts
document.addEventListener('DOMContentLoaded', () => {

    // ========================================
    // 1. Dark Mode Toggle Functionality
    // ========================================
    const darkModeToggle = document.getElementById('darkModeToggle');

    function applyTheme(theme) {
        const body = document.body;
        if (theme === 'dark') {
            body.classList.add('dark-mode');
            darkModeToggle.textContent = '🌙';
        } else {
            body.classList.remove('dark-mode');
            darkModeToggle.textContent = '☀️';
        }
    }

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        applyTheme(savedTheme);
    } else {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(prefersDark ? 'dark' : 'light');
    }

    darkModeToggle.addEventListener('click', () => {
        const newTheme = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
        localStorage.setItem('theme', newTheme);
        applyTheme(newTheme);
    });


    // ========================================
    // 2. Mobile Navigation (Hamburger Menu)
    // ========================================
    const menuToggle = document.getElementById('menuToggle');
    const navbarLinks = document.getElementById('navbarLinks');
    
    // **NEW**: Select all links within the navigation menu
    const navLinks = document.querySelectorAll('#navbarLinks a');

    // **NEW**: A reusable function to close the mobile menu
    function closeMenu() {
        navbarLinks.classList.remove('nav-active');
        menuToggle.classList.remove('active');
    }

    // Toggle menu when the hamburger icon is clicked
    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            const isMenuOpen = navbarLinks.classList.contains('nav-active');
            if (isMenuOpen) {
                closeMenu();
            } else {
                navbarLinks.classList.add('nav-active');
                menuToggle.classList.add('active');
            }
        });
    }
    
    // **NEW**: Add click event listeners to each navigation link
    // When a link is clicked, the menu will close.
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            closeMenu();
        });
    });
});
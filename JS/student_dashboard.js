document.addEventListener('DOMContentLoaded', () => {
    const profileButton = document.getElementById('profileButton');
    const dropdownMenu = document.getElementById('profileDropdown');

    if (profileButton && dropdownMenu) {
        // Toggle dropdown when the profile button is clicked
        profileButton.addEventListener('click', (event) => {
            // Stop the click from immediately being caught by the 'window' listener
            event.stopPropagation();
            dropdownMenu.classList.toggle('active');
        });

        // Close the dropdown if the user clicks anywhere else on the page
        window.addEventListener('click', (event) => {
            // Check if the dropdown is active and the click was outside of it
            if (dropdownMenu.classList.contains('active') && !profileButton.contains(event.target)) {
                dropdownMenu.classList.remove('active');
            }
        });
    }
});
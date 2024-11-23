document.addEventListener('DOMContentLoaded', () => {
    const borrowerIcon = document.getElementById('borrower-icon');
    const dropdownMenu = document.getElementById('dropdown-menu');

    borrowerIcon.addEventListener('click', (event) => {
        event.preventDefault();
        dropdownMenu.classList.toggle('show');
    });

    // Close the dropdown menu if clicking outside
    document.addEventListener('click', (event) => {
        if (!borrowerIcon.contains(event.target) && !dropdownMenu.contains(event.target)) {
            dropdownMenu.classList.remove('show');
        }
    });
});

// js/admin-script.js

document.addEventListener('DOMContentLoaded', function () {
    console.log('JavaScript loaded'); // Add this line to verify the script is running
    const container = document.getElementById('email-addresses-container');
    const addButton = document.getElementById('add-email');

    addButton.addEventListener('click', function () {
        const newField = document.createElement('div');
        newField.className = 'email-address-field';
        newField.innerHTML = `
            <input type="text" name="fcn_email_addresses[]" class="email-input" />
            <button type="button" class="remove-email">Remove</button>
        `;
        container.appendChild(newField);
        console.log('Added new email input'); // Verify new elements are added
    });

    container.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-email')) {
            event.target.parentElement.remove();
            console.log('Removed email input'); // Verify elements are removed
        }
    });
});

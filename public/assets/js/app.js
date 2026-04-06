document.addEventListener('DOMContentLoaded', () => {
    const firstField = document.querySelector('input[name="username"]');

    if (firstField instanceof HTMLInputElement) {
        firstField.focus();
    }
});

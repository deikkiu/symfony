document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('form-submit').addEventListener('click', handleFormSubmit);
});

function handleFormSubmit(event) {
    event.preventDefault();

    const form = document.querySelector('form');
    const params = {};

    [...form.elements].forEach(input => {
        if (input.name === 'isAmount') {
            input.value = input.checked ? 1 : 0;
        }

        if (input.value.trim() === '' || input.value < 1) {
            return;
        }

        params[input.name] = input.value;
    });

    const uri = window.location.origin.concat(window.location.pathname);
    const query = new URLSearchParams(params).toString().trim();

    const url = query ? uri.concat('?', query) : uri;

    window.location.replace(url);
}
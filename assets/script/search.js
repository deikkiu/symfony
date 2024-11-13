document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('form-submit').addEventListener('click', handleFormSubmit);
});

function handleFormSubmit(e) {
    e.preventDefault();

    const form = document.querySelector('form');
    const params = {};

    [...form.elements].forEach(input => {
        if (input.name === 'isAmount') {
            input.value = input.checked ? 1 : 0;
        }

        if (input.value.trim() === '') {
            return;
        }

        if (input.value < 1) {
            return;
        }

        return params[input.name] = input.value;
    });

    const uri = window.location.origin + window.location.pathname;
    const query = new URLSearchParams(params).toString();

    const url = query.trim() !== '' ? uri.concat('?', query) : uri;

    window.location.replace(url);
}

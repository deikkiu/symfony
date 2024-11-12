document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.pagination a.page-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            document.querySelector('input[name="product_search[page]"]').value = this.getAttribute('data-page');
            document.querySelector('form[name="product_search"]').submit();
        });
    });
});
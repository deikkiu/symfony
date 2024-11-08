document.addEventListener('DOMContentLoaded', function () {
        const addToCartBtn = document.getElementById('addProductToCart');

        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function () {
                const productId = addToCartBtn.getAttribute('data-product');

                const url = `/cart/add/${productId}`;
                const body = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                };

                fetchCartRequest(url, body)
            });
        }

        async function fetchCartRequest(url, body) {
            try {
                const response = await fetch(url, body);
                const data = await response.json();

                renderCounter(addToCartBtn, 1, addToCartBtn.getAttribute('data-amount'));
                addFlash('success', data.message);
            } catch (e) {
                console.error('Error:', e)
                addFlash('error', e.message);
            }
        }

        function renderCounter(element, initialValue, maxAmount) {
            const productId = element.getAttribute('data-product');
            element.outerHTML = `
                <div class="d-flex justify-content-center align-items-center">
                    <button class="btn btn-outline-secondary" type="button" id="decreaseBtn" data-product="${productId}">-</button>
                    <input type="number" class="form-control text-center mx-2" id="counterInput" value="${initialValue}" readonly>
                    <button class="btn btn-outline-secondary" type="button" id="increaseBtn" data-product="${productId}" data-amount="${maxAmount}">+</button>
                </div>`;

            setupCounterButtons();
        }

        function addFlash(status, message) {
            const main = document.getElementsByTagName('main')[0];

            const flash = `
                <div class="flash flash-${status}">
                    ${message}
                </div>`;

            main.insertAdjacentHTML('afterbegin', flash);
        }

        function setupCounterButtons() {
            const decreaseBtn = document.getElementById('decreaseBtn');
            const increaseBtn = document.getElementById('increaseBtn');
            const counterInput = document.getElementById('counterInput');

            if (decreaseBtn && increaseBtn) {
                decreaseBtn.addEventListener('click', function () {
                    let currentValue = parseInt(counterInput.value, 10);

                    if (currentValue > 1) {
                        updateCartProductQuantity(decreaseBtn.getAttribute('data-product'), 'decrease');
                        counterInput.value = currentValue - 1;
                        increaseBtn.disabled = false;
                    }

                    if (currentValue - 1 < 2) {
                        decreaseBtn.disabled = true;
                    }
                });

                increaseBtn.addEventListener('click', function () {
                    let currentValue = parseInt(counterInput.value, 10);
                    const maxAmount = parseInt(increaseBtn.getAttribute('data-amount'), 10);

                    if (currentValue < maxAmount) {
                        updateCartProductQuantity(increaseBtn.getAttribute('data-product'), 'increase');
                        counterInput.value = currentValue + 1;
                        decreaseBtn.disabled = false;
                    }

                    if (currentValue + 1 >= maxAmount) {
                        increaseBtn.disabled = true;
                    }
                });
            }
        }

        async function updateCartProductQuantity(productId, action) {
            const url = action === 'increase' ? `/cart/add/${productId}` : `/cart/delete/${productId}`;
            const body = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            };

            try {
                await fetch(url, body);
            } catch (e) {
                console.error('Error:', e)
            }
        }

        setupCounterButtons();
    }
)
;

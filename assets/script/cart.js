document.addEventListener('DOMContentLoaded', function () {
    function updateQuantity(productId, action, currentValue) {
        const quantityInput = document.getElementById(`product-quantity-${productId}`);

        if (action === 'delete' && currentValue === 1) {
            window.location.href = `/cart/remove/${productId}`;
            return;
        }

        const url = `/cart/${action}/${productId}`;
        const body = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        };

        fetchCartRequest(url, body, quantityInput, action, currentValue);
    }

    async function fetchCartRequest(url, body, quantityInput, action, currentValue) {
        try {
            const response = await fetch(url, body);
            const data = await response.json();

            quantityInput.value = action === 'add' ? currentValue + 1 : currentValue - 1;
            await updateCartDataInPage(data.totalPrice, data.quantity);
        } catch (error) {
            console.error('Error:', error);
        }
    }

    document.querySelectorAll('.increase-btn').forEach(button => {
        button.addEventListener('click', function () {
            const productId = button.getAttribute('data-product-id');
            const maxAmount = parseInt(button.getAttribute('data-amount'), 10);
            const quantityInput = document.getElementById(`product-quantity-${productId}`);
            const currentValue = parseInt(quantityInput.value, 10);

            if (currentValue < maxAmount) {
                updateQuantity(productId, 'add', currentValue);
            }

            addButtonStateIncrease(productId, parseInt(quantityInput.value, 10), maxAmount);
        });
    });

    document.querySelectorAll('.decrease-btn').forEach(button => {
        button.addEventListener('click', function () {
            const productId = button.getAttribute('data-product-id');
            const maxAmount = parseInt(button.getAttribute('data-amount'), 10);
            const quantityInput = document.getElementById(`product-quantity-${productId}`);
            const currentValue = parseInt(quantityInput.value, 10);

            if (currentValue > 0) {
                updateQuantity(productId, 'delete', currentValue);
            }

            addButtonStateDecrease(productId, parseInt(quantityInput.value, 10), maxAmount);
        });
    });

    function addButtonStateIncrease(productId, currentQuantity, maxAmount) {
        const addBtn = document.querySelector('.increase-btn');

        addBtn.disabled = currentQuantity + 1 >= maxAmount;
    }

    function addButtonStateDecrease(productId, currentQuantity, maxAmount) {
        const addBtn = document.querySelector('.increase-btn');

        addBtn.disabled = currentQuantity - 1 > maxAmount;
    }

    function updateCartDataInPage(totalPrice, quantity) {
        const totalPriceSpan = document.querySelector('[data-totalPrice] > span');
        const quantitySpan = document.querySelector('[data-quantity] > span');

        const formatPrice = new Intl.NumberFormat('en-US', {
            style: 'decimal',
        }).format(totalPrice / 100);

        totalPriceSpan.textContent = `${formatPrice}$`;
        quantitySpan.textContent = quantity;
    }
})
;

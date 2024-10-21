document.addEventListener('DOMContentLoaded', function () {
    function updateQuantity(productId, action, currentValue) {
        const quantityInput = document.getElementById(`product-quantity-${productId}`);

        if (action === 'delete' && currentValue === 1) {
            window.location.href = `/cart/remove/${productId}`;
            return;
        }

        let url = `/cart/${action}/${productId}`;

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (action === 'add') {
                        quantityInput.value = currentValue + 1;
                    } else if (action === 'delete' && currentValue > 1) {
                        quantityInput.value = currentValue - 1;
                    }
                } else {
                    console.error('Ошибка при обновлении количества:', data.message);
                }
            })
            .catch(error => console.error('Error:', error));
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
});

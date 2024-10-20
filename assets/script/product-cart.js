document.addEventListener('DOMContentLoaded', function () {
	const addToCartBtn = document.getElementById('addProductToCart');

	if (addToCartBtn) {
		addToCartBtn.addEventListener('click', function () {
			const productId = addToCartBtn.getAttribute('data-product');

			fetch(`/cart/add/${productId}`, {  // Передаем ID как часть URL
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				}
			})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						renderCounter(addToCartBtn, 1, addToCartBtn.getAttribute('data-amount'));
					}
				})
				.catch(error => console.error('Error:', error));
		});
	}

	function renderCounter(element, initialValue, maxAmount) {
		const productId = element.getAttribute('data-product');
		const counterHtml = `
            <div class="d-flex justify-content-center align-items-center">
                <button class="btn btn-outline-secondary" type="button" id="decreaseBtn" data-product="${productId}">-</button>
                <input type="number" class="form-control text-center mx-2" id="counterInput" value="${initialValue}" readonly>
                <button class="btn btn-outline-secondary" type="button" id="increaseBtn" data-product="${productId}" data-amount="${maxAmount}">+</button>
            </div>
        `;
		element.outerHTML = counterHtml;

		setupCounterButtons();
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
				} else {
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

	function updateCartProductQuantity(productId, action) {
		const url = action === 'increase' ? `/cart/add/${productId}` : `/cart/delete/${productId}`;
		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			}
		})
			.then(response => response.json())
			.then(data => {
				if (!data.success) {
					console.error('Ошибка обновления корзины:', data.message);
				}
			})
			.catch(error => console.error('Error:', error));
	}

	setupCounterButtons();
});

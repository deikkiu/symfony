document.addEventListener('DOMContentLoaded', function () {

	function updateQuantity(productId, action, currentValue) {
		const quantityInput = document.getElementById(`product-quantity-${productId}`);

		let url = action === 'delete' && currentValue === 1
			? `/cart/remove/${productId}`
			: `/cart/${action}/${productId}`;

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
					} else if (action === 'delete' && currentValue === 1) {
						window.location.href = data.redirectUrl;
					}

					if (action !== 'delete' || currentValue > 1) {
						toggleButtonsState(productId, parseInt(quantityInput.value, 10), data.maxAmount);
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
		});
	});

	document.querySelectorAll('.decrease-btn').forEach(button => {
		button.addEventListener('click', function () {
			const productId = button.getAttribute('data-product-id');
			const quantityInput = document.getElementById(`product-quantity-${productId}`);
			const currentValue = parseInt(quantityInput.value, 10);

			if (currentValue > 0) {
				updateQuantity(productId, 'delete', currentValue);
			}
		});
	});

	function toggleButtonsState(productId, currentQuantity, maxAmount) {
		const deleteBtn = document.querySelector(`.decrease-btn[data-product-id="${productId}"]`);
		const addBtn = document.querySelector(`.increase-btn[data-product-id="${productId}"]`);

		if (currentQuantity <= 1) {
			deleteBtn.disabled = false;
		} else {
			addBtn.disabled = false;
		}

		addBtn.disabled = currentQuantity >= maxAmount;
	}
});

document
    .querySelectorAll('.add_item_link')
    .forEach(btn => {
        btn.addEventListener("click", addFormToCollection)
    });

document
    .querySelectorAll('ul.colors li')
    .forEach((color) => {
        addColorFormDeleteLink(color)
    })

document
    .querySelectorAll('ul.colors li')
    .forEach((color) => {
        addColorFormDeleteLink(color)
    })

function addFormToCollection(e) {
    const collectionHolder = document.querySelector('.' + e.currentTarget.dataset.collectionHolderClass);

    const item = document.createElement('li');
    item.innerHTML = '<button type="button" class="delete_item_link" data-collection-holder-class="colors">X</button>';

    item.innerHTML = collectionHolder
        .dataset
        .prototype
        .replace(
            /__name__/g,
            collectionHolder.dataset.index
        );

    addColorFormDeleteLink(item);

    collectionHolder.appendChild(item);

    collectionHolder.dataset.index++;
}

function addColorFormDeleteLink(item) {
    const removeFormButton = document.createElement('button');
    removeFormButton.innerText = 'X';

    item.append(removeFormButton);

    removeFormButton.addEventListener('click', (e) => {
        e.preventDefault();
        item.remove();
    });
}

document.querySelector('.quantity_minus').addEventListener('click', minusQuantity);
document.querySelector('.quantity_plus').addEventListener('click', plusQuantity);

function minusQuantity() {
    const quantityDiv = document.querySelector('.quantity_count');
    let quantityCount = Number(quantityDiv.value);

    if (quantityCount < 2) {
        return;
    }

    quantityDiv.value = String(quantityCount - 1);
}

function plusQuantity() {
    const amount = Number(document.querySelector('.amount').getAttribute('data-amount'));
    const quantityDiv = document.querySelector('.quantity_count');
    let quantityCount = Number(quantityDiv.value);

    if(quantityCount >= amount) {
        return;
    }

    quantityDiv.value = String(quantityCount + 1);
}
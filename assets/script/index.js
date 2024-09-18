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

    let nameAttr =  item.children.item(0).getAttribute('name') + '[name]';
    let idAttr =  item.children.item(0).getAttribute('id') + '_name';

    item.children.item(0).setAttribute('name', nameAttr)
    item.children.item(0).setAttribute('id', idAttr)
    item.children.item(0).setAttribute('type','text')
    item.children.item(0).setAttribute('required','required')
    item.children.item(0).setAttribute('maxlength','255')

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
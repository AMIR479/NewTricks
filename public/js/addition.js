
    const addTagFormDeleteLink = (item) => {
        const removeFormButton = document.createElement('button');
        removeFormButton.innerText = 'Delete this tag';
    
        item.append(removeFormButton);
    
        removeFormButton.addEventListener('click', (e) => {
            e.preventDefault();
            // remove the li for the tag form
            item.remove();
        });
    }        
    
        const addFormToCollection = (e) => {
            const collectionHolder = document.querySelector('.' + e.currentTarget.dataset.collectionHolderClass);
            const item = document.createElement('li');
    
            item.innerHTML = collectionHolder
                .dataset
                .prototype
                .replace(
                /__name__/g,
                collectionHolder.dataset.index
                );
    
      collectionHolder.appendChild(item);
    
      collectionHolder.dataset.index++;
    
      addTagFormDeleteLink(item);
    };
        
        document.querySelectorAll('.add_item_link')
        .forEach(btn => {
            btn.addEventListener("click", addFormToCollection)
        });
        document
        .querySelectorAll('ul.videos li')
        .forEach((video) => {
            addVideoFormDeleteLink(video);
        })
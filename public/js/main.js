document.addEventListener('DOMContentLoaded', function() {
    // Update Product Modal Handler
    const updateProductModal = document.getElementById('updateProductModal');
    if (updateProductModal) {
        updateProductModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const productId = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const description = button.getAttribute('data-description');
            const price = button.getAttribute('data-price');
            const stock = button.getAttribute('data-stock');
            const category = button.getAttribute('data-category');
            const image = button.getAttribute('data-image');

            document.getElementById('update-product-id').value = productId;
            document.getElementById('update-name').value = name;
            document.getElementById('update-description').value = description;
            document.getElementById('update-price').value = price;
            document.getElementById('update-stock').value = stock;
            document.getElementById('update-category').value = category;
            document.getElementById('current-image').value = image.split('/').pop();
        });
    }
});

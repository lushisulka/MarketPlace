// Add to Cart
function addToCart(productId, quantity = 1) {
    fetch(SITE_URL + '/ajax/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&product_id=${productId}&quantity=${quantity}`
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('✅ ' + data.message, 'success');
                const badge = document.querySelector('.cart-badge');
                if (badge) badge.textContent = data.cart_count;
            } else {
                showToast('❌ ' + (data.error || 'Gabim'), 'danger');
            }
        });
}

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed shadow-lg`;
    toast.style.cssText = 'bottom:20px; right:20px; z-index:9999; min-width:300px; animation: fadeIn 0.3s';
    toast.innerHTML = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
document.addEventListener('DOMContentLoaded', function() {
    const configEl = document.getElementById('checkout-config');
    if (!configEl) return;
    const config = JSON.parse(configEl.textContent);

    const subtotal = config.subtotal;
    const freeShippingOver = config.freeShippingOver;
    const codFee = config.codFee;
    const currency = config.currency;
    
    const selectZone = document.getElementById('shipping_zone');
    const radios = document.querySelectorAll('input[name="payment_method"]');
    const txtShipping = document.getElementById('txt-shipping');
    const txtTotal = document.getElementById('txt-total');
    const btnSubmit = document.getElementById('checkout-btn');
    const rowCod = document.getElementById('row-cod');
    const labelOnline = document.getElementById('label-online');
    const labelCod = document.getElementById('label-cod');

    function formatMoney(amount) {
        return currency + parseFloat(amount).toFixed(2);
    }

    function updateTotal() {
        let shipping = 0;
        const opt = selectZone.options[selectZone.selectedIndex];
        
        if (opt && opt.value) {
            shipping = parseFloat(opt.getAttribute('data-cost')) || 0;
            if (freeShippingOver > 0 && subtotal >= freeShippingOver) {
                shipping = 0;
            }
        }

        let total = subtotal + shipping;
        let isCod = false;
        
        // Active border styling for radio buttons
        if(labelOnline) labelOnline.style.borderColor = 'var(--border)';
        if(labelCod) labelCod.style.borderColor = 'var(--border)';

        radios.forEach(r => {
            if (r.checked) {
                if (r.value === 'cod') {
                    isCod = true;
                    total += codFee;
                    if(labelCod) labelCod.style.borderColor = 'var(--terracotta)';
                } else {
                    if(labelOnline) labelOnline.style.borderColor = 'var(--terracotta)';
                }
            }
        });

        // Update UI
        if (!opt || !opt.value) {
            txtShipping.innerText = 'Select a city';
        } else {
            txtShipping.innerText = shipping === 0 ? 'Free' : formatMoney(shipping);
        }

        if (rowCod) {
            rowCod.style.display = (isCod && codFee > 0) ? 'flex' : 'none';
        }

        txtTotal.innerText = formatMoney(total);
        btnSubmit.innerText = 'Place Order — ' + formatMoney(total);
    }

    selectZone.addEventListener('change', updateTotal);
    radios.forEach(r => r.addEventListener('change', updateTotal));
    
    updateTotal(); // initial run
});

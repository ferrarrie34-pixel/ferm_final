document.addEventListener('DOMContentLoaded', function () {
    const deliverySelect = document.querySelector('#delivery-region');
    const summary = document.querySelector('.summary');
    const deliveryCost = document.querySelector('#delivery-cost');
    const orderTotal = document.querySelector('#order-total');

    function formatRubles(value) {
        return new Intl.NumberFormat('ru-RU').format(value) + ' ₽';
    }

    function updateDelivery() {
        if (!deliverySelect || !summary || !deliveryCost || !orderTotal) {
            return;
        }

        const selectedOption = deliverySelect.options[deliverySelect.selectedIndex];
        const subtotal = Number(summary.dataset.subtotal || 0);
        const cost = Number(selectedOption.dataset.cost || 0);

        deliveryCost.textContent = formatRubles(cost);
        orderTotal.textContent = formatRubles(subtotal + cost);
    }

    if (deliverySelect) {
        deliverySelect.addEventListener('change', updateDelivery);
        updateDelivery();
    }

    document.querySelectorAll('input[type="number"]').forEach(function (input) {
        input.addEventListener('input', function () {
            const min = Number(input.min || 1);
            const max = Number(input.max || 99);
            const value = Number(input.value || min);

            if (value < min) {
                input.value = String(min);
            }

            if (value > max) {
                input.value = String(max);
            }
        });
    });
});
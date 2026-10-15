// Public storefront scripts

document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss flash alerts after 5 seconds
    document.querySelectorAll('.main > .alert').forEach((alert) => {
        setTimeout(() => {
            const instance = bootstrap.Alert.getOrCreateInstance(alert);
            instance.close();
        }, 5000);
    });

    // Prevent negative quantities in cart forms
    document.querySelectorAll('input[type="number"][name="quantity"]').forEach((input) => {
        input.addEventListener('change', () => {
            const min = parseInt(input.min || '1', 10);
            const max = parseInt(input.max || '999', 10);
            let value = parseInt(input.value || String(min), 10);
            if (Number.isNaN(value)) value = min;
            input.value = Math.min(max, Math.max(min, value));
        });
    });
});

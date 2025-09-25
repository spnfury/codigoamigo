const initializeStripe = (publishableKey, config) => {
    const stripe = Stripe(publishableKey);

    const handleCheckout = async (priceId, clientReferenceId) => {
        try {
            const { error } = await stripe.redirectToCheckout({
                mode: 'payment',
                lineItems: [{
                    price: priceId,
                    quantity: 1
                }],
                clientReferenceId,
                billingAddressCollection: 'auto',
                successUrl: `${config.successUrl}?session_id={CHECKOUT_SESSION_ID}`,
                cancelUrl: config.cancelUrl
            });

            if (error) {
                console.error('Error:', error);
                document.getElementById('error-message').textContent = error.message;
            }
        } catch (e) {
            console.error('Error:', e);
        }
    };

    return { handleCheckout };
}; 
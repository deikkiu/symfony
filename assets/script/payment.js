const STRIPE_PUBLIC_KEY = 'pk_test_51QF8smF1xKsVMqq0FKRxmPKz4mHK8rlqp7GI7fPKV6JO9utvzXGntcvmA1Ek9MRRKetcLy1LfMemuy4Qg8liCqAT000b6Uem6x';
const stripe = Stripe(STRIPE_PUBLIC_KEY);

initialize();

// Create a Checkout Session
async function initialize() {
    try {
        const fetchClientSecret = async () => {
            const response = await fetch("/payment/checkout", {
                method: "POST",
            });
            const {clientSecret} = await response.json();
            return clientSecret;
        };

        const checkout = await stripe.initEmbeddedCheckout({
            fetchClientSecret,
        });

        // Mount Checkout
        checkout.mount('#checkout');
    } catch (e) {
        console.error(e.message);
    }
}
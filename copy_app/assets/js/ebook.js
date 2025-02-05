// Konfiguracja Stripe
const stripe = Stripe('your_publishable_key'); // Tutaj trzeba będzie wstawić prawdziwy klucz
const elements = stripe.elements();

// Tworzenie elementu karty płatniczej
const card = elements.create('card', {
    style: {
        base: {
            color: '#fff',
            fontFamily: 'Arial, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: '#aab7c4'
            },
            backgroundColor: 'transparent'
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    }
});

// Montowanie elementu karty
document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('payment-form');
    if (paymentForm) {
        card.mount('#card-element');
    }

    // Obsługa błędów walidacji karty
    card.addEventListener('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
});

// Obsługa formularza płatności
async function handlePayment(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.textContent = 'Przetwarzanie...';

    try {
        // Tworzenie tokenu płatności
        const {token, error} = await stripe.createToken(card);

        if (error) {
            const errorElement = document.getElementById('card-errors');
            errorElement.textContent = error.message;
            submitButton.disabled = false;
            submitButton.textContent = 'Zapłać';
            return;
        }

        // Wysyłanie tokenu do serwera
        const response = await fetch('/process-payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                token: token.id,
                amount: 4999, // 49.99 PLN w groszach
                email: document.getElementById('email').value
            })
        });

        const result = await response.json();

        if (result.success) {
            // Przekierowanie do strony podziękowania
            window.location.href = '/thank-you.html';
        } else {
            throw new Error(result.error);
        }

    } catch (err) {
        const errorElement = document.getElementById('card-errors');
        errorElement.textContent = 'Wystąpił błąd podczas przetwarzania płatności. Spróbuj ponownie.';
        submitButton.disabled = false;
        submitButton.textContent = 'Zapłać';
    }
}

// Otwieranie modalu z płatnością
function openPaymentModal() {
    const modal = document.getElementById('payment-modal');
    modal.style.display = 'flex';
}

// Zamykanie modalu
function closePaymentModal() {
    const modal = document.getElementById('payment-modal');
    modal.style.display = 'none';
}

// Nasłuchiwanie kliknięć przycisków "Kup teraz"
document.addEventListener('DOMContentLoaded', function() {
    const buyButtons = document.querySelectorAll('.buy-now-btn');
    buyButtons.forEach(button => {
        button.addEventListener('click', openPaymentModal);
    });

    // Zamykanie modalu przy kliknięciu poza nim
    window.onclick = function(event) {
        const modal = document.getElementById('payment-modal');
        if (event.target === modal) {
            closePaymentModal();
        }
    };

    // Obsługa formularza płatności
    const paymentForm = document.getElementById('payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', handlePayment);
    }
});

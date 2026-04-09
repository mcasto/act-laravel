{{-- <div>
    <p>Transfer {{ $param }}:
    <ul>
        <li>Laura Smith Jessup</li>
        <li>JEP Savings Account (Ahorros)</li>
        <li>Account number 406183057801</li>
        <li>CI: 0152404844</li>
    </ul>
    </p>
    <p>
        Then click to email us
        <a href='mailto:actseats@gmail.com'>actseats@gmail.com</a>
        confirming you sent {{ $param }} by bank transfer and the transfer date. Also
        include your name and
        phone number. We will reply to confirm receipt of payment.
    </p>

</div> --}}

<style>
    .q-field {
        display: flex;
        flex-direction: column;
        width: 100%;
        margin-bottom: 16px;
    }

    .q-field label {
        font-size: 12px;
        font-weight: 400;
        color: rgba(0, 0, 0, 0.6);
        letter-spacing: 0.00937em;
        margin-bottom: 4px;
    }

    .q-field input {
        width: 100%;
        border: 1px solid rgba(0, 0, 0, 0.24);
        border-radius: 4px;
        padding: 4px 12px;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.87);
        background: transparent;
        box-sizing: border-box;
        outline: none;
        transition: border-color 0.2s;
    }

    .q-field input:focus {
        border-color: #1976d2;
        border-width: 2px;
    }

    .q-field input:focus+.q-field__after,
    .q-field:focus-within label {
        color: #1976d2;
    }
</style>

<div>
    <p>Transfer {{ $param }}:
    <ul>
        <li>Laura Smith Jessup</li>
        <li>JEP Savings Account (Ahorros)</li>
        <li>Account number 406183057801</li>
        <li>CI: 0152404844</li>
    </ul>
    </p>

    <fieldset style='border: 1px solid rgba(0,0,0,0.24); border-radius: 4px; padding: 12px 16px;'>
        <legend style='font-size: 13px; color: rgba(0,0,0,0.6); padding: 0 4px;'>2. Submit this form</legend>
        <form method='post' action='/api/ticket-sales'
            onsubmit="event.preventDefault();
                const form = event.target;
                const errEl = document.getElementById('transfer-error');
                errEl.textContent = '';
                fetch('/api/ticket-sales', { method: 'POST', body: new FormData(form) })
                    .then(r => r.json().then(data => ({ ok: r.ok, data })))
                    .then(({ ok, data }) => {
                        if (!ok) {
                            errEl.textContent = data.errors
                                ? Object.values(data.errors).flat()[0]
                                : (data.message || 'Something went wrong.');
                            return;
                        }
                        window.location.href = '/ticket-confirmation/' + data.transaction_id;
                    })
                    .catch(() => { errEl.textContent = 'Network error. Please try again.'; });"
        >
            <input type='hidden' name='type' value='transfer' />
            <input type='hidden' name='performance_id' />
            <div class='q-field'>
                <label for='transfer-email'>Email</label>
                <input type='email' id='transfer-email' name='email' required
                    onblur="fetch('/api/patrons/lookup?email=' + encodeURIComponent(this.value))
                        .then(r => r.ok ? r.json() : null)
                        .then(p => { if (p) { document.getElementById('transfer-first-name').value = p.first_name; document.getElementById('transfer-last-name').value = p.last_name; document.getElementById('transfer-phone').value = p.phone; } })" />
            </div>
            <div class='q-field'>
                <label for='transfer-first-name'>First Name</label>
                <input type='text' id='transfer-first-name' name='first_name' required />
            </div>
            <div class='q-field'>
                <label for='transfer-last-name'>Last Name</label>
                <input type='text' id='transfer-last-name' name='last_name' required />
            </div>
            <div class='q-field'>
                <label for='transfer-phone'>Phone</label>
                <input type='tel' id='transfer-phone' name='phone' required />
            </div>

            <div class='q-field'>
                <label for='transfer-quantity'>Number of Tickets</label>
                <input type='number' id='transfer-quantity' name='quantity' min='1' required />
            </div>

            <div id='transfer-error' style='color: #c10015; font-size: 13px; margin-bottom: 8px;'></div>
            <div class='flex justify-end'>
                <button type='submit' class='cursor-pointer bg-primary text-white q-pa-sm'
                    style='border:none;'>PURCHASE</button>
            </div>
        </form>
    </fieldset>
</div>

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
    <fieldset style='border: 1px solid rgba(0,0,0,0.24); border-radius: 4px; padding: 12px 16px;'>
        <legend style='font-size: 13px; color: rgba(0,0,0,0.6); padding: 0 4px;'>Submit this form</legend>
        <form method='post' action='/api/ticket-sales'
            onsubmit="event.preventDefault();
                const form = event.target;
                const errEl = document.getElementById('flex-error');
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
            <input type='hidden' name='type' value='flex' />
            <input type='hidden' name='performance_id' />
            <div class='q-field'>
                <label for='flex-email'>Email</label>
                <input type='email' id='flex-email' name='email' required
                    onblur="fetch('/api/patrons/lookup?email=' + encodeURIComponent(this.value))
                        .then(r => r.ok ? r.json() : null)
                        .then(p => { if (p) { document.getElementById('flex-first-name').value = p.first_name; document.getElementById('flex-last-name').value = p.last_name; document.getElementById('flex-phone').value = p.phone; } })" />
            </div>
            <div class='q-field'>
                <label for='flex-first-name'>First Name</label>
                <input type='text' id='flex-first-name' name='first_name' required />
            </div>
            <div class='q-field'>
                <label for='flex-last-name'>Last Name</label>
                <input type='text' id='flex-last-name' name='last_name' required />
            </div>
            <div class='q-field'>
                <label for='flex-phone'>Phone</label>
                <input type='tel' id='flex-phone' name='phone' required />
            </div>
            <div class='q-field'>
                <label for='flex-quantity'>Number of Tickets</label>
                <input type='number' id='flex-quantity' name='quantity' min='1' required />
            </div>

            <div id='flex-error' style='color: #c10015; font-size: 13px; margin-bottom: 8px;'></div>
            <div class='flex justify-end'>
                <button type='submit' class='cursor-pointer bg-primary text-white q-pa-sm'
                    style='border:none;'>PURCHASE</button>
            </div>
        </form>
    </fieldset>
</div>

/**
 * main.js — LuxCarry Frontend Utilities
 */

'use strict';

/**
 * Toggle password field visibility.
 * @param {string} fieldId — The input field ID
 */
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    field.type = field.type === 'password' ? 'text' : 'password';
    const btn = field.nextElementSibling;
    if (btn) {
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = field.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        }
    }
}

/**
 * Auto-submit OTP form when 6 digits entered.
 */
document.addEventListener('DOMContentLoaded', () => {

    // OTP auto-submit
    const otpInput = document.getElementById('otp');
    if (otpInput) {
        otpInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length === 6) {
                setTimeout(() => this.closest('form')?.submit(), 200);
            }
        });
    }

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert.alert-success, .alert.alert-info').forEach(el => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert?.close();
        }, 5000);
    });

    // Password strength indicator
    const pwField = document.getElementById('password');
    if (pwField) {
        const indicator = document.createElement('div');
        indicator.className = 'password-strength mt-1';
        pwField.parentElement.parentElement.appendChild(indicator);

        pwField.addEventListener('input', function () {
            const val = this.value;
            let strength = 0;
            let label = '';
            let color = '';

            if (val.length >= 8)  strength++;
            if (val.length >= 12) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            if (strength <= 1)      { label = 'Weak';   color = '#dc3545'; }
            else if (strength <= 3) { label = 'Medium'; color = '#fd7e14'; }
            else                    { label = 'Strong';  color = '#198754'; }

            if (val.length === 0) {
                indicator.innerHTML = '';
            } else {
                indicator.innerHTML = `
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <div style="flex:1; height:4px; background:#eee; border-radius:99px; overflow:hidden;">
                            <div style="width:${(strength/5)*100}%; height:100%; background:${color}; border-radius:99px; transition:width .3s"></div>
                        </div>
                        <small style="color:${color}; font-size:0.75rem;">${label}</small>
                    </div>`;
            }
        });
    }
});

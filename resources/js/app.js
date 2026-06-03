import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

window.formatRupiah = function (angka) {
    if (!angka) return 'Rp 0';
    let number = parseFloat(angka).toFixed(0);
    return 'Rp ' + number.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

window.showToast = function (message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) return;

    const iconMap = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill',
    };

    const bgMap = {
        success: 'bg-emerald-500',
        error: 'bg-red-500',
        warning: 'bg-amber-500',
        info: 'bg-cyan-500',
    };

    const toastEl = document.createElement('div');
    toastEl.className = `toast ${bgMap[type] || bgMap.info}`;
    toastEl.innerHTML = `
        <i class="bi ${iconMap[type] || iconMap.info}"></i>
        <span class="flex-1">${message}</span>
        <button type="button" class="ml-auto text-white/80 hover:text-white" onclick="this.parentElement.remove()">
            <i class="bi bi-x-lg"></i>
        </button>
    `;
    toastContainer.appendChild(toastEl);
    setTimeout(() => {
        toastEl.style.opacity = '0';
        toastEl.style.transition = 'opacity 0.3s';
        setTimeout(() => toastEl.remove(), 300);
    }, 4000);
};

window.showLoader = function () {
    const loader = document.getElementById('loader-overlay');
    if (loader) loader.classList.add('show');
};

window.hideLoader = function () {
    const loader = document.getElementById('loader-overlay');
    if (loader) loader.classList.remove('show');
};

// Auto show toast on page load from session messages
(function toastInit() {
    const body = document.body;
    if (!body) return setTimeout(toastInit, 10);
    if (body.dataset.toastSuccess) {
        showToast(body.dataset.toastSuccess, 'success');
    }
    if (body.dataset.toastError) {
        showToast(body.dataset.toastError, 'error');
    }
    if (body.dataset.toastWarning) {
        showToast(body.dataset.toastWarning, 'warning');
    }

    const invalidInputs = document.querySelectorAll('.is-invalid');
    if (invalidInputs.length > 0) {
        invalidInputs.forEach(function (input) {
            const feedback = input.parentElement?.querySelector('.invalid-feedback');
            if (feedback && feedback.textContent.trim()) {
                showToast(feedback.textContent.trim(), 'warning');
            }
        });
    }
})();

// Currency auto-format for inputs with class .rupiah-input
(function currencyInit() {
    document.querySelectorAll('.rupiah-input').forEach(function (input) {
        input.addEventListener('blur', function () {
            let val = this.value.replace(/\D/g, '');
            if (val === '') { this.value = ''; return; }
            this.value = parseInt(val, 10).toLocaleString('id-ID');
        });
        input.addEventListener('focus', function () {
            this.value = this.value.replace(/\D/g, '');
        });
        input.addEventListener('input', function () {
            let raw = this.value.replace(/\D/g, '');
            if (raw.length > 0 && raw.length <= 3) {
                this.value = raw;
            }
        });
        let initVal = input.value.replace(/\D/g, '');
        if (initVal && initVal !== '0' && initVal !== '') {
            input.value = parseInt(initVal, 10).toLocaleString('id-ID');
        }
    });
})();

// Global: get raw number from rupiah input
window.getRawRupiah = function (el) {
    let val = (typeof el === 'string') ? el : (el.value || '');
    return parseInt(val.replace(/\D/g, ''), 10) || 0;
};

// Show loader on form submit
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form.dataset.noloading !== 'true') {
        showLoader();
    }
});

// Show loader on delete buttons that trigger form submission
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-loading]');
    if (btn) {
        showLoader();
    }
});

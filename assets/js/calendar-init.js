/**
 * Surat Digital TVRI Jawa Timur
 * Calendar Popup Initializer & Interactive Manager
 */

(function () {
    'use strict';

    function initDatePickerElement(inputEl) {
        if (!inputEl || inputEl.dataset.flatpickrInitialized === 'true') {
            return;
        }

        // Check if flatpickr library is available
        if (typeof flatpickr === 'undefined') {
            console.warn('Flatpickr is not loaded.');
            return;
        }

        // Indonesian locale
        const idLocale = (flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default';

        // Read formats and initial values
        const dateFormat = inputEl.dataset.dateFormat || 'Y-m-d';
        const altFormat = inputEl.dataset.altFormat || 'j F Y';
        const initialVal = inputEl.dataset.defaultDate || inputEl.value || inputEl.getAttribute('value') || '';

        // Safely parse initial value into a Date object so flatpickr doesn't misparse YYYY-MM-DD with j F Y format
        let defaultDateVal = null;
        if (typeof initialVal === 'string' && initialVal.trim()) {
            const raw = initialVal.trim();
            if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
                const parts = raw.split('-').map(Number);
                defaultDateVal = new Date(parts[0], parts[1] - 1, parts[2]);
            } else if (raw.toLowerCase() === 'today') {
                defaultDateVal = new Date();
            } else {
                const idMonths = {
                    'januari': 0, 'februari': 1, 'maret': 2, 'april': 3,
                    'mei': 4, 'juni': 5, 'juli': 6, 'agustus': 7,
                    'september': 8, 'oktober': 9, 'november': 10, 'desember': 11
                };
                const mMatch = raw.match(/^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})$/);
                if (mMatch && idMonths[mMatch[2].toLowerCase()] !== undefined) {
                    defaultDateVal = new Date(parseInt(mMatch[3], 10), idMonths[mMatch[2].toLowerCase()], parseInt(mMatch[1], 10));
                } else {
                    defaultDateVal = raw;
                }
            }
        }
        if (!defaultDateVal && (inputEl.id === 'inTanggalAgenda' || inputEl.name === 'tanggal_agenda')) {
            defaultDateVal = new Date();
        }

        const fpInstance = flatpickr(inputEl, {
            locale: idLocale,
            dateFormat: dateFormat,
            altInput: true,
            altFormat: altFormat,
            altInputClass: 'form-input-custom modern-datepicker-alt',
            disableMobile: true,
            defaultDate: defaultDateVal || null,
            monthSelectorType: 'static', // Static display only (no interactive dropdown)
            animate: true,
            onReady: function (selectedDates, dateStr, instance) {
                inputEl.dataset.flatpickrInitialized = 'true';

                if (instance.calendarContainer) {
                    // Disable and lock year input so it only acts as static display text
                    const yearInput = instance.calendarContainer.querySelector('input.cur-year');
                    if (yearInput) {
                        yearInput.readOnly = true;
                        yearInput.tabIndex = -1;
                        yearInput.disabled = true;
                    }

                    // Remove any up/down arrows from year input
                    const yearArrows = instance.calendarContainer.querySelectorAll('.numInputWrapper span.arrowUp, .numInputWrapper span.arrowDown');
                    yearArrows.forEach(function (arrowEl) {
                        arrowEl.remove();
                    });
                }

                // If input is wrapped in .modern-datepicker-wrapper, bind calendar icon
                const wrapper = inputEl.closest('.modern-datepicker-wrapper');
                if (wrapper) {
                    const iconBtn = wrapper.querySelector('.datepicker-calendar-icon');
                    if (iconBtn && !iconBtn.dataset.bound) {
                        iconBtn.dataset.bound = 'true';
                        iconBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            instance.open();
                        });
                    }
                }
            },
            onChange: function (selectedDates, dateStr, instance) {
                // Ensure underlying input value is updated
                inputEl.value = dateStr;

                // Dispatch native events so preview modals and validation immediately react
                inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                inputEl.dispatchEvent(new Event('change', { bubbles: true }));

                if (typeof inputEl.oninput === 'function') {
                    inputEl.oninput();
                }
                if (typeof inputEl.onchange === 'function') {
                    inputEl.onchange();
                }
            }
        });

        return fpInstance;
    }

    function initAllModernDatepickers() {
        // Select all explicit modern datepickers
        const targets = document.querySelectorAll('.modern-datepicker, input[type="date"]');
        targets.forEach(function (el) {
            initDatePickerElement(el);
        });
    }

    // Expose globally for dynamic components
    window.initDatePickerElement = initDatePickerElement;
    window.initAllModernDatepickers = initAllModernDatepickers;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllModernDatepickers);
    } else {
        initAllModernDatepickers();
    }
})();

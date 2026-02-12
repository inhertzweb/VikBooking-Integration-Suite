jQuery(document).ready(function ($) {
    const $widget = $('#vb-mobile-widget');
    const $modal = $('#vb-mw-modal');
    const $openBtn = $('#vb-mw-open-calendar');
    const $closeBtn = $('#vb-mw-modal-close');
    const $confirmBtn = $('#vb-mw-confirm');
    const $calendarContainer = $('#vb-mw-calendar-container');

    let checkIn = null;
    let checkOut = null;
    let adults = 2;
    let children = 0;
    let bookedDates = [];

    // Fetch availability on init
    function fetchAvailability() {
        $.ajax({
            url: vbMWSettings.ajaxUrl,
            data: {
                action: 'vb_mw_get_availability'
            },
            success: function (response) {
                if (response.success) {
                    bookedDates = response.data;
                    renderCalendar();
                }
            }
        });
    }

    fetchAvailability();
    updateConfirmLink();

    // Open/Close Modal
    $openBtn.on('click', function () {
        $modal.css('display', 'flex').hide().fadeIn(200);
        renderCalendar();
    });

    $closeBtn.on('click', function () {
        $modal.fadeOut(200);
    });

    $(window).on('click', function (e) {
        if ($(e.target).is($modal)) {
            $modal.fadeOut(200);
        }
    });

    // Guest Counter Logic
    $('.vb-mw-plus, .vb-mw-minus').on('click', function () {
        const target = $(this).data('target');
        const isPlus = $(this).hasClass('vb-mw-plus');
        const $input = $(`#mw-${target}`);
        let val = parseInt($input.val());

        if (isPlus) {
            val++;
        } else {
            val = Math.max(target === 'adults' ? 1 : 0, val - 1);
        }

        $input.val(val);
        if (target === 'adults') adults = val;
        if (target === 'children') children = val;
        updateConfirmLink();
    });

    // Simple Calendar Implementation
    let currentViewDate = new Date();
    // Initialize View Date to first available month if needed
    if (isMonthFullyClosed(currentViewDate.getMonth(), currentViewDate.getFullYear())) {
        const next = getNextAvailableMonth(currentViewDate, 1);
        if (next) currentViewDate = next;
    }

    function getNextAvailableMonth(date, direction = 1) {
        let tempDate = new Date(date);
        let offset = 0;
        // Search up to 24 months
        while (offset < 24) {
            tempDate.setMonth(tempDate.getMonth() + direction);
            // If direction is -1 (back), we shouldn't go before today's actual month
            if (direction === -1) {
                const today = new Date();
                today.setDate(1);
                today.setHours(0, 0, 0, 0);
                if (tempDate < today) return null;
            }

            if (!isMonthFullyClosed(tempDate.getMonth(), tempDate.getFullYear())) {
                return tempDate;
            }
            offset++;
        }
        return null;
    }

    function renderCalendar() {
        const month = currentViewDate.getMonth();
        const year = currentViewDate.getFullYear();

        const monthNames = ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
            "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"
        ];

        let headerHtml = `
            <div class="vb-mw-cal-header">
                <button type="button" class="vb-mw-cal-nav vb-mw-prev">&lsaquo;</button>
                <h4>${monthNames[month]} ${year}</h4>
                <button type="button" class="vb-mw-cal-nav vb-mw-next">&rsaquo;</button>
            </div>
        `;

        let gridHtml = renderMonthGrid(month, year);

        $calendarContainer.html(headerHtml + gridHtml);
        updateSelectionStyles();
    }

    // Navigation Events
    $calendarContainer.on('click', '.vb-mw-prev', function (e) {
        e.stopPropagation();
        const prev = getNextAvailableMonth(currentViewDate, -1);
        if (prev) {
            currentViewDate = prev;
            renderCalendar();
        }
    });

    $calendarContainer.on('click', '.vb-mw-next', function (e) {
        e.stopPropagation();
        const next = getNextAvailableMonth(currentViewDate, 1);
        if (next) {
            currentViewDate = next;
            renderCalendar();
        }
    });

    function renderMonthGrid(month, year) {
        const dayNames = ["Lu", "Ma", "Me", "Gi", "Ve", "Sa", "Do"];
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        let startingDay = firstDay === 0 ? 6 : firstDay - 1;

        let html = `<div class="vb-mw-calendar-month"><div class="vb-mw-calendar-grid">`;

        dayNames.forEach(d => html += `<div class="vb-mw-cal-day-name">${d}</div>`);

        for (let i = 0; i < startingDay; i++) {
            html += '<div class="vb-mw-cal-day empty"></div>';
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const isPast = date < today;

            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');
            const dateStr = `${yyyy}-${mm}-${dd}`;

            const isBooked = bookedDates.indexOf(dateStr) !== -1;

            const classes = ['vb-mw-cal-day'];
            if (isPast || isBooked) classes.push('disabled');
            if (isBooked) classes.push('booked');
            if (date.getTime() === today.getTime()) classes.push('today');

            html += `<div class="${classes.join(' ')}" data-date="${dateStr}">${day}</div>`;
        }

        html += '</div></div>';
        return html;
    }

    function isMonthFullyClosed(month, year) {
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        let hasAvailableDay = false;
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            if (date < today) continue;

            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');
            const dateStr = `${yyyy}-${mm}-${dd}`;

            if (bookedDates.indexOf(dateStr) === -1) {
                hasAvailableDay = true;
                break;
            }
        }
        // If no days are available in the future for this month, it's closed
        return !hasAvailableDay;
    }


    $calendarContainer.on('click', '.vb-mw-cal-day:not(.disabled):not(.empty)', function () {
        const dateStr = $(this).data('date');
        const selectedDate = new Date(dateStr);

        if (!checkIn || (checkIn && checkOut)) {
            checkIn = selectedDate;
            checkOut = null;
        } else if (selectedDate < checkIn) {
            checkIn = selectedDate;
            checkOut = null;
        } else if (selectedDate.getTime() === checkIn.getTime()) {
            checkIn = null;
        } else {
            // Check if there are any booked dates between check-in and potential check-out
            let hasBookedInRange = false;
            let tempDate = new Date(checkIn);
            tempDate.setDate(tempDate.getDate() + 1);

            while (tempDate < selectedDate) {
                const yyyy = tempDate.getFullYear();
                const mm = String(tempDate.getMonth() + 1).padStart(2, '0');
                const dd = String(tempDate.getDate()).padStart(2, '0');
                const dStr = `${yyyy}-${mm}-${dd}`;

                if (bookedDates.indexOf(dStr) !== -1) {
                    hasBookedInRange = true;
                    break;
                }
                tempDate.setDate(tempDate.getDate() + 1);
            }

            if (hasBookedInRange) {
                alert('La selezione contiene date non disponibili. Scegliere un altro intervallo.');
            } else {
                checkOut = selectedDate;
            }
        }

        updateSelectionStyles();
    });

    function updateSelectionStyles() {
        $('.vb-mw-cal-day').removeClass('selected in-range');
        if (checkIn) {
            const ciStr = checkIn.toISOString().split('T')[0];
            $(`.vb-mw-cal-day[data-date="${ciStr}"]`).addClass('selected');
        }
        if (checkOut) {
            const coStr = checkOut.toISOString().split('T')[0];
            $(`.vb-mw-cal-day[data-date="${coStr}"]`).addClass('selected');

            // Highlight range
            $('.vb-mw-cal-day:not(.empty)').each(function () {
                const dStr = $(this).data('date');
                if (dStr) {
                    const d = new Date(dStr);
                    if (d > checkIn && d < checkOut) {
                        $(this).addClass('in-range');
                    }
                }
            });
        }
        updateConfirmLink();
    }

    // Dynamic Link Update
    function updateConfirmLink() {
        if (!checkIn || !checkOut) {
            $confirmBtn.attr('href', '#');
            $confirmBtn.css('opacity', '0.5');
            return;
        }

        $confirmBtn.css('opacity', '1');

        const formatDate = (date) => {
            const d = String(date.getDate()).padStart(2, '0');
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const y = date.getFullYear();

            // VikBooking usually expects standard format, but let's respect settings if needed
            // However, for URL parameters, YYYY-MM-DD or specific format might be required by controller
            // Let's stick to the previous logic which seemed to map to VikBooking expectations

            const format = vbMWSettings.dateFormat || '%d/%m/%Y';

            if (format === '%m/%d/%Y') {
                return `${m}/${d}/${y}`;
            } else if (format === '%Y/%m/%d') {
                return `${y}/${m}/${d}`;
            }

            // Default %d/%m/%Y
            return `${d}/${m}/${y}`;
        };

        const ci = formatDate(checkIn);
        const co = formatDate(checkOut);

        let url = vbMWSettings.bookingUrl;
        if (url.indexOf('?') === -1) url += '?';
        else url += '&';

        url += `option=com_vikbooking&view=search&task=search`;
        url += `&checkindate=${ci}&checkoutdate=${co}`;
        url += `&adults=${adults}&children=${children}&roomsnum=1`;

        $confirmBtn.attr('href', url);
    }

    $confirmBtn.on('click', function (e) {
        if ($(this).attr('href') === '#') {
            e.preventDefault();
            alert('Seleziona le date di check-in e check-out');
        }
    });
});

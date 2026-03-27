/**
 * Offer Popup A/B Testing Logic
 */

document.addEventListener('DOMContentLoaded', function () {
    if (typeof vbOfferSettings === 'undefined' || !vbOfferSettings.offers || vbOfferSettings.offers.length === 0) {
        return;
    }

    const container = document.getElementById('vb-offer-popup-container');
    if (!container) return;

    const offers = vbOfferSettings.offers;
    let selectedOffer = null;

    // Check if we already have an offer assigned in localStorage for consistency
    const savedOfferId = localStorage.getItem('vb_ab_offer_id');
    
    if (savedOfferId) {
        selectedOffer = offers.find(o => o.id == savedOfferId);
    }

    // If no saved offer or saved offer is no longer active, pick a random one
    if (!selectedOffer) {
        const randomIndex = Math.floor(Math.random() * offers.length);
        selectedOffer = offers[randomIndex];
        localStorage.setItem('vb_ab_offer_id', selectedOffer.id);
    }

    // Build the popup HTML
    let thumbnailHtml = '';
    if (selectedOffer.thumbnail) {
        thumbnailHtml = `<img src="${selectedOffer.thumbnail}" alt="${selectedOffer.title}" class="vb-offer-thumbnail">`;
    }

    // Determine CTA Text
    let ctaText = vbOfferSettings.labels.copyBtn;
    if (!selectedOffer.coupon) {
        ctaText = 'Prenota Ora'; // Testo predefinito per offerte senza coupon
    }

    const popupHtml = `
        <div id="vb-ab-popup" class="vb-offer-popup" data-offer-id="${selectedOffer.id}" data-coupon="${selectedOffer.coupon}" data-url="${selectedOffer.custom_url}">
            <div class="vb-offer-header">
                <h4 class="vb-offer-title">${selectedOffer.title}</h4>
                <button class="vb-offer-close" aria-label="Close">&times;</button>
            </div>
            <div class="vb-offer-body">
                ${thumbnailHtml}
                <div class="vb-offer-content">${selectedOffer.content}</div>
            </div>
            <div class="vb-offer-footer">
                <button class="vb-offer-cta">${ctaText}</button>
            </div>
        </div>
    `;

    container.innerHTML = popupHtml;
    container.style.display = 'block';

    const popupEl = document.getElementById('vb-ab-popup');
    const closeBtn = popupEl.querySelector('.vb-offer-close');
    const ctaBtn = popupEl.querySelector('.vb-offer-cta');

    // Show popup after a short delay (e.g. 3 seconds)
    setTimeout(() => {
        // Only show if the user hasn't explicitly dismissed this offer today/recently
        const dismissed = localStorage.getItem('vb_ab_offer_dismissed_' + selectedOffer.id);
        const now = new Date().getTime();
        
        // Let's show it if never dismissed or dismissed more than 24h ago
        if (!dismissed || (now - parseInt(dismissed) > 86400000)) {
            popupEl.classList.add('vb-offer-show');
            trackEvent('vb_track_offer_view', selectedOffer.id);
        }
    }, 3000);

    // Close logic
    closeBtn.addEventListener('click', () => {
        popupEl.classList.remove('vb-offer-show');
        localStorage.setItem('vb_ab_offer_dismissed_' + selectedOffer.id, new Date().getTime());
    });

    // Handle Opening behavior (Mobile Widget or Custom URL)
    function openDestination(popupEl, ctaBtn) {
        setTimeout(() => {
            popupEl.classList.remove('vb-offer-show');
            const customUrl = popupEl.getAttribute('data-url');
            if (customUrl) {
                window.location.href = customUrl;
                return;
            }
            
            // Trigger mobile widget if it exists
            const mobileWidgetBtn = document.getElementById('vb-mw-open-calendar');
            if (mobileWidgetBtn) {
                mobileWidgetBtn.click();
            } else {
                if(typeof vbMWSettings !== 'undefined' && vbMWSettings.bookingUrl) {
                     window.location.href = vbMWSettings.bookingUrl;
                }
            }
        }, 1000);
    }

    // CTA Logic: copy coupon, track click, open booking widget
    ctaBtn.addEventListener('click', (e) => {
        e.preventDefault();
        trackEvent('vb_track_offer_click', selectedOffer.id);

        const coupon = popupEl.getAttribute('data-coupon');
        if (coupon && coupon.trim() !== '') {
            navigator.clipboard.writeText(coupon).then(() => {
                ctaBtn.innerText = vbOfferSettings.labels.copied;
                openDestination(popupEl, ctaBtn);
            }).catch(err => {
                console.error('Failed to copy: ', err);
                openDestination(popupEl, ctaBtn);
            });
        } else {
            // No coupon, just go to destination
            ctaBtn.innerText = 'Apertura in corso...';
            openDestination(popupEl, ctaBtn);
        }
    });

    // Simple AJAX wrapper for tracking
    function trackEvent(action, offerId) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('offer_id', offerId);

        fetch(vbOfferSettings.ajaxUrl, {
            method: 'POST',
            body: formData
        }).catch(err => console.error('Tracking error', err));
    }
});

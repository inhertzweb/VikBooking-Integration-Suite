/**
 * Offer Popup A/B Testing Logic
 */

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('vb-offer-popup-container');
    if (!container) return;

    const offerElements = Array.from(container.querySelectorAll('.vb-ab-popup-instance'));
    if (offerElements.length === 0) return;

    let selectedEl = null;
    let selectedOfferId = null;

    // Check if we already have an offer assigned in localStorage for consistency
    const savedOfferId = localStorage.getItem('vb_ab_offer_id');
    
    if (savedOfferId) {
        selectedEl = offerElements.find(el => el.getAttribute('data-offer-id') === savedOfferId);
    }

    // If no saved offer or saved offer is no longer active, pick a random one
    if (!selectedEl) {
        const randomIndex = Math.floor(Math.random() * offerElements.length);
        selectedEl = offerElements[randomIndex];
        selectedOfferId = selectedEl.getAttribute('data-offer-id');
        localStorage.setItem('vb_ab_offer_id', selectedOfferId);
    } else {
        selectedOfferId = savedOfferId;
    }

    // Remove all other offers from DOM to avoid duplicate IDs/content footprint
    offerElements.forEach(el => {
        if (el !== selectedEl) {
            el.remove();
        }
    });

    // We now have exactly one offer popup element
    selectedEl.style.display = '';
    selectedEl.id = 'vb-ab-popup';
    container.style.display = 'block';

    const popupEl = selectedEl;
    const closeBtn = popupEl.querySelector('.vb-offer-close');
    const ctaBtn = popupEl.querySelector('.vb-offer-cta');

    // Helper to manage cookies
    function setCookie(name, value, hours) {
        let expires = "";
        if (hours) {
            const date = new Date();
            date.setTime(date.getTime() + (hours * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "")  + expires + "; path=/";
    }

    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for(let i=0;i < ca.length;i++) {
            let c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
    }

    const isBlocked = getCookie('vb_ab_offer_blocked');
    let offerShown = false;

    function showPopup() {
        if (!isBlocked && !offerShown) {
            offerShown = true;
            popupEl.classList.add('vb-offer-show');
            trackEvent('vb_track_offer_view', selectedOfferId);
        }
    }

    if (window.innerWidth <= 768) {
        const scrollTrigger = () => {
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            if (maxScroll <= 0) {
                showPopup();
                window.removeEventListener('scroll', scrollTrigger);
                return;
            }
            const scrollPercent = (window.scrollY / maxScroll) * 100;
            if (scrollPercent >= 25) {
                showPopup();
                window.removeEventListener('scroll', scrollTrigger);
            }
        };
        // Initial check in case they load already scrolled down
        scrollTrigger();
        window.addEventListener('scroll', scrollTrigger, { passive: true });
    } else {
        // Desktop: wait 3 seconds
        setTimeout(showPopup, 3000);
    }

    // Close logic
    closeBtn.addEventListener('click', () => {
        popupEl.classList.remove('vb-offer-show');
        // Block for 48 hours globally
        setCookie('vb_ab_offer_blocked', 'true', 48);
    });

    // Handle Opening behavior (Mobile Widget or Custom URL)
    function openDestination(popupEl, ctaBtn) {
        setTimeout(() => {
            popupEl.classList.remove('vb-offer-show');
            const customUrl = ctaBtn.getAttribute('href');
            if (customUrl && customUrl !== '#' && customUrl.trim() !== '') {
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
        trackEvent('vb_track_offer_click', selectedOfferId);
        
        // Block for 30 days globally (30 * 24 = 720 hours)
        setCookie('vb_ab_offer_blocked', 'true', 720);

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

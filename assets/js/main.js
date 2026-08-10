const initMobileMenu = () => {
    const menuToggle = document.querySelector('.nav-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileClose = document.querySelector('.mobile-menu__close');
    const servicesToggle = document.querySelector('[data-mobile-services]');
    const servicesSubmenu = document.querySelector('.mobile-menu__submenu');

    const addMobileQuoteButton = () => {
        if (document.querySelector('.mobile-cta')) return;
        const quoteLink = document.createElement('a');
        quoteLink.className = 'mobile-cta';
        quoteLink.href = '/contact/#quote-form';
        quoteLink.textContent = 'Get Quote';
        if (menuToggle?.parentElement) {
            menuToggle.insertAdjacentElement('afterend', quoteLink);
        }
    };

    addMobileQuoteButton();

    menuToggle?.addEventListener('click', () => {
        mobileMenu?.classList.add('open');
        document.body.classList.add('menu-open');
        mobileMenu?.setAttribute('aria-hidden', 'false');
        menuToggle.setAttribute('aria-expanded', 'true');
    });

    mobileClose?.addEventListener('click', () => {
        mobileMenu?.classList.remove('open');
        document.body.classList.remove('menu-open');
        mobileMenu?.setAttribute('aria-hidden', 'true');
        menuToggle?.setAttribute('aria-expanded', 'false');
    });

    servicesToggle?.addEventListener('click', () => {
        const opened = servicesSubmenu?.classList.toggle('open');
        servicesToggle.setAttribute('aria-expanded', opened ? 'true' : 'false');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobileMenu?.classList.contains('open')) {
            mobileMenu.classList.remove('open');
            mobileMenu.setAttribute('aria-hidden', 'true');
            menuToggle?.setAttribute('aria-expanded', 'false');
        }
    });
};

const initCounters = () => {
    const items = document.querySelectorAll('[data-counter]');
    if (!items.length) return;
    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            const finalValue = Number(el.dataset.counter);
            const duration = 1400;
            let startTime = null;
            const step = (timestamp) => {
                if (!startTime) startTime = timestamp;
                const progress = Math.min((timestamp - startTime) / duration, 1);
                el.textContent = Math.floor(progress * finalValue);
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    el.textContent = finalValue;
                }
            };
            requestAnimationFrame(step);
            obs.unobserve(el);
        });
    }, { threshold: 0.4 });
    items.forEach((item) => observer.observe(item));
};

const initQuoteForms = () => {
    const forms = document.querySelectorAll('.quote-form');
    forms.forEach((form) => {
        const status = form.querySelector('.form-status');
        if (!form.querySelector('input[name="website"]')) {
            const honeypot = document.createElement('input');
            honeypot.type = 'text';
            honeypot.name = 'website';
            honeypot.tabIndex = -1;
            honeypot.autocomplete = 'off';
            honeypot.style.position = 'absolute';
            honeypot.style.left = '-9999px';
            honeypot.style.opacity = '0';
            form.appendChild(honeypot);
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            const submitButton = form.querySelector('button[type="submit"]');
            const action = form.getAttribute('action') || '/contact-form-handler.php';
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';
            status.classList.remove('active', 'error');
            status.textContent = '';

            try {
                const response = await fetch(action, {
                    method: 'POST',
                    body: new FormData(form),
                });
                const payload = await response.json().catch(() => null);
                if (!response.ok || !payload?.success) {
                    throw new Error(payload?.message || 'Server error');
                }
                status.classList.add('active');
                status.textContent = payload.message || 'Thank you! Your enquiry has been submitted successfully. Our team will contact you shortly.';
            } catch (error) {
                status.classList.add('active', 'error');
                status.textContent = error.message || 'Unable to submit this request right now. Please try again later or contact us directly.';
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        });
    });
};

window.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initCounters();
    initQuoteForms();
});

const initMobileMenu = () => {
    const menuToggle = document.querySelector('.nav-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileClose = document.querySelector('.mobile-menu__close');
    const servicesToggle = document.querySelector('[data-mobile-services]');
    const servicesSubmenu = document.querySelector('.mobile-menu__submenu');

    menuToggle?.addEventListener('click', () => {
        mobileMenu?.classList.add('open');
        mobileMenu?.setAttribute('aria-hidden', 'false');
        menuToggle.setAttribute('aria-expanded', 'true');
    });

    mobileClose?.addEventListener('click', () => {
        mobileMenu?.classList.remove('open');
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
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            const submitButton = form.querySelector('button[type="submit"]');
            const action = form.getAttribute('action') || '/contact-form-handler.php';
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';
            status.classList.remove('active', 'error');
            status.textContent = '';

            try {
                const response = await fetch(action, {
                    method: 'POST',
                    body: new FormData(form),
                });
                if (!response.ok) {
                    throw new Error('Server error');
                }
                status.classList.add('active');
                status.textContent = 'Thank you. Your message has been submitted successfully. We will contact you soon.';
                form.reset();
            } catch (error) {
                status.classList.add('active', 'error');
                status.textContent = 'Unable to submit this request right now. Please try again later or contact us directly.';
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'REQUEST A QUOTE';
            }
        });
    });
};

window.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initCounters();
    initQuoteForms();
});

const heroSlider = (() => {
    const slides = document.querySelectorAll('.hero__slide');
    const dots = document.querySelectorAll('.hero__dot');
    const prevBtn = document.querySelector('.hero__prev');
    const nextBtn = document.querySelector('.hero__next');
    let activeIndex = 0;
    let interval = null;
    const delay = 6500;

    const setSlide = (index) => {
        if (!slides.length) return;
        slides.forEach((slide, idx) => {
            slide.classList.toggle('active', idx === index);
            slide.setAttribute('aria-hidden', idx !== index);
        });
        dots.forEach((dot, idx) => dot.classList.toggle('active', idx === index));
        activeIndex = index;
    };

    const next = () => setSlide((activeIndex + 1) % slides.length);
    const prev = () => setSlide((activeIndex - 1 + slides.length) % slides.length);
    const start = () => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        interval = setInterval(next, delay);
    };
    const stop = () => clearInterval(interval);

    const init = () => {
        if (!slides.length || !dots.length) return;
        setSlide(0);
        start();

        nextBtn?.addEventListener('click', () => {
            next();
            stop();
            start();
        });
        prevBtn?.addEventListener('click', () => {
            prev();
            stop();
            start();
        });
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                setSlide(index);
                stop();
                start();
            });
        });

        const slider = document.querySelector('.hero__slides');
        if (slider) {
            slider.addEventListener('pointerdown', pointerStart);
            slider.addEventListener('pointerup', pointerEnd);
            slider.addEventListener('pointerleave', pointerCancel);
            slider.addEventListener('pointercancel', pointerCancel);
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowRight') next();
            if (event.key === 'ArrowLeft') prev();
        });
    };

    let startX = 0;
    const pointerStart = (event) => { startX = event.clientX; };
    const pointerEnd = (event) => {
        const distance = event.clientX - startX;
        if (Math.abs(distance) > 50) {
            if (distance < 0) next(); else prev();
            stop(); start();
        }
    };
    const pointerCancel = () => { startX = 0; };

    return { init };
})();

window.addEventListener('DOMContentLoaded', () => heroSlider.init());

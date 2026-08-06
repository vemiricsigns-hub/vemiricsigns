# VEMIRIC SIGNS WEBSITE

A lightweight static website for VEMIRIC SIGNS ADVERTISING L.L.C-FZ, built with HTML, CSS and vanilla JavaScript.

## Folder structure

- `/index.html` — Homepage
- `/about/index.html` — About page
- `/services/index.html` — Services overview page
- `/signage/index.html` — Signage solutions page
- `/digital-printing/index.html` — Digital printing page
- `/branding/index.html` — Branding services page
- `/contact/index.html` — Contact page
- `/privacy-policy/index.html` — Privacy policy placeholder
- `/terms/index.html` — Terms & conditions placeholder
- `/assets/css/style.css` — Shared site styles
- `/assets/css/responsive.css` — Responsive overrides
- `/assets/js/main.js` — Mobile menu, counters, form submission handling
- `/assets/js/slider.js` — Hero slider behavior
- `/assets/images/` — Placeholder image assets

## How to change the logo

1. Replace `/assets/images/logo/logo.svg` with your new logo file (or add `/assets/images/logo/vemiric_signs_advertising_llc.png`).
2. Keep the same filename or update the image source paths in the header and footer across pages to `/assets/images/logo/vemiric_signs_advertising_llc.png`.

## How to replace hero images

1. Replace `/assets/images/hero/hero-signage.webp`, `/assets/images/hero/hero-printing.webp`, `/assets/images/hero/hero-branding.webp`.
2. Use images with a wide aspect ratio (16:9 or wider) and keep the same filenames for easiest swap.

## How to change service images

1. Replace images in `/assets/images/services/` and `/assets/images/projects/`.
2. The markup uses responsive `img` with object-fit cover, so keep dimensions consistent.

## How to change text

1. Open the relevant HTML page and update headings, paragraphs and button text directly.
2. Keep the semantic structure intact: `<h1>`, `<h2>`, `<p>`, `<a>`, etc.

## How to add services

1. Add a new service card in `/services/index.html` or any relevant page.
2. Use the same `.card` structure with image, title, description and link.
3. Add a new navigation link if needed in the header sections.

## How to change phone/email/WhatsApp

Update the contact details in all header and footer sections, plus the `mailto:`, `tel:`, and WhatsApp links.

## How to update forms

1. Forms are currently front-end only and submit to `/contact-form-handler.php`.
2. Update the `action` attribute when connecting to a PHP handler, webhook or CRM endpoint.
3. The JavaScript provides a loading state, success and error messages.

## How to upload to Hostinger

1. Upload the full site folder structure to your Hostinger public HTML directory.
2. Ensure folders remain intact (`about/`, `services/`, `signage/`, etc.).
3. Set the domain root to the folder containing `index.html`.

## Clean URLs

The site uses folder-based URLs like:
- `/about/`
- `/services/`
- `/signage/`

Each page is inside a folder with `index.html` so the URL remains clean.

## Recommended image dimensions

- Hero: 1600x900 or wider
- Service / project cards: 1200x900
- About / gallery images: 1200x900

## Notes for deployment

- Update `example.com` to your actual domain in metadata, `robots.txt` and `sitemap.xml`.
- Add a live Google Map embed to `contact/index.html` if required.
- Replace placeholder text and testimonials with real content before launch.

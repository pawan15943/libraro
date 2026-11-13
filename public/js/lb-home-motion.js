/* Libraro home: Motion (vanilla Framer Motion) effects. Purely decorative: if this file or the
   CDN fails to load, the page is complete without it. */
import { animate, scroll, inView } from 'https://cdn.jsdelivr.net/npm/motion@11.11.13/+esm';

const doc = document;
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const ease = [0.2, 0.8, 0.2, 1];

if (!reduce) {
    /* Hero: headline lines blur in, then lead + buttons */
    const h1 = doc.querySelector('.lh-hero-center h1');
    if (h1) {
        animate(h1, { opacity: [0, 1], filter: ['blur(14px)', 'blur(0px)'], transform: ['translateY(18px)', 'translateY(0px)'] }, { duration: 1, ease });
        const rest = doc.querySelectorAll('.lh-hero-center .lh-chip, .lh-hero-center .lh-hero-lead, .lh-hero-center .lh-hero-actions, .lh-hero-proof');
        rest.forEach((el, i) => animate(el, { opacity: [0, 1], transform: ['translateY(14px)', 'translateY(0px)'] }, { duration: .8, delay: .15 + i * .08, ease }));
    }

    /* Hero laptop: rises and settles with a spring */
    const mac = doc.querySelector('.lh-mac--hero');
    if (mac) {
        const a = animate(mac, { transform: ['rotateX(22deg) translateY(40px) scale(.96)', 'rotateX(10deg) translateY(0px) scale(1)'] }, { type: 'spring', stiffness: 60, damping: 16, delay: .25 });
        a.finished.then(() => { mac.style.transform = ''; mac.style.animation = 'none'; });
    }

    /* Big words: parallax now runs in CSS (lb-home.css v11) */

    /* Background photos: gentle zoom while the section passes */
    doc.querySelectorAll('.lh-bgimg').forEach((bg) => {
        scroll(animate(bg, { transform: ['scale(1.12)', 'scale(1)'] }, { ease: 'linear' }), { target: bg.parentElement, offset: ['start end', 'end start'] });
    });

    /* Number ticker for the hero stats */
    inView('.lh-hero-stats', (el) => {
        el.querySelectorAll('dt').forEach((dt) => {
            const m = dt.textContent.trim().match(/^(\d+)(.*)$/);
            if (!m) return;
            const end = parseInt(m[1], 10), suffix = m[2];
            animate(0, end, { duration: 1.4, ease, onUpdate: (v) => { dt.textContent = Math.round(v) + suffix; } });
        });
    }, { amount: 0.6 });

    /* Magnetic primary buttons */
    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        doc.querySelectorAll('.lh-btn-main, .lh-final-actions .lb-btn--accent, .lh-refund-actions .lb-btn').forEach((btn) => {
            btn.addEventListener('pointermove', (e) => {
                const r = btn.getBoundingClientRect();
                const x = (e.clientX - r.left - r.width / 2) * 0.22, y = (e.clientY - r.top - r.height / 2) * 0.3;
                animate(btn, { transform: `translate(${x}px, ${y}px)` }, { type: 'spring', stiffness: 300, damping: 20 });
            });
            btn.addEventListener('pointerleave', () => animate(btn, { transform: 'translate(0px, 0px)' }, { type: 'spring', stiffness: 260, damping: 18 }));
        });
    }
}

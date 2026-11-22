/*
 * The page builder's ready-made blocks — what Puck calls its component list.
 *
 * Every block carries its own CSS under a `cw-` prefix, so it looks right the
 * moment it is dropped and never leans on the theme's stylesheet (page content
 * is stored as plain HTML + CSS, see builders). Colours are plain values the
 * Style Manager can change element by element.
 */

const svg = (d) => `<svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`;

const icon = {
    layout: svg('<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 10v10"/>'),
    cols2: svg('<rect x="3" y="5" width="8" height="14" rx="1.5"/><rect x="13" y="5" width="8" height="14" rx="1.5"/>'),
    cols3: svg('<rect x="2.5" y="5" width="5.5" height="14" rx="1.2"/><rect x="9.2" y="5" width="5.5" height="14" rx="1.2"/><rect x="15.9" y="5" width="5.5" height="14" rx="1.2"/>'),
    cols4: svg('<rect x="2" y="5" width="4" height="14" rx="1"/><rect x="7.4" y="5" width="4" height="14" rx="1"/><rect x="12.8" y="5" width="4" height="14" rx="1"/><rect x="18.2" y="5" width="3.8" height="14" rx="1"/>'),
    text: svg('<path d="M4 6h16M4 12h16M4 18h10"/>'),
    heading: svg('<path d="M6 5v14M18 5v14M6 12h12"/>'),
    button: svg('<rect x="3" y="8" width="18" height="8" rx="4"/><path d="M9 12h6"/>'),
    image: svg('<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-9 9"/>'),
    hero: svg('<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 10h10M9 14h6"/>'),
    grid: svg('<rect x="3" y="3" width="7" height="7" rx="1.2"/><rect x="14" y="3" width="7" height="7" rx="1.2"/><rect x="3" y="14" width="7" height="7" rx="1.2"/><rect x="14" y="14" width="7" height="7" rx="1.2"/>'),
    star: svg('<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/>'),
    quote: svg('<path d="M9 7H5v6h4v4H5M19 7h-4v6h4v4h-4"/>'),
    list: svg('<path d="M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01"/>'),
    mail: svg('<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>'),
    dollar: svg('<path d="M12 3v18M16 7.5C15 6.5 13.7 6 12 6c-2.2 0-4 1-4 3s1.8 2.6 4 3 4 1 4 3-1.8 3-4 3c-1.7 0-3-.5-4-1.5"/>'),
    help: svg('<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.7.3-1 .9-1 1.7M12 17h.01"/>'),
    footer: svg('<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 15h18M7 18h4"/>'),
    nav: svg('<rect x="3" y="5" width="18" height="6" rx="2"/><path d="M7 8h2M12 8h2"/>'),
    minus: svg('<path d="M4 12h16"/>'),
    space: svg('<path d="M12 4v16M8 8l4-4 4 4M8 16l4 4 4-4"/>'),
    users: svg('<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M16 5.5a3 3 0 0 1 0 5M18 14.5c2 .7 3 2.600 3 5.500"/>'),
    chart: svg('<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>'),
    gallery: svg('<rect x="3" y="3" width="8" height="10" rx="1.2"/><rect x="13" y="3" width="8" height="6" rx="1.2"/><rect x="13" y="11" width="8" height="10" rx="1.2"/><rect x="3" y="15" width="8" height="6" rx="1.2"/>'),
    megaphone: svg('<path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1ZM15 9a4 4 0 0 1 0 6"/>'),
};

const IMG = 'https://placehold.co';

// [id, label, category, icon, html + css]
const blocks = [
    // ───────────────────────────── Layout
    ['cw-container', 'Container', 'Layout', icon.layout, `
<div class="cw-container" data-gjs-droppable="true"><p>Drop elements here</p></div>
<style>.cw-container{max-width:1140px;margin:0 auto;padding:32px 20px;min-height:60px}</style>`],

    ['cw-cols-2', '2 columns', 'Layout', icon.cols2, `
<div class="cw-row"><div class="cw-col"><p>Column one</p></div><div class="cw-col"><p>Column two</p></div></div>
<style>.cw-row{display:flex;flex-wrap:wrap;gap:24px;max-width:1140px;margin:0 auto;padding:24px 20px}.cw-col{flex:1 1 280px;min-height:60px}</style>`],

    ['cw-cols-3', '3 columns', 'Layout', icon.cols3, `
<div class="cw-row"><div class="cw-col"><p>One</p></div><div class="cw-col"><p>Two</p></div><div class="cw-col"><p>Three</p></div></div>
<style>.cw-row{display:flex;flex-wrap:wrap;gap:24px;max-width:1140px;margin:0 auto;padding:24px 20px}.cw-col{flex:1 1 220px;min-height:60px}</style>`],

    ['cw-cols-4', '4 columns', 'Layout', icon.cols4, `
<div class="cw-row"><div class="cw-col"><p>One</p></div><div class="cw-col"><p>Two</p></div><div class="cw-col"><p>Three</p></div><div class="cw-col"><p>Four</p></div></div>
<style>.cw-row{display:flex;flex-wrap:wrap;gap:24px;max-width:1140px;margin:0 auto;padding:24px 20px}.cw-col{flex:1 1 180px;min-height:60px}</style>`],

    ['cw-split', 'Image + text', 'Layout', icon.cols2, `
<section class="cw-split"><div class="cw-split-media"><img src="${IMG}/640x440" alt=""></div><div class="cw-split-body"><h2>Section heading</h2><p>Explain what you offer in a couple of sentences. Click any text to edit it, and use the style panel to change spacing, colours and fonts.</p><a class="cw-btn" href="#">Learn more</a></div></section>
<style>.cw-split{display:flex;flex-wrap:wrap;align-items:center;gap:48px;max-width:1140px;margin:0 auto;padding:64px 20px}.cw-split-media{flex:1 1 320px}.cw-split-media img{width:100%;border-radius:16px;display:block}.cw-split-body{flex:1 1 320px}.cw-split-body h2{font-size:2rem;line-height:1.2;margin:0 0 16px}.cw-split-body p{color:#475569;line-height:1.7;margin:0 0 24px}.cw-btn{display:inline-block;background:#2563eb;color:#fff;padding:12px 26px;border-radius:8px;text-decoration:none;font-weight:600}</style>`],

    ['cw-spacer', 'Spacer', 'Layout', icon.space, `
<div class="cw-spacer"></div>
<style>.cw-spacer{height:64px}</style>`],

    ['cw-divider', 'Divider', 'Layout', icon.minus, `
<hr class="cw-divider">
<style>.cw-divider{border:0;border-top:1px solid #e2e8f0;margin:32px auto;max-width:1140px}</style>`],

    // ───────────────────────────── Content
    ['cw-heading', 'Heading', 'Content', icon.heading, `<h2 class="cw-h">Your heading</h2><style>.cw-h{font-size:2.25rem;line-height:1.2;font-weight:700;margin:0 0 16px}</style>`],
    ['cw-paragraph', 'Paragraph', 'Content', icon.text, `<p class="cw-p">Write something here. Double-click to edit the text, select part of it for bold, italic and links.</p><style>.cw-p{font-size:1.05rem;line-height:1.75;color:#475569;margin:0 0 16px}</style>`],
    ['cw-button', 'Button', 'Content', icon.button, `<a class="cw-btn" href="#">Click me</a><style>.cw-btn{display:inline-block;background:#2563eb;color:#fff;padding:12px 26px;border-radius:8px;text-decoration:none;font-weight:600}</style>`],
    ['cw-btn-outline', 'Outline button', 'Content', icon.button, `<a class="cw-btn-o" href="#">Click me</a><style>.cw-btn-o{display:inline-block;border:2px solid #2563eb;color:#2563eb;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:600}</style>`],
    ['cw-image', 'Image', 'Content', icon.image, { type: 'image', style: { 'max-width': '100%', display: 'block' }, attributes: { src: `${IMG}/960x540`, alt: '' }, activeOnRender: 1 }],
    ['cw-list', 'List', 'Content', icon.list, `<ul class="cw-list"><li>First point</li><li>Second point</li><li>Third point</li></ul><style>.cw-list{line-height:2;color:#475569;padding-left:22px;margin:0 0 16px}</style>`],
    ['cw-quote', 'Quote', 'Content', icon.quote, `<blockquote class="cw-quote"><p>“A short, memorable quote goes here.”</p><cite>— Name, Company</cite></blockquote><style>.cw-quote{border-left:4px solid #2563eb;margin:24px 0;padding:8px 0 8px 24px;font-size:1.25rem;color:#0f172a}.cw-quote p{margin:0 0 8px}.cw-quote cite{font-size:.9rem;color:#64748b;font-style:normal}</style>`],
    ['cw-iconbox', 'Icon box', 'Content', icon.star, `<div class="cw-ib"><div class="cw-ib-i">★</div><h3>Feature title</h3><p>Short description of the feature or benefit.</p></div><style>.cw-ib{padding:24px;border:1px solid #e2e8f0;border-radius:14px;text-align:center}.cw-ib-i{width:56px;height:56px;margin:0 auto 16px;border-radius:14px;background:#dbeafe;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:24px}.cw-ib h3{margin:0 0 8px;font-size:1.15rem}.cw-ib p{margin:0;color:#64748b;line-height:1.6}</style>`],

    // ───────────────────────────── Sections
    ['cw-hero', 'Hero', 'Sections', icon.hero, `
<section class="cw-hero"><div class="cw-hero-in"><span class="cw-hero-tag">New</span><h1>Build something people love</h1><p>A short supporting line that explains your product or service in plain words.</p><div class="cw-hero-btns"><a class="cw-btn" href="#">Get started</a><a class="cw-btn-o" href="#">Learn more</a></div></div></section>
<style>.cw-hero{background:linear-gradient(135deg,#eff6ff,#e0e7ff);padding:96px 20px;text-align:center}.cw-hero-in{max-width:760px;margin:0 auto}.cw-hero-tag{display:inline-block;background:#fff;color:#2563eb;border-radius:999px;padding:4px 14px;font-size:.85rem;font-weight:600;margin-bottom:20px}.cw-hero h1{font-size:3.2rem;line-height:1.1;font-weight:800;margin:0 0 20px;color:#0f172a}.cw-hero p{font-size:1.2rem;line-height:1.6;color:#475569;margin:0 0 32px}.cw-hero-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}.cw-btn{display:inline-block;background:#2563eb;color:#fff;padding:13px 28px;border-radius:8px;text-decoration:none;font-weight:600}.cw-btn-o{display:inline-block;border:2px solid #2563eb;color:#2563eb;padding:11px 26px;border-radius:8px;text-decoration:none;font-weight:600}@media(max-width:768px){.cw-hero h1{font-size:2.2rem}}</style>`],

    ['cw-hero-image', 'Hero with image', 'Sections', icon.hero, `
<section class="cw-hi"><div class="cw-hi-in"><div class="cw-hi-text"><h1>A headline that says it all</h1><p>Support it with one or two lines that tell visitors what to expect and why it matters.</p><a class="cw-btn" href="#">Get started</a></div><div class="cw-hi-media"><img src="${IMG}/720x520" alt=""></div></div></section>
<style>.cw-hi{padding:80px 20px;background:#fff}.cw-hi-in{max-width:1140px;margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;gap:48px}.cw-hi-text{flex:1 1 360px}.cw-hi-text h1{font-size:3rem;line-height:1.1;font-weight:800;margin:0 0 20px;color:#0f172a}.cw-hi-text p{font-size:1.15rem;line-height:1.7;color:#475569;margin:0 0 28px}.cw-hi-media{flex:1 1 360px}.cw-hi-media img{width:100%;border-radius:20px;display:block}.cw-btn{display:inline-block;background:#2563eb;color:#fff;padding:13px 28px;border-radius:8px;text-decoration:none;font-weight:600}@media(max-width:768px){.cw-hi-text h1{font-size:2.2rem}}</style>`],

    ['cw-features', 'Features (3)', 'Sections', icon.grid, `
<section class="cw-feat"><div class="cw-feat-head"><h2>Why choose us</h2><p>Three reasons customers stay with us.</p></div><div class="cw-feat-grid"><div class="cw-feat-card"><div class="cw-feat-i">⚡</div><h3>Fast</h3><p>Describe the first benefit in a sentence or two.</p></div><div class="cw-feat-card"><div class="cw-feat-i">🔒</div><h3>Secure</h3><p>Describe the second benefit in a sentence or two.</p></div><div class="cw-feat-card"><div class="cw-feat-i">💬</div><h3>Supported</h3><p>Describe the third benefit in a sentence or two.</p></div></div></section>
<style>.cw-feat{padding:72px 20px;max-width:1140px;margin:0 auto}.cw-feat-head{text-align:center;margin-bottom:48px}.cw-feat-head h2{font-size:2.2rem;margin:0 0 10px}.cw-feat-head p{color:#64748b;margin:0}.cw-feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}.cw-feat-card{padding:28px;border:1px solid #e2e8f0;border-radius:16px;background:#fff}.cw-feat-i{width:52px;height:52px;border-radius:14px;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:16px}.cw-feat-card h3{margin:0 0 8px;font-size:1.2rem}.cw-feat-card p{margin:0;color:#64748b;line-height:1.6}@media(max-width:768px){.cw-feat-grid{grid-template-columns:1fr}}</style>`],

    ['cw-stats', 'Stats', 'Sections', icon.chart, `
<section class="cw-stats"><div class="cw-stats-in"><div><strong>10k+</strong><span>Customers</span></div><div><strong>99%</strong><span>Satisfaction</span></div><div><strong>24/7</strong><span>Support</span></div><div><strong>15</strong><span>Years</span></div></div></section>
<style>.cw-stats{background:#0f172a;color:#fff;padding:56px 20px}.cw-stats-in{max-width:1140px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);gap:24px;text-align:center}.cw-stats strong{display:block;font-size:2.6rem;font-weight:800;color:#60a5fa}.cw-stats span{color:#cbd5e1}@media(max-width:768px){.cw-stats-in{grid-template-columns:repeat(2,1fr)}}</style>`],

    ['cw-cta', 'Call to action', 'Sections', icon.megaphone, `
<section class="cw-cta"><div class="cw-cta-in"><h2>Ready to get started?</h2><p>Join today and see the difference for yourself.</p><a class="cw-cta-btn" href="#">Contact us</a></div></section>
<style>.cw-cta{padding:72px 20px}.cw-cta-in{max-width:960px;margin:0 auto;background:linear-gradient(135deg,#2563eb,#4f46e5);color:#fff;border-radius:24px;padding:56px 32px;text-align:center}.cw-cta h2{font-size:2.2rem;margin:0 0 12px}.cw-cta p{margin:0 0 28px;opacity:.9;font-size:1.1rem}.cw-cta-btn{display:inline-block;background:#fff;color:#2563eb;padding:13px 30px;border-radius:8px;text-decoration:none;font-weight:700}</style>`],

    ['cw-pricing', 'Pricing', 'Sections', icon.dollar, `
<section class="cw-pr"><div class="cw-pr-head"><h2>Simple pricing</h2><p>Pick the plan that fits you.</p></div><div class="cw-pr-grid">
<div class="cw-pr-card"><h3>Starter</h3><div class="cw-pr-price">$9<small>/mo</small></div><ul><li>1 project</li><li>Basic support</li><li>Core features</li></ul><a class="cw-pr-btn" href="#">Choose</a></div>
<div class="cw-pr-card cw-pr-hot"><h3>Pro</h3><div class="cw-pr-price">$29<small>/mo</small></div><ul><li>10 projects</li><li>Priority support</li><li>All features</li></ul><a class="cw-pr-btn" href="#">Choose</a></div>
<div class="cw-pr-card"><h3>Business</h3><div class="cw-pr-price">$79<small>/mo</small></div><ul><li>Unlimited projects</li><li>Dedicated support</li><li>Custom options</li></ul><a class="cw-pr-btn" href="#">Choose</a></div></div></section>
<style>.cw-pr{padding:72px 20px;max-width:1140px;margin:0 auto}.cw-pr-head{text-align:center;margin-bottom:48px}.cw-pr-head h2{font-size:2.2rem;margin:0 0 10px}.cw-pr-head p{color:#64748b;margin:0}.cw-pr-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;align-items:stretch}.cw-pr-card{border:1px solid #e2e8f0;border-radius:18px;padding:32px;background:#fff;text-align:center}.cw-pr-hot{border:2px solid #2563eb;box-shadow:0 20px 40px -20px rgba(37,99,235,.4)}.cw-pr-card h3{margin:0 0 12px}.cw-pr-price{font-size:2.8rem;font-weight:800;margin-bottom:20px}.cw-pr-price small{font-size:1rem;color:#64748b;font-weight:500}.cw-pr-card ul{list-style:none;padding:0;margin:0 0 24px;line-height:2.1;color:#475569}.cw-pr-btn{display:block;background:#2563eb;color:#fff;padding:12px;border-radius:8px;text-decoration:none;font-weight:600}@media(max-width:768px){.cw-pr-grid{grid-template-columns:1fr}}</style>`],

    ['cw-testimonials', 'Testimonials', 'Sections', icon.quote, `
<section class="cw-tm"><h2>What people say</h2><div class="cw-tm-grid">
<figure class="cw-tm-card"><blockquote>“Great service from start to finish. Highly recommended.”</blockquote><figcaption><img src="${IMG}/80x80" alt=""><span><b>Customer name</b><small>Company</small></span></figcaption></figure>
<figure class="cw-tm-card"><blockquote>“They delivered exactly what we needed, right on time.”</blockquote><figcaption><img src="${IMG}/80x80" alt=""><span><b>Customer name</b><small>Company</small></span></figcaption></figure>
<figure class="cw-tm-card"><blockquote>“Professional, friendly and easy to work with.”</blockquote><figcaption><img src="${IMG}/80x80" alt=""><span><b>Customer name</b><small>Company</small></span></figcaption></figure></div></section>
<style>.cw-tm{padding:72px 20px;max-width:1140px;margin:0 auto}.cw-tm h2{text-align:center;font-size:2.2rem;margin:0 0 40px}.cw-tm-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}.cw-tm-card{margin:0;padding:28px;border-radius:16px;background:#f8fafc}.cw-tm-card blockquote{margin:0 0 20px;line-height:1.7;color:#334155}.cw-tm-card figcaption{display:flex;align-items:center;gap:12px}.cw-tm-card img{width:44px;height:44px;border-radius:50%}.cw-tm-card b{display:block}.cw-tm-card small{color:#64748b}@media(max-width:768px){.cw-tm-grid{grid-template-columns:1fr}}</style>`],

    ['cw-faq', 'FAQ', 'Sections', icon.help, `
<section class="cw-faq"><h2>Frequently asked questions</h2>
<details open><summary>How does it work?</summary><p>Answer the question here in a few clear sentences.</p></details>
<details><summary>How long does it take?</summary><p>Answer the question here in a few clear sentences.</p></details>
<details><summary>Can I cancel anytime?</summary><p>Answer the question here in a few clear sentences.</p></details></section>
<style>.cw-faq{max-width:820px;margin:0 auto;padding:72px 20px}.cw-faq h2{text-align:center;font-size:2.2rem;margin:0 0 36px}.cw-faq details{border-bottom:1px solid #e2e8f0;padding:18px 0}.cw-faq summary{cursor:pointer;font-weight:600;font-size:1.1rem}.cw-faq p{margin:12px 0 0;color:#64748b;line-height:1.7}</style>`],

    ['cw-team', 'Team', 'Sections', icon.users, `
<section class="cw-team"><h2>Meet the team</h2><div class="cw-team-grid">
<div class="cw-team-m"><img src="${IMG}/240x240" alt=""><h3>Full Name</h3><p>Role</p></div>
<div class="cw-team-m"><img src="${IMG}/240x240" alt=""><h3>Full Name</h3><p>Role</p></div>
<div class="cw-team-m"><img src="${IMG}/240x240" alt=""><h3>Full Name</h3><p>Role</p></div>
<div class="cw-team-m"><img src="${IMG}/240x240" alt=""><h3>Full Name</h3><p>Role</p></div></div></section>
<style>.cw-team{padding:72px 20px;max-width:1140px;margin:0 auto}.cw-team h2{text-align:center;font-size:2.2rem;margin:0 0 40px}.cw-team-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;text-align:center}.cw-team-m img{width:100%;border-radius:16px;display:block;margin-bottom:14px}.cw-team-m h3{margin:0 0 4px;font-size:1.1rem}.cw-team-m p{margin:0;color:#64748b}@media(max-width:768px){.cw-team-grid{grid-template-columns:repeat(2,1fr)}}</style>`],

    ['cw-gallery', 'Gallery', 'Sections', icon.gallery, `
<section class="cw-gal"><div class="cw-gal-grid"><img src="${IMG}/600x400" alt=""><img src="${IMG}/600x400" alt=""><img src="${IMG}/600x400" alt=""><img src="${IMG}/600x400" alt=""><img src="${IMG}/600x400" alt=""><img src="${IMG}/600x400" alt=""></div></section>
<style>.cw-gal{padding:48px 20px;max-width:1140px;margin:0 auto}.cw-gal-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.cw-gal-grid img{width:100%;border-radius:12px;display:block}@media(max-width:768px){.cw-gal-grid{grid-template-columns:repeat(2,1fr)}}</style>`],

    ['cw-logos', 'Logo cloud', 'Sections', icon.grid, `
<section class="cw-logos"><p>Trusted by teams everywhere</p><div class="cw-logos-row"><img src="${IMG}/140x48?text=Logo" alt=""><img src="${IMG}/140x48?text=Logo" alt=""><img src="${IMG}/140x48?text=Logo" alt=""><img src="${IMG}/140x48?text=Logo" alt=""><img src="${IMG}/140x48?text=Logo" alt=""></div></section>
<style>.cw-logos{padding:48px 20px;text-align:center}.cw-logos p{color:#64748b;margin:0 0 24px}.cw-logos-row{display:flex;flex-wrap:wrap;gap:40px;justify-content:center;align-items:center;opacity:.7}</style>`],

    ['cw-contact', 'Contact form', 'Sections', icon.mail, `
<section class="cw-ct"><div class="cw-ct-in"><h2>Get in touch</h2><p>We usually reply within one business day.</p><form class="cw-ct-form" method="post" action="#"><input type="text" name="name" placeholder="Your name"><input type="email" name="email" placeholder="Email address"><textarea name="message" rows="5" placeholder="How can we help?"></textarea><button type="submit">Send message</button></form></div></section>
<style>.cw-ct{padding:72px 20px;background:#f8fafc}.cw-ct-in{max-width:620px;margin:0 auto;text-align:center}.cw-ct h2{font-size:2.2rem;margin:0 0 10px}.cw-ct p{color:#64748b;margin:0 0 28px}.cw-ct-form{display:grid;gap:14px;text-align:left}.cw-ct-form input,.cw-ct-form textarea{width:100%;padding:13px 14px;border:1px solid #cbd5e1;border-radius:8px;font:inherit;background:#fff}.cw-ct-form button{background:#2563eb;color:#fff;border:0;padding:14px;border-radius:8px;font-weight:600;cursor:pointer;font:inherit}</style>`],

    ['cw-newsletter', 'Newsletter', 'Sections', icon.mail, `
<section class="cw-nl"><div class="cw-nl-in"><div><h3>Stay in the loop</h3><p>News and updates, no spam.</p></div><form class="cw-nl-form" method="post" action="#"><input type="email" name="email" placeholder="Your email"><button type="submit">Subscribe</button></form></div></section>
<style>.cw-nl{padding:48px 20px;background:#eff6ff}.cw-nl-in{max-width:1000px;margin:0 auto;display:flex;flex-wrap:wrap;gap:24px;align-items:center;justify-content:space-between}.cw-nl h3{margin:0 0 4px;font-size:1.5rem}.cw-nl p{margin:0;color:#475569}.cw-nl-form{display:flex;gap:10px;flex:1 1 320px;max-width:440px}.cw-nl-form input{flex:1;padding:12px 14px;border:1px solid #cbd5e1;border-radius:8px;font:inherit}.cw-nl-form button{background:#2563eb;color:#fff;border:0;padding:12px 22px;border-radius:8px;font-weight:600;cursor:pointer;font:inherit}</style>`],

    ['cw-header', 'Header / menu', 'Sections', icon.nav, `
<header class="cw-hd"><div class="cw-hd-in"><a class="cw-hd-logo" href="#">Brand</a><nav class="cw-hd-nav"><a href="#">Home</a><a href="#">About</a><a href="#">Services</a><a href="#">Contact</a></nav><a class="cw-btn" href="#">Get started</a></div></header>
<style>.cw-hd{border-bottom:1px solid #e2e8f0;background:#fff}.cw-hd-in{max-width:1140px;margin:0 auto;padding:16px 20px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px}.cw-hd-logo{font-weight:800;font-size:1.3rem;color:#0f172a;text-decoration:none}.cw-hd-nav{display:flex;gap:24px;flex-wrap:wrap}.cw-hd-nav a{color:#475569;text-decoration:none}.cw-btn{display:inline-block;background:#2563eb;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-weight:600}</style>`],

    ['cw-footer', 'Footer', 'Sections', icon.footer, `
<footer class="cw-ft"><div class="cw-ft-in"><div class="cw-ft-brand"><strong>Brand</strong><p>A short line about your company.</p></div><div><h4>Company</h4><a href="#">About</a><a href="#">Careers</a><a href="#">Contact</a></div><div><h4>Support</h4><a href="#">Help centre</a><a href="#">Terms</a><a href="#">Privacy</a></div></div><div class="cw-ft-bar">© Your company. All rights reserved.</div></footer>
<style>.cw-ft{background:#0f172a;color:#cbd5e1;padding:56px 20px 24px}.cw-ft-in{max-width:1140px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr;gap:32px}.cw-ft strong{color:#fff;font-size:1.3rem}.cw-ft p{margin:10px 0 0;line-height:1.6}.cw-ft h4{color:#fff;margin:0 0 14px}.cw-ft a{display:block;color:#cbd5e1;text-decoration:none;margin-bottom:8px}.cw-ft-bar{max-width:1140px;margin:40px auto 0;padding-top:20px;border-top:1px solid #1e293b;font-size:.9rem;color:#94a3b8}@media(max-width:768px){.cw-ft-in{grid-template-columns:1fr}}</style>`],
];

// One line under each block's name in the Components list.
const desc = {
    'cw-container': 'Centred wrapper for any content',
    'cw-cols-2': 'Two side-by-side columns',
    'cw-cols-3': 'Three equal columns',
    'cw-cols-4': 'Four equal columns',
    'cw-split': 'Picture beside a heading and text',
    'cw-spacer': 'Empty vertical space',
    'cw-divider': 'Thin horizontal line',
    'cw-heading': 'A section title',
    'cw-paragraph': 'A block of body text',
    'cw-button': 'Solid call-to-action button',
    'cw-btn-outline': 'Outlined secondary button',
    'cw-image': 'A picture from your library',
    'cw-list': 'Bulleted list of points',
    'cw-quote': 'Highlighted quotation',
    'cw-iconbox': 'Icon, title and short text',
    'cw-hero': 'Big headline with two buttons',
    'cw-hero-image': 'Headline beside a large image',
    'cw-features': 'Three benefit cards in a row',
    'cw-stats': 'Key numbers on a dark band',
    'cw-cta': 'Call-to-action band with button',
    'cw-pricing': 'Three plans side by side',
    'cw-testimonials': 'Customer quotes with photos',
    'cw-faq': 'Questions that open and close',
    'cw-team': 'People with photo and role',
    'cw-gallery': 'Grid of photos',
    'cw-logos': 'Row of partner logos',
    'cw-contact': 'Name, email and message form',
    'cw-newsletter': 'Email sign-up strip',
    'cw-header': 'Logo, menu and button',
    'cw-footer': 'Links and copyright',
};

const esc = (t) => String(t).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

/** The label GrapesJS prints for a block: name over a one-line description (styled in the builder view). */
export const blockLabel = (name, description = '') => `<b>${esc(name)}</b><small>${esc(description)}</small>`;

/*
 * Dynamic blocks: saved as an empty `<div data-cw="…" data-…>` placeholder and filled with
 * live data on the server each time the page is shown. Nothing is hard-coded here — every
 * block is a Blade component (App\View\Components\Builder\*) that describes itself
 * (name, icon, settings), and the editor builds its component type, its settings panel and
 * its list entry from that description (App\Support\Builder\DynamicBlocks::definitions()).
 * Add a component class on the server and it appears here by itself.
 */
export function registerDynamicBlocks(editor, { dynamicBlocks = [] }) {
    const traitOf = (f) => {
        const base = { name: `data-${f.name}`, label: f.label, changeProp: false };

        if (f.type === 'select') return { ...base, type: 'select', options: Object.entries(f.options || {}).map(([id, name]) => ({ id, name })) };
        if (f.type === 'checkbox') return { ...base, type: 'checkbox', valueTrue: 'true', valueFalse: 'false' };
        if (f.type === 'number') return { ...base, type: 'number', min: f.min, max: f.max };

        return { ...base, type: f.type === 'textarea' ? 'text' : 'text', placeholder: f.placeholder || '' };
    };

    const attrsOf = (d) => Object.fromEntries(Object.entries(d.defaults || {}).map(([k, v]) => [`data-${k}`, v]));

    dynamicBlocks.forEach((d) => {
        const type = `cw-${d.key}`;
        const shape = d.preview?.shape || 'card';

        editor.Components.addType(type, {
            isComponent: (el) => (el.getAttribute && el.getAttribute('data-cw') === d.key ? { type } : false),
            model: {
                defaults: {
                    tagName: 'div',
                    name: d.name,
                    droppable: false,
                    attributes: { 'data-cw': d.key, ...attrsOf(d) },
                    traits: d.fields.map(traitOf),
                },
            },
            view: {
                // Preview only — never exported (GrapesJS saves the model, not what the view draws).
                onRender({ el }) {
                    const at = this.model.getAttributes();
                    const n = d.preview?.count ?? Math.min(parseInt(at['data-limit']) || 4, shape === 'logo' ? 6 : 8);
                    const cols = d.preview?.columns === false ? n : (at['data-columns'] || 3);
                    const media = shape === 'logo' ? 'height:28px' : (shape === 'square' ? 'aspect-ratio:1' : 'aspect-ratio:16/10');
                    const heading = at['data-heading'] ? `<div style="grid-column:1/-1;text-align:center;font:700 22px system-ui;color:#0f172a">${esc(at['data-heading'])}</div>` : '';

                    el.style.cssText = `display:grid;grid-template-columns:repeat(${cols},1fr);gap:16px;padding:24px;min-height:80px`;
                    el.innerHTML = heading + Array.from({ length: n }, () => `<div style="border:1px dashed #94a3b8;border-radius:12px;padding:12px;text-align:center;color:#64748b;font:13px system-ui"><div style="${media};background:#e2e8f0;border-radius:8px;margin-bottom:8px"></div>${esc(d.preview?.sample || d.name)}</div>`).join('');
                },
            },
        });

        editor.Blocks.add(type, {
            label: blockLabel(d.name, d.hint),
            category: d.category || 'Dynamic',
            media: icon[d.icon] ?? icon.grid,
            content: { type },
            select: true,
        });
    });

    const dynamicTypes = dynamicBlocks.map((d) => `cw-${d.key}`);
    editor.on('component:update:attributes', (m) => { if (dynamicTypes.includes(m.get('type'))) m.view?.render(); });
}

export function registerBlocks(editor, options = {}) {
    const bm = editor.Blocks;
    registerDynamicBlocks(editor, options);

    blocks.forEach(([id, label, category, media, content]) => {
        bm.add(id, {
            label: blockLabel(label, desc[id]),
            category,
            media,
            content: typeof content === 'string' ? content.trim() : content,
            select: true,
        });
    });
}

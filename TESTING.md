# Staging test checklist

Do all of this on a staging copy or a local test site, never first on the live site. Take a backup first.

## 1. Install and activate
- [ ] Plugin installs without an error (zip upload or Git Updater).
- [ ] Activation shows no warning or white screen.
- [ ] Pages > All Pages shows about 40 new DRAFT pages: Home, About, Methodology, Privacy Policy, Disclaimer, Contact, 4 silo parents, 30 tool pages.
- [ ] Tool pages appear as children of Calculators, GEO and AI Visibility, Tool Comparisons and No-Code AI Workflows.
- [ ] Settings > Permalinks is set to "Post name", so URLs look like /calculators/make-vs-zapier-pricing-calculator/.
- [ ] WordPress creates its own draft "Privacy Policy" page. If it already existed, the installer skipped it, so paste privacy-policy text from /pages/privacy-policy.html into it by hand.
- [ ] Deactivate and reactivate: no duplicate pages are created, and draft pages refresh from /content while any published page stays unchanged.

## 2. Tool pages (preview each type, not all 30)
- [ ] Trust bar shows the date, with no broken layout.
- [ ] Calculator renders; moving sliders changes the bars and the verdict; typing in the number box works.
- [ ] No result shows NaN, undefined or a blank verdict.
- [ ] Homepage breakeven calculator: words slider, context buttons and seats all update the result.
- [ ] Directory: search, category buttons and "no match" message work; category pages start on the right filter.
- [ ] Prompt box: Copy button copies the text; quotes in the prompt stay straight, not curly.
- [ ] FAQ opens and closes. View page source: one FAQPage JSON-LD block per page.
- [ ] Test the llms.txt generator on its page.
- [ ] Click Copy Result on a calculator and paste: you should get the title, inputs, each cost, the verdict line and the page address. The button should read Copied! for about 2 seconds.
- [ ] Paste one tool page into Google's Rich Results Test or the Schema.org validator: the WebApplication block should parse with no errors.
- [ ] The Related tools box appears above the FAQ and its three links open the right pages (they work once those pages are published).
- [ ] Pricing tables scroll sideways inside their own box on a phone, with no page-wide horizontal scroll.
- [ ] Check on a phone: nothing scrolls sideways; buttons are tappable.
- [ ] Check with your real theme: fonts, spacing and the two-column layout in a narrow content column.

- [ ] View source of one tool page: there is one meta description, og:title and og:description. With Yoast SEO or Rank Math active, the plugin prints none of them.
- [ ] Open /llms.txt: it loads as plain text (not a download, not a 404) with four sections and 30 links. Check the response type is text/plain.
- [ ] On a phone or a 768 px-wide browser window, change a calculator: the bottom bar shows the best option and cost, the affiliate button appears only if one is set, and the x dismisses it.
- [ ] Open the site normally after activating: no stray text appears above the page header.

- [ ] On a tool page, a line reading Home / silo name / page title appears above the trust bar and each part links to the right page. View source: one BreadcrumbList block. With a SEO plugin that also outputs breadcrumbs, check Search Console shows no duplicate-breadcrumb warning.
- [ ] On a phone, load a calculator page: no bottom bar appears at first. It appears after you move a slider or scroll about a third of a screen down.
- [ ] With All in One SEO, SEOPress or The SEO Framework active, view source: this plugin prints no meta description or og tags.

## 3. Settings page (Settings > Lab Settings)
- [ ] Change the verified date; reload a tool page; the trust bar shows the new date.
- [ ] Edit one calculator's rate; the page shows the new result.
- [ ] Add an affiliate link; the button appears and opens the right URL.
- [ ] Tick Reset; the original values return.
- [ ] Log in as a non-admin: the page is not available.

## 4. Compatibility
- [ ] Caching or optimization plugin: exclude lt.js from "delay JavaScript" if calculators render blank.
- [ ] Security plugin: confirm it does not block the page installer or the scripts.
- [ ] SEO plugin: set a title and meta description on each page; do not use its FAQ block together with [lt_faq] on the same page.
- [ ] Google Rich Results Test on one FAQ page.
- [ ] PageSpeed Insights on the homepage and one calculator page (mobile).

## 5. Before going live
- [ ] Every price in Settings is checked against the vendor's pricing page, and the verified date is honest.
- [ ] Every "How we tested" section has real results; no empty headings remain.
- [ ] Trust pages are reviewed; the contact email is the one you want public.
- [ ] Old unwanted posts are redirected or removed.
- [ ] Sitemap is submitted in Search Console. Apply for AdSense only after the pages are published.

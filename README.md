# Latest & Trending Lab (WordPress plugin)

## Install
1. GitHub: upload everything in this folder to the root of a repo, then add the repo in the Git Updater plugin. Edit `GitHub Plugin URI` in `latest-trending-lab.php` first.
2. Or zip upload: Plugins > Add New > Upload Plugin, using latest-trending-lab.zip.
3. Activate. It creates DRAFT pages, and on every later activation it refreshes any page that is still a draft with the current content from /content. Published pages are never touched, but edits you make to a draft will be overwritten, so publish a page (or copy your edits elsewhere) before reactivating. It creates: Home, About, Methodology, Privacy Policy, Disclaimer, Contact, four silo parents and 30 tool pages under /calculators/, /geo-seo/, /vs/ and /workflows/. Existing pages with the same address are skipped. To run it again, deactivate and reactivate.

All 30 tool pages have drafts in /content (flagship calculators, GEO pages, /vs/ comparisons and /workflows/) are created from the drafts in /content. If you already activated the plugin, trash those 8 draft pages, then deactivate and reactivate; or paste the file contents into the pages by hand.

Test on staging first: see TESTING.md.

## Before you publish anything
- Replace the illustrative prices in data/calculators.json with figures from each vendor's pricing page, then update LT_VERIFIED in latest-trending-lab.php.
- Trust pages use your WordPress admin email as the public contact address. Edit it in the pages if you prefer another.
- Have the Privacy Policy and Disclaimer reviewed for your situation.
- Fill the commented sections on each tool page with real test results. Pages without them are thin content.
- Delete or redirect old posts you no longer want.

## Settings page
In WordPress go to Settings > Lab Settings to change the pricing verified date, every calculator's rates, button text, affiliate link and note, with no file editing. Edits are stored in the database, so plugin updates do not overwrite them. Tick Reset on a calculator to return to the shipped values.

## Shortcodes
[lt_trust] [lt_breakeven] [lt_calc id="slug"] [lt_directory cat="all|calc|geo|vs|wf"] [lt_prompt title=""]text[/lt_prompt] [lt_faq]Question|Answer (one per line)[/lt_faq] [lt_disclaimer] [lt_llms]

## Add a calculator
Add an object to data/calculators.json. Use the tool page's slug as the id. Cost of an option = fixed + sum of rate x product of inputs (terms) + a price tier (tier). Optional: cta {text,url} for your affiliate link, note for assumptions.

## Pricing and honesty
See PRICING-NOTES.md for the figures, sources and conflicts. The trust bar says "Calculated by" and the pages say "How the numbers are calculated", because they contain worked examples, not lab tests. Change the wording only after you run and document real tests.

## Structured data and sharing (v1.3.0)
Every `[lt_calc]`, `[lt_breakeven]` and `[lt_llms]` tool outputs a WebApplication JSON-LD block (free, requires JavaScript). Each calculator result has a Copy Result button that copies the title, inputs, each cost, the verdict and the page link. Each content page has a block of three related-tool links above the FAQ, spread across the four silos.

## SEO, GEO and mobile (v1.3.1)
- **Meta tags:** on the pages this plugin creates (Home, the 4 silo pages, the 5 trust pages and the 30 tool pages) it prints a meta description, og:title and og:description in the page head, using the description from data/directory.json plus "Updated" and the verified date from Lab Settings. It does nothing when Yoast SEO or Rank Math is active, so those plugins stay in charge.
- **llms.txt route:** visiting /llms.txt serves a plain text index of all 30 tools by silo, with full links and descriptions. A real llms.txt file in the site root takes priority over the route. By default it lists all 30 tools even while some are drafts; to list only published pages, add `add_filter('lt_llms_only_published', '__return_true');` in a small snippet plugin. If a cache or CDN serves old copies, purge /llms.txt after changes.
- **Mobile result bar:** on screens up to 768 px wide, a small bar at the bottom shows the current best option and its cost, plus your affiliate button if you set one in Lab Settings. The x button hides it until the page is reloaded.

## Polish (v1.3.2)
- **SEO plugin guard:** the meta tags are also skipped when All in One SEO, SEOPress or The SEO Framework is active, in addition to Yoast SEO and Rank Math.
- **Breadcrumbs:** `[lt_trust]` on a tool page shows "Home / silo name / page title" above the trust bar and outputs BreadcrumbList data. The silo name comes from the parent page title. If your SEO plugin already outputs breadcrumb data, add `add_filter('lt_breadcrumb_schema', '__return_false');` to keep only the visible line.
- **Mobile bar timing:** the bottom result bar stays hidden on first load and appears after the visitor changes an input or scrolls more than 320 px.

<?php
/**
 * Plugin Name: Latest & Trending Lab
 * Description: Shortcodes for live AI pricing calculators, a tool directory, copy-paste prompt boxes, FAQ schema and a pricing disclaimer.
 * Version: 1.3.2
 * Author: Latest & Trending
 * License: GPL-2.0-or-later
 * GitHub Plugin URI: YOUR-GITHUB-USERNAME/latest-trending-lab
 * Primary Branch: main
 */
if (!defined('ABSPATH')) exit;

define('LT_DIR', plugin_dir_path(__FILE__));
define('LT_URL', plugin_dir_url(__FILE__));
define('LT_VER', '1.3.2');
/* Edit this text only after you have re-checked the vendors' pricing pages. */
define('LT_VERIFIED', 'November 2026');

function lt_assets() {
    static $done = false;
    if ($done) return;
    $done = true;
    wp_enqueue_style('lt-css', LT_URL . 'assets/lt.css', array(), LT_VER);
    wp_enqueue_script('lt-js', LT_URL . 'assets/lt.js', array(), LT_VER, true);
}

add_filter('no_texturize_shortcodes', function ($list) {
    $list[] = 'lt_prompt';
    return $list;
});

/* Load the CSS in the page head (not the footer) on any page that uses a lab shortcode. */
add_action('wp_enqueue_scripts', function () {
    if (!is_singular()) return;
    $post = get_post();
    if ($post && strpos($post->post_content, '[lt_') !== false) lt_assets();
});

function lt_json($file) {
    if ($file === 'calculators.json') return lt_calcs_merged();
    return lt_json_raw($file);
}

/* Calculators with any edits saved in Settings > Lab Settings applied. */
function lt_calcs_merged() {
    $calcs = lt_json_raw('calculators.json');
    $ov = get_option('lt_calc_overrides', array());
    if (!is_array($ov)) $ov = array();
    foreach ($calcs as $k => $c) {
        if (isset($c['id']) && isset($ov[$c['id']]) && is_array($ov[$c['id']])) $calcs[$k] = $ov[$c['id']];
    }
    return $calcs;
}

function lt_json_raw($file) {
    $p = LT_DIR . 'data/' . $file;
    if (!file_exists($p)) return array();
    $d = json_decode(file_get_contents($p), true);
    return is_array($d) ? $d : array();
}

/* WebApplication structured data for each interactive tool */
function lt_webapp_schema($name, $desc) {
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        'name' => $name,
        'description' => $desc,
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Any',
        'browserRequirements' => 'Requires JavaScript',
        'offers' => array('@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'),
    );
    $url = get_permalink();
    if ($url) $schema['url'] = $url;
    return '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
}

function lt_verified_text() {
    return get_option('lt_verified') ? get_option('lt_verified') : LT_VERIFIED;
}

/* [lt_trust] Block 1: trust bar */
/* Breadcrumb line and BreadcrumbList data for tool pages under the four silos */
function lt_breadcrumb_html() {
    $post = get_post();
    if (!$post || $post->post_type !== 'page') return '';
    $parts = explode('/', get_page_uri($post));
    if (count($parts) !== 2) return '';
    $silos = array();
    foreach (lt_silo_parents() as $s) $silos[$s[0]] = $s[1];
    if (!isset($silos[$parts[0]])) return '';
    $parent = get_page_by_path($parts[0]);
    $silo_name = $parent ? wp_strip_all_tags(html_entity_decode(get_the_title($parent), ENT_QUOTES, 'UTF-8')) : $silos[$parts[0]];
    $home = home_url('/');
    $silo_url = home_url('/' . $parts[0] . '/');
    $title = wp_strip_all_tags(html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'));
    $url = get_permalink($post);
    $html = '<nav class="lt-crumbs-nav" aria-label="Breadcrumb"><ol class="lt-crumbs">'
        . '<li><a href="' . esc_url($home) . '">Home</a></li>'
        . '<li><a href="' . esc_url($silo_url) . '">' . esc_html($silo_name) . '</a></li>'
        . '<li><span aria-current="page">' . esc_html($title) . '</span></li></ol></nav>';
    if (apply_filters('lt_breadcrumb_schema', true)) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array(
                array('@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $home),
                array('@type' => 'ListItem', 'position' => 2, 'name' => $silo_name, 'item' => $silo_url),
                array('@type' => 'ListItem', 'position' => 3, 'name' => $title, 'item' => $url),
            ),
        );
        $html .= '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
    }
    return $html;
}

add_shortcode('lt_trust', function () {
    lt_assets();
    return lt_breadcrumb_html() . '<div class="lt-trust" role="status">Pricing &amp; API Rates Verified: <b>' . esc_html(get_option('lt_verified') ? get_option('lt_verified') : LT_VERIFIED) . '</b> <span>|</span> Calculated by Latest &amp; Trending Lab <span>|</span> 30+ Interactive Benchmarks</div>';
});

/* [lt_breakeven cta_text="" cta_url=""] Homepage API vs subscription calculator */
add_shortcode('lt_breakeven', function ($a) {
    lt_assets();
    $a = shortcode_atts(array('cta_text' => 'Compare API wrappers', 'cta_url' => home_url('/calculators/')), $a);
    return '<div class="lt-calc" data-lt="breakeven" data-cta-text="' . esc_attr($a['cta_text']) . '" data-cta-url="' . esc_url($a['cta_url']) . '"></div>'
        . lt_webapp_schema('AI API vs Monthly Subscription Breakeven Calculator', 'Free interactive calculator that compares pay-as-you-go AI API costs with flat monthly subscription seats, using published vendor prices checked ' . lt_verified_text() . '.');
});

/* [lt_calc id="make-vs-zapier"] Block 2: calculator defined in data/calculators.json */
add_shortcode('lt_calc', function ($a) {
    lt_assets();
    $a = shortcode_atts(array('id' => ''), $a);
    foreach (lt_json('calculators.json') as $c) {
        if (isset($c['id']) && $c['id'] === $a['id']) {
            $title = isset($c['title']) ? $c['title'] : 'Cost calculator';
            return '<div class="lt-calc" data-lt="calc" data-cfg="' . esc_attr(wp_json_encode($c)) . '"></div>'
                . lt_webapp_schema($title, 'Free interactive calculator: ' . $title . '. Uses published vendor prices checked ' . lt_verified_text() . ' and clearly stated assumptions.');
        }
    }
    return current_user_can('edit_posts') ? '<p>Calculator "' . esc_html($a['id']) . '" was not found in data/calculators.json.</p>' : '';
});

/* [lt_llms] Simple llms.txt generator */
add_shortcode('lt_llms', function () {
    lt_assets();
    return '<div class="lt-calc" data-lt="llms"></div>'
        . lt_webapp_schema('llms.txt Generator', 'Free tool that builds an llms.txt file from your site name, summary and key pages. It runs in your browser, so nothing you type is sent to our servers.');
});

/* [lt_directory cat="all|calc|geo|vs|wf"] Searchable directory from data/directory.json */
add_shortcode('lt_directory', function ($a) {
    lt_assets();
    $a = shortcode_atts(array('cat' => 'all'), $a);
    $items = array_map(function ($i) { $i['u'] = home_url($i['u']); return $i; }, lt_json('directory.json'));
    return '<div class="lt-dir" data-lt="dir" data-cat="' . esc_attr($a['cat']) . '" data-items="' . esc_attr(wp_json_encode($items)) . '"></div>';
});

/* [lt_prompt title=""]prompt text[/lt_prompt] Block 4: copy-paste prompt box */
add_shortcode('lt_prompt', function ($a, $c = '') {
    lt_assets();
    $a = shortcode_atts(array('title' => 'Copy-paste workflow prompt'), $a);
    $t = trim(wp_strip_all_tags(preg_replace('#<br\s*/?>|</p>#i', "\n", $c)));
    return '<div class="lt-prompt"><div class="lt-ph"><span>' . esc_html($a['title']) . '</span><button type="button" class="lt-copy">Copy Workflow Prompt</button></div><pre>' . esc_html($t) . '</pre></div>';
});

/* [lt_faq] Block 5: one "Question|Answer" per line, outputs FAQPage JSON-LD */
add_shortcode('lt_faq', function ($a, $c = '') {
    lt_assets();
    $qa = array();
    foreach (preg_split('#\r\n|\r|\n|<br\s*/?>|</p>#i', $c) as $l) {
        $l = trim(html_entity_decode(wp_strip_all_tags($l), ENT_QUOTES, 'UTF-8'));
        if (strpos($l, '|') === false) continue;
        list($q, $an) = array_map('trim', explode('|', $l, 2));
        if ($q !== '' && $an !== '') $qa[] = array($q, $an);
    }
    if (!$qa) return '';
    $h = '<div class="lt-faq">';
    $ld = array();
    foreach ($qa as $x) {
        $h .= '<details><summary>' . esc_html($x[0]) . '</summary><p>' . esc_html($x[1]) . '</p></details>';
        $ld[] = array('@type' => 'Question', 'name' => $x[0], 'acceptedAnswer' => array('@type' => 'Answer', 'text' => $x[1]));
    }
    $schema = array('@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $ld);
    return $h . '</div><script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
});

/* [lt_disclaimer] Pricing and financial disclaimer banner */
add_shortcode('lt_disclaimer', function () {
    lt_assets();
    return '<div class="lt-disc" role="note"><strong>Pricing and financial disclaimer:</strong> Calculator results are estimates for planning only and are not financial, legal or purchasing advice. Vendor prices, limits and credit rules change without notice. Some links are affiliate links; we may earn a commission at no extra cost to you.</div>';
});


/* ---------- Page definitions shared by the installer and the SEO meta tags ---------- */
function lt_silo_parents() {
    return array(
        'calc' => array('calculators', 'AI Pricing Calculators', 'Live calculators that show what AI tools and automations really cost at your usage level.'),
        'geo'  => array('geo-seo', 'GEO and AI Visibility', 'Tools and comparisons for tracking and improving how your brand appears in AI search answers.'),
        'vs'   => array('vs', 'Tool Comparisons', 'Side-by-side pricing and credit burn comparisons for popular AI and automation tools.'),
        'wf'   => array('workflows', 'No-Code AI Workflows', 'No-code AI workflow blueprints with cost and time savings calculators.'),
    );
}

/* slug => array(page title, social title, description) */
function lt_trust_pages() {
    return array(
        'about'          => array('About', 'About Latest & Trending', 'An independent lab that publishes live AI pricing calculators, tool comparisons and no-code workflow blueprints.'),
        'methodology'    => array('Methodology', 'Methodology: How Latest & Trending Calculates Costs', 'How every calculator works: published vendor rates, formulas, worked examples and clearly labelled assumptions.'),
        'privacy-policy' => array('Privacy Policy', 'Privacy Policy | Latest & Trending', 'How we handle data, cookies, advertising and affiliate links. Calculator inputs stay in your browser.'),
        'disclaimer'     => array('Disclaimer', 'Disclaimer | Latest & Trending', 'Pricing, financial and affiliate disclaimer for our calculators and comparisons.'),
        'contact'        => array('Contact', 'Contact Latest & Trending', 'Report a pricing error, ask a question or discuss a partnership.'),
    );
}

/* ---------- SEO meta tags for plugin pages (skipped when Yoast SEO or Rank Math is active) ---------- */
function lt_meta_for_path($path) {
    if ($path === 'lt-home') {
        return array('Latest & Trending: Live AI Pricing Calculators and Tool Comparisons', 'Live AI pricing calculators, GEO trackers, tool comparisons and no-code workflow blueprints.');
    }
    foreach (lt_silo_parents() as $p) {
        if ($path === $p[0]) return array($p[1], $p[2]);
    }
    foreach (lt_trust_pages() as $slug => $t) {
        if ($path === $slug) return array($t[1], $t[2]);
    }
    foreach (lt_json('directory.json') as $i) {
        if (trim($i['u'], '/') === $path) return array($i['t'], $i['d']);
    }
    return null;
}

add_action('wp_head', 'lt_meta_tags', 5);
function lt_meta_tags() {
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION') || defined('SEOPRESS_VERSION') || defined('THE_SEO_FRAMEWORK_VERSION')) return;
    if (!is_page()) return;
    $post = get_queried_object();
    if (!$post || empty($post->ID)) return;
    $m = lt_meta_for_path(get_page_uri($post));
    if (!$m) return;
    $d = trim($m[1]);
    if ($d !== '' && !preg_match('/[.!?]$/', $d)) $d .= '.';
    $desc = $d . ' Updated ' . lt_verified_text() . '.';
    echo "\n" . '<meta name="description" content="' . esc_attr($desc) . '">' . "\n"
        . '<meta property="og:title" content="' . esc_attr($m[0]) . '">' . "\n"
        . '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
}

/* ---------- Live /llms.txt (a physical llms.txt file in the site root takes priority) ---------- */
function lt_llms_clean($s) {
    return trim(preg_replace('/\s+/', ' ', str_replace(array('[', ']'), array('(', ')'), (string) $s)));
}

function lt_llms_txt_body() {
    $groups = array('calculators' => 'Calculators', 'geo-seo' => 'GEO and AI Visibility', 'vs' => 'Tool Comparisons', 'workflows' => 'No-Code Workflows');
    $only_published = apply_filters('lt_llms_only_published', false);
    $by = array();
    foreach (lt_json('directory.json') as $i) {
        $path = trim($i['u'], '/');
        $parts = explode('/', $path);
        if (!isset($groups[$parts[0]])) continue;
        if ($only_published) {
            $pg = get_page_by_path($path);
            if (!$pg || $pg->post_status !== 'publish') continue;
        }
        $by[$parts[0]][] = '- [' . lt_llms_clean($i['t']) . '](' . home_url($i['u']) . '): ' . lt_llms_clean($i['d']);
    }
    $out  = "# Latest & Trending Lab\n\n";
    $out .= '> Live AI pricing calculators, GEO trackers, tool comparisons and no-code workflow blueprints. Prices checked ' . lt_verified_text() . ".\n\n";
    $out .= "Latest & Trending Lab publishes free interactive calculators that estimate what AI tools and automations cost at your usage level. Figures come from published vendor pricing and clearly stated assumptions, so they are planning estimates, not quotes. The methodology page explains the sources and formulas.\n\n";
    foreach ($groups as $silo => $label) {
        if (empty($by[$silo])) continue;
        $out .= '## ' . $label . "\n\n" . implode("\n", $by[$silo]) . "\n\n";
    }
    $out .= "## Optional\n\n";
    $out .= '- [Methodology](' . home_url('/methodology/') . "): How every calculator works, with sources and assumptions\n";
    $out .= '- [About](' . home_url('/about/') . "): Who publishes this site\n";
    $out .= '- [Disclaimer](' . home_url('/disclaimer/') . "): Pricing, financial and affiliate disclaimer\n";
    $out .= '- [Privacy policy](' . home_url('/privacy-policy/') . "): How data and cookies are handled\n";
    $out .= '- [Contact](' . home_url('/contact/') . "): Report an error or ask a question\n";
    return $out;
}

add_action('init', 'lt_serve_llms_txt', 1);
function lt_serve_llms_txt() {
    if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX) || (defined('WP_CLI') && WP_CLI)) return;
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $path = rtrim((string) parse_url($uri, PHP_URL_PATH), '/');
    $target = rtrim((string) parse_url(home_url('/llms.txt'), PHP_URL_PATH), '/');
    if ($path === '' || strcasecmp($path, $target) !== 0) return;
    status_header(200);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=3600');
    echo lt_llms_txt_body();
    exit;
}

/* ---------- Page installer: runs once on activation, creates DRAFT pages only ---------- */
register_activation_hook(__FILE__, 'lt_install_pages');

function lt_page($title, $slug, $content, $parent = 0) {
    $path = $parent ? get_page_uri($parent) . '/' . $slug : $slug;
    $existing = get_page_by_path($path);
    if ($existing) {
        /* Refresh pages that are still drafts. Published pages are never touched, so live edits are safe. */
        if ($existing->post_status === 'draft') {
            wp_update_post(array(
                'ID' => $existing->ID,
                'post_title' => $title,
                'post_content' => wp_slash($content),
            ));
        }
        return (int) $existing->ID;
    }
    $id = wp_insert_post(array(
        'post_title' => $title, 'post_name' => $slug, 'post_content' => wp_slash($content),
        'post_status' => 'draft', 'post_type' => 'page', 'post_parent' => $parent,
    ), true);
    return is_wp_error($id) ? 0 : (int) $id;
}

function lt_page_file($name) {
    $p = LT_DIR . 'pages/' . $name . '.html';
    if (!file_exists($p)) return '';
    return str_replace(
        array('{{email}}', '{{site}}', '{{date}}'),
        array(get_option('admin_email'), home_url(), date_i18n('F j, Y')),
        file_get_contents($p)
    );
}

function lt_install_pages() {
    foreach (lt_trust_pages() as $slug => $t) lt_page($t[0], $slug, lt_page_file($slug));

    lt_page('Home', 'lt-home', "[lt_trust]\n[lt_breakeven]\n<h2>All tools and blueprints</h2>\n[lt_directory]\n[lt_disclaimer]");

    $parents = lt_silo_parents();
    $ids = array();
    foreach ($parents as $k => $p) {
        $ids[$k] = lt_page($p[1], $p[0], '<p>' . $p[2] . "</p>\n[lt_directory cat=\"" . $k . "\"]");
    }

    $calcs = array();
    foreach (lt_json('calculators.json') as $c) $calcs[$c['id']] = true;

    foreach (lt_json('directory.json') as $i) {
        $parts = explode('/', trim($i['u'], '/'));
        if (count($parts) < 2) continue;
        $slug = $parts[1];
        if ($slug === 'api-vs-subscription-calculator') $calc = '[lt_breakeven]';
        elseif ($slug === 'optimize-website-for-chatgpt-perplexity-citations') $calc = '[lt_llms]';
        elseif (isset($calcs[$slug])) $calc = '[lt_calc id="' . $slug . '"]';
        else $calc = '<!-- The interactive tool for this page goes here. -->';
        $body = "[lt_trust]\n\n" . $calc . "\n\n"
            . "<h2>Direct answer</h2>\n<!-- After testing, add 3 bullets: best for solo builders, best for agencies, the pricing trap to watch. -->\n\n"
            . "<h2>Copy-paste workflow prompt</h2>\n<!-- Add a prompt box here with the lt_prompt shortcode. -->\n\n"
            . "<h2>How we tested</h2>\n<!-- Add real test inputs, outputs and screenshots here. -->\n\n"
            . "<h2>FAQ</h2>\n<!-- Add four real questions here with the lt_faq shortcode. -->\n\n"
            . "[lt_disclaimer]";
        $cf = LT_DIR . 'content/' . $slug . '.html';
        if (file_exists($cf)) $body = "[lt_trust]\n\n" . $calc . "\n\n" . file_get_contents($cf) . "\n\n[lt_disclaimer]";
        lt_page($i['t'], $slug, $body, isset($ids[$i['c']]) ? $ids[$i['c']] : 0);
    }
}

if (is_admin()) require_once LT_DIR . 'includes/admin.php';

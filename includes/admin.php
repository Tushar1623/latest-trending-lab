<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_options_page('Latest & Trending Lab', 'Lab Settings', 'manage_options', 'lt-lab', 'lt_admin_page');
});

function lt_num($v) {
    return is_numeric($v) ? (float) $v : 0.0;
}

function lt_fmt($v) {
    return rtrim(rtrim(sprintf('%.10F', (float) $v), '0'), '.') ?: '0';
}

function lt_admin_save() {
    if (!isset($_POST['lt_save'])) return '';
    if (!current_user_can('manage_options')) return '';
    check_admin_referer('lt_save_settings');

    update_option('lt_verified', sanitize_text_field(wp_unslash(isset($_POST['lt_verified']) ? $_POST['lt_verified'] : '')));

    $post = (isset($_POST['lt']) && is_array($_POST['lt'])) ? wp_unslash($_POST['lt']) : array();
    $overrides = array();

    foreach (lt_json_raw('calculators.json') as $orig) {
        $id = $orig['id'];
        if (!isset($post[$id]) || !is_array($post[$id])) continue;
        $p = $post[$id];
        if (!empty($p['reset'])) continue;

        $c = $orig;
        $note = isset($p['note']) ? sanitize_text_field($p['note']) : '';
        if ($note !== '') $c['note'] = $note; else unset($c['note']);

        $url = isset($p['cta_url']) ? esc_url_raw(trim($p['cta_url']), array('http', 'https')) : '';
        $txt = isset($p['cta_text']) ? sanitize_text_field($p['cta_text']) : '';
        if ($url !== '') $c['cta'] = array('text' => ($txt !== '' ? $txt : 'See pricing'), 'url' => $url); else unset($c['cta']);

        foreach ($c['options'] as $i => $o) {
            $q = (isset($p['options'][$i]) && is_array($p['options'][$i])) ? $p['options'][$i] : array();
            if (isset($q['name'])) {
                $name = sanitize_text_field($q['name']);
                if ($name !== '') $c['options'][$i]['name'] = $name;
            }
            $fixed = isset($q['fixed']) ? lt_num($q['fixed']) : 0.0;
            if (isset($o['fixed']) || $fixed != 0) $c['options'][$i]['fixed'] = $fixed;
            if (!empty($o['terms'])) {
                foreach ($o['terms'] as $j => $t) {
                    if (isset($q['terms'][$j])) $c['options'][$i]['terms'][$j][0] = lt_num($q['terms'][$j]);
                }
            }
            if (isset($o['min']) && isset($q['min'])) $c['options'][$i]['min'] = lt_num($q['min']);
            if (!empty($o['extra'])) {
                foreach ($o['extra'] as $x => $e) {
                    if (isset($q['extra'][$x])) $c['options'][$i]['extra'][$x]['rate'] = lt_num($q['extra'][$x]);
                }
            }
            if (!empty($o['tier']['steps'])) {
                foreach ($o['tier']['steps'] as $k => $s) {
                    if (isset($q['steps'][$k][0])) $c['options'][$i]['tier']['steps'][$k][0] = lt_num($q['steps'][$k][0]);
                    if (isset($q['steps'][$k][1])) $c['options'][$i]['tier']['steps'][$k][1] = lt_num($q['steps'][$k][1]);
                }
                if (isset($q['over'])) $c['options'][$i]['tier']['over'] = lt_num($q['over']);
            }
        }
        if ($c != $orig) $overrides[$id] = $c;
    }
    update_option('lt_calc_overrides', $overrides, false);
    return 'Settings saved.';
}

function lt_admin_page() {
    if (!current_user_can('manage_options')) return;
    $msg = lt_admin_save();
    $calcs = lt_json('calculators.json');
    $raw = array();
    foreach (lt_json_raw('calculators.json') as $r) $raw[$r['id']] = true;

    echo '<div class="wrap"><h1>Latest &amp; Trending Lab</h1>';
    if ($msg) echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
    echo '<form method="post">';
    wp_nonce_field('lt_save_settings');

    echo '<h2>Pricing verified date</h2>';
    echo '<p>Shown in the trust bar at the top of every tool page. Change it only after you have rechecked the vendors\' pricing pages.</p>';
    echo '<input type="text" name="lt_verified" class="regular-text" value="' . esc_attr(get_option('lt_verified') ? get_option('lt_verified') : LT_VERIFIED) . '">';

    echo '<h2>Calculator rates</h2>';
    echo '<p>All amounts are USD per month unless a label says otherwise. Open a calculator to edit its rates, your affiliate link and its note. The rates shipped with the plugin are illustrative, so replace them with figures from each vendor\'s pricing page. Tick Reset to go back to the shipped values.</p>';

    $ov = get_option('lt_calc_overrides', array());
    foreach ($calcs as $c) {
        $n = 'lt[' . $c['id'] . ']';
        $changed = is_array($ov) && isset($ov[$c['id']]);
        echo '<details style="background:#fff;border:1px solid #c3c4c7;padding:8px 14px;margin:8px 0">';
        echo '<summary><strong>' . esc_html($c['title']) . '</strong> <code>' . esc_html($c['id']) . '</code>' . ($changed ? ' <em>(edited)</em>' : '') . '</summary>';

        foreach ($c['options'] as $i => $o) {
            $b = $n . '[options][' . $i . ']';
            echo '<fieldset style="margin:12px 0;padding:10px;border:1px solid #dcdcde"><p>';
            echo '<label>Name <input type="text" name="' . esc_attr($b . '[name]') . '" value="' . esc_attr($o['name']) . '"></label> ';
            echo '<label>Fixed monthly fee <input type="number" step="any" min="0" name="' . esc_attr($b . '[fixed]') . '" value="' . esc_attr(lt_fmt(isset($o['fixed']) ? $o['fixed'] : 0)) . '"></label></p>';
            if (!empty($o['terms'])) {
                foreach ($o['terms'] as $j => $t) {
                    $label = 'Rate per ' . implode(' × ', array_slice($t, 1));
                    echo '<p><label>' . esc_html($label) . ' ($) <input type="number" step="any" min="0" name="' . esc_attr($b . '[terms][' . $j . ']') . '" value="' . esc_attr(lt_fmt($t[0])) . '"></label></p>';
                }
            }
            if (!empty($o['tier']['steps'])) {
                $inl = implode(' × ', $o['tier']['in']);
                echo '<p><strong>Plan tiers</strong> (usage = ' . esc_html($inl) . ')</p>';
                foreach ($o['tier']['steps'] as $k => $s) {
                    echo '<p>Up to <input type="number" step="any" min="0" name="' . esc_attr($b . '[steps][' . $k . '][0]') . '" value="' . esc_attr(lt_fmt($s[0])) . '"> costs $ <input type="number" step="any" min="0" name="' . esc_attr($b . '[steps][' . $k . '][1]') . '" value="' . esc_attr(lt_fmt($s[1])) . '"></p>';
                }
                echo '<p><label>Extra cost per unit above the top tier ($) <input type="number" step="any" min="0" name="' . esc_attr($b . '[over]') . '" value="' . esc_attr(lt_fmt(isset($o['tier']['over']) ? $o['tier']['over'] : 0)) . '"></label></p>';
            }
            if (isset($o['min'])) {
                echo '<p><label>Monthly minimum charge ($) <input type="number" step="any" min="0" name="' . esc_attr($b . '[min]') . '" value="' . esc_attr(lt_fmt($o['min'])) . '"></label></p>';
            }
            if (!empty($o['extra'])) {
                foreach ($o['extra'] as $x => $e) {
                    echo '<p><label>Extra cost per ' . esc_html($e['in']) . ' above ' . esc_html($e['free']) . ' ($) <input type="number" step="any" min="0" name="' . esc_attr($b . '[extra][' . $x . ']') . '" value="' . esc_attr(lt_fmt($e['rate'])) . '"></label></p>';
                }
            }
            echo '</fieldset>';
        }

        $cta = isset($c['cta']) ? $c['cta'] : array('text' => '', 'url' => '');
        echo '<p><label>Button text <input type="text" name="' . esc_attr($n . '[cta_text]') . '" value="' . esc_attr($cta['text']) . '"></label> ';
        echo '<label>Button link (affiliate URL) <input type="text" class="regular-text" name="' . esc_attr($n . '[cta_url]') . '" value="' . esc_attr($cta['url']) . '"></label></p>';
        echo '<p><label>Note shown under the result<br><textarea rows="2" class="large-text" name="' . esc_attr($n . '[note]') . '">' . esc_textarea(isset($c['note']) ? $c['note'] : '') . '</textarea></label></p>';
        echo '<p><label><input type="checkbox" name="' . esc_attr($n . '[reset]') . '" value="1"> Reset this calculator to the shipped values</label></p>';
        echo '</details>';
    }
    submit_button('Save settings', 'primary', 'lt_save');
    echo '</form></div>';
}

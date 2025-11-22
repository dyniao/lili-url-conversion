<?php

//define("COUNTDOWN", false);
define("COUNTDOWN", true);

$languages = [
    'en' => [
        'title' => 'Liliill URL Encoder - liliill.li',
        'submit' => 'Submit',
        'friendly' => 'Friendly',
        'shorter' => 'Shorter',
        'copy' => 'Copy',
        'copied' => 'Copied to clipboard',
        'copy_failed' => 'Copy failed. Please copy manually.',
        'input_placeholder' => 'Paste your link here!',
        'encoding' => 'Encoding...',
        'url_empty' => 'URL cannot be empty',
        'decode_error' => 'Decoding error',
        'redirect_wait' => 'Redirecting in {sec} seconds. Press any key or click to pause.',
        'redirect_paused' => 'Redirection paused. You can click "Jump Now" to continue.',
        'jump_now' => 'Jump Now',
        'title_description' => 'Lili ill .li - Liliill',
        'js_error' => 'Error: ',
    ],
    'de' => [
        'title' => 'Liliill URL-Encoder - liliill.li',
        'submit' => 'Absenden',
        'friendly' => 'Besser',
        'shorter' => 'Kürzer',
        'copy' => 'Kopieren',
        'copied' => 'In die Zwischenablage kopiert',
        'copy_failed' => 'Kopieren fehlgeschlagen. Bitte manuell kopieren.',
        'input_placeholder' => 'Füge hier deinen Link ein!',
        'encoding' => 'Wird codiert...',
        'url_empty' => 'URL darf nicht leer sein',
        'decode_error' => 'Fehler beim Decodieren',
        'redirect_wait' => 'Weiterleitung in {sec} Sekunden. Drücke eine Taste oder klicke, um zu pausieren.',
        'redirect_paused' => 'Weiterleitung pausiert. Du kannst mit „Jetzt springen“ fortfahren.',
        'jump_now' => 'Jetzt springen',
        'title_description' => 'Lili ill .li - Liliill',
        'js_error' => 'Fehler: ',
    ],
    'zh' => [
        'title' => '立立竝 URL 编码器  - liliill.li',
        'submit' => '提交',
        'friendly' => '更友好',
        'shorter' => '更短',
        'copy' => '复制',
        'copied' => '已复制到剪贴板',
        'copy_failed' => '复制失败，请手动复制',
        'input_placeholder' => '竝你的链接!',
        'encoding' => '编码中...',
        'url_empty' => 'URL不能为空',
        'decode_error' => '解码错误',
        'redirect_wait' => '还有{sec}秒，正准备跳转以下地址，如需要暂停，请按任意键或点击鼠标。',
        'redirect_paused' => '已暂停跳转。您可以点击"立即跳转"按钮手动跳转。',
        'jump_now' => '立即跳转',
        'title_description' => 'Lili ill .li - 立立竝',
        'js_error' => '错误: ',
    ],
];

$selected_lang = $_COOKIE['lang'] ?? 'en';
if (!isset($languages[$selected_lang]))
    $selected_lang = 'en';
$L = $languages[$selected_lang];

if (isset($_POST['action']) && $_POST['action'] === 'change_language') {
    header('Content-Type: application/json');
    $lang = $_POST['lang'] ?? '';
    if (isset($languages[$lang])) {
        setcookie('lang', $lang, time() + (86400 * 30), '/');
        echo json_encode(['success' => true, 'translations' => $languages[$lang]]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

$CHAR_MAP = [
    'replacements' => [
        '0' => '৷',
        '1' => '୲',
        '2' => 'ᛁ',
        '3' => 'ᥣ',
        '4' => '❘',
        '5' => 'ⵏ',
        '6' => 'ꓲ',
        '7' => '𐰾'
    ]
];
$CHAR_MAP['restore'] = array_flip($CHAR_MAP['replacements']);

function scheme_prefix_bits(string $url, string &$rest): string
{
    $low = strtolower($url);
    if (str_starts_with($low, 'https://')) {
        $rest = substr($url, 8);
        return '1111';
    }
    if (str_starts_with($low, 'http://')) {
        $rest = substr($url, 7);
        return '0000';
    }
    $rest = $url;
    return '1010';
}
function string_to_bitstring(string $s): string
{
    return implode('', array_map(fn($c) => str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT), str_split($s)));
}
function bitstring_to_string(string $bits): string
{
    return implode('', array_map(fn($b) => chr(bindec($b)), str_split($bits, 8)));
}
function replace_chars(string $text, string $mode): string
{
    global $CHAR_MAP;
    return match ($mode) {
        'binary' => str_replace(['0', '1'], ['I', 'l'], $text),
        'octal' => strtr($text, $CHAR_MAP['replacements']),
        default => $text
    };
}
function reverse_replace_chars(string $text, string $mode): string
{
    global $CHAR_MAP;
    return match ($mode) {
        'binary' => str_replace(['I', 'l'], ['0', '1'], $text),
        'octal' => strtr($text, $CHAR_MAP['restore']),
        default => $text
    };
}
function encode_url(string $url, string $mode = 'binary'): string
{
    $rest = '';
    $scheme_bits = scheme_prefix_bits($url, $rest);
    $data_bits = string_to_bitstring(rawurlencode($rest));
    if ($mode === 'binary') {
        $encoded = replace_chars('00' . $scheme_bits . $data_bits, 'binary');
    } elseif ($mode === 'octal') {
        $bits = $scheme_bits . $data_bits;
        $bits = str_pad($bits, ceil(strlen($bits) / 3) * 3, '0');
        $oct = implode('', array_map(fn($g) => bindec($g), str_split($bits, 3)));
        $encoded = replace_chars('01' . $oct, 'octal');
    } else
        $encoded = $url;
    return "https://lIlIIll.lI/" . $encoded;
}
function decode_text(string $text): array
{
    if (strlen($text) < 2)
        return ['error' => 'too short'];
    $is_binary = preg_match('/^[Il]{2}/', $text) === 1;
    if ($is_binary) {
        $text = reverse_replace_chars($text, 'binary');
        if (!str_starts_with($text, '00'))
            return ['error' => 'binary error'];
        $data = substr($text, 2);
        $scheme_bits = substr($data, 0, 4);
        $data_bits = substr($data, 4);
    } else {
        $text = reverse_replace_chars($text, 'octal');
        if (!str_starts_with($text, '01'))
            return ['error' => 'octal error'];
        $data = substr($text, 2);
        $full_bits = '';
        foreach (str_split($data) as $ch)
            $full_bits .= str_pad(decbin((int) $ch), 3, '0', STR_PAD_LEFT);
        $scheme_bits = substr($full_bits, 0, 4);
        $data_bits = substr($full_bits, 4);
    }
    $scheme = match ($scheme_bits) { '1111' => 'https://', '0000' => 'http://', default => ''};
    $rest_encoded = bitstring_to_string($data_bits);
    return ['original' => $scheme . rawurldecode($rest_encoded)];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'encode') {
    header('Content-Type: application/json');
    $url = trim($_POST['url'] ?? '');
    $mode = $_POST['mode'] ?? 'binary';
    if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
        echo json_encode(['success' => false, 'error' => $L['url_empty']]);
        exit;
    }
    echo json_encode(['success' => true, 'encoded' => encode_url($url, $mode)]);
    exit;
}

$decoded_result = !empty($_GET['get']) ? decode_text(trim($_GET['get'])) : null;

if (!COUNTDOWN) {
    header("Location:" . $decoded_result['original']);
}

?>
<!DOCTYPE html>
<html lang="en"  data-theme="light">

<head>
    <meta charset="utf-8">
    <link rel="icon" href="favicon.png" />
    <meta name="description"
        content="Makes your links look cleaner, smarter, and more shareable. Instantly transform any URL into a stylish, memorable, and user-friendly link. Simply make your links look better.">
    <meta name="keywords"
        content="link beautifier, URL shortener, link designer, clean URLs, aesthetic links, link generator, custom URL, shareable links, make links look better">
    <title><?= htmlspecialchars($L['title']) ?></title>
    <style>
html,body,h1{margin:0;padding:0}
a{text-decoration:none}
html,body{height:100%;text-align:center}
@font-face{font-family:'LiliFont';src:url('lilifont.woff2') format('woff2')}
:root{--primary-gradient:linear-gradient(135deg,#23a6d5,#23d5ab);--accent-color:#23a6d5;--accent-color-2:#23d5ab;--text-color:#111;--bg-color:#fff;--error-color:#d00;--success-color:#060;--card-bg:#fff;--nav-bg:#43cea2;--border-radius:10px;--shadow-light:0 6px 18px rgba(0,0,0,0.03);--shadow-medium:0 4px 15px rgba(37,117,252,0.4);--transition:all 0.3s ease}
[data-theme="dark"]{--bg-color:#121212;--text-color:#eee;--card-bg:#1e1e1e;--nav-bg:#185a9d;--link-color:#23a6d5;--shadow-light:0 6px 18px rgba(255,255,255,0.05)}
.page-section{height:100vh;padding:0 3em;color:var(--text-color);transition:var(--transition);background:var(--bg-color)}
.page-section.one{background:linear-gradient(45deg,#f0f9ff 10%,#e0f2fe 90%);display:flex;flex-direction:column;justify-content:space-between;min-height:100vh;box-sizing:border-box;color:#1e293b}
[data-theme="dark"] body{background:var(--bg-color);color:var(--text-color)}
[data-theme="dark"] input[type="text"],[data-theme="dark"] textarea{background:#2d2d2d;border-color:#444;color:var(--text-color)}
[data-theme="dark"] .radio{background:#2d2d2d}
[data-theme="dark"] .footer-content,[data-theme="dark"] .lang-switch{color:#bbb}
[data-theme="dark"] .lang-switch{background:rgba(255,255,255,0.1)}
[data-theme="dark"] .page-section.one{background:linear-gradient(45deg,#0f172a 10%,#1e293b 90%);color:#f1f5f9}
.card{width:900px;margin:0 auto;padding:100px 0}
.one h1{font-family:"LiliFont",sans-serif;font-size:10rem;position:relative;text-align:center;color:transparent;background:linear-gradient(45deg,#23d5ab,#23a6d5,#e73c7e,#ee7752);background-size:400% 400%;-webkit-background-clip:text;background-clip:text;animation:text-gradient 15s ease infinite}
@keyframes text-gradient{0%{background-position:0% 50%}
50%{background-position:100% 50%}
100%{background-position:0% 50%}
}@keyframes ani{0%{background-position:0 0}
100%{background-position:4em 4em}
}input[type="text"],textarea{border:1px solid #ddd;font-size:1em;color:#666;transition:var(--transition)}
.input-gradient{margin:0;padding:26px;flex:1;color:white;border:none;outline:none;font-size:1em;border-radius:35px;cursor:pointer;transition:var(--transition)}
.input-gradient:focus,.input-gradient:active{outline:none;box-shadow:none}
.row-container{display:flex;align-items:center;justify-content:space-between;gap:20px;margin:30px 0;flex-wrap:wrap}
.row-container:has(>:only-child){justify-content:center}
.row-container:has(>:only-child) > *{flex:1 1 100%}
.row-container:has(>:nth-child(2):last-child){justify-content:flex-end}
.switch-container{min-width:280px;display:flex;align-items:center}
.radio{position:relative;width:280px;height:74px;background:#f0f0f0;border-radius:35px;box-shadow:inset 0 2px 10px rgba(0,0,0,0.1);overflow:hidden}
.radio input{display:none}
.radio label{position:absolute;top:0;width:50%;height:100%;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:1em;cursor:pointer;transition:color 0.3s ease;z-index:2}
.bar{position:absolute;top:5px;left:10px;width:calc(50% - 10px);height:calc(100% - 10px);background:var(--primary-gradient);border-radius:30px;transition:transform 0.4s cubic-bezier(0.65,0,0.35,1);box-shadow:var(--shadow-medium)}
#off:checked ~ .bar{transform:translateX(100%)}
.radio label[for="on"]{left:0}
.radio label[for="off"]{right:0}
.radio:has(#on:checked) label[for="on"]{color:#fff}
.radio:has(#on:checked) label[for="off"]{color:#666}
.radio:has(#off:checked) label[for="off"]{color:#fff}
.radio:has(#off:checked) label[for="on"]{color:#666}
.submit-container{display:flex;align-items:center;flex:0 0 auto}
.submit-btn{background:var(--primary-gradient);color:white;border:none;padding:22px 42px;font-size:1em;font-weight:600;border-radius:35px;cursor:pointer;transition:var(--transition);height:74px;display:flex;align-items:center;justify-content:center}
.submit-btn:hover{transform:translateY(-2px);box-shadow:var(--shadow-medium)}
.submit-copy{margin-top:30px}
.copy_warpper{width:100%;display:flex;flex-direction:column;align-items:center;justify-content:center}
#copytips{position:absolute;background:rgba(0,0,0,0.8);color:#fff;padding:8px 20px;border-radius:25px;font-size:15px;font-weight:600;pointer-events:none;opacity:0;display:none;z-index:9999;white-space:nowrap;transform:translate(-50%,0) scale(0.95);box-shadow:0 4px 10px rgba(0,0,0,0.3);transition:opacity 0.5s ease,transform 0.5s ease}
.loading{display:none;color:#666;font-style:italic;text-align:center}
.error{color:var(--error-color);background:#ffe6e6;padding:8px;border-radius:4px;margin:8px 0}
.success{color:var(--success-color);background:#e6ffe6;padding:8px;border-radius:4px;margin:8px 0}
.result{margin-top:30px;padding:10px 20px;font-family:"LiliFont";white-space:pre-wrap;word-break:break-all;font-size:2em;text-align:left}
.jumpresult{margin-top:30px;padding:10px 20px;white-space:pre-wrap;word-break:break-all;font-size:2em;text-align:left}
.domain-highlight{color:#fff;font-weight:700;background:linear-gradient(90deg,#23d5ab,#23a6d5);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-shadow:0 2px 10px rgba(35,213,171,0.3)}
footer{display:flex;flex-direction:column;align-items:center;gap:1.2rem;font-size:0.8rem;color:#444;text-align:center;margin:1em 0;transition:var(--transition)}
.footer-content{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:0.5rem}
.separator{color:#bbb;margin:0 0.5rem}
.separator::before{content:"|"}
.lang-switch{display:flex;align-items:center;gap:0.5rem;padding:0.2rem 0.5rem;border-radius:6px;color:#666;font-size:0.9rem;background:rgba(255,255,255,0.6);box-shadow:0 2px 8px rgba(0,0,0,0.05);border:1px solid rgba(0,0,0,0.05);transition:var(--transition)}
.lang-switch.active{background:#3498db;color:white}
.lang-switch:not(.active):hover{background:rgba(52,152,219,0.1);color:#3498db}
.github-icon{width:1.2em;height:1.2em;vertical-align:middle;fill:currentColor}
.jump{display: grid;place-items: center;}
.actions{margin-top: 30px;}
.footer{text-align:center;padding:1em;background:rgba(0,0,0,0.2);border-top:1px solid rgba(255,255,255,0.2)}
#themeToggle{background:transparent;border:none;color:var(--text-color);cursor:pointer;font-size:1.2em;padding:0 0.5rem;transition: transform 0.3s;}
#themeToggle:hover{transform: scale(1.2);}
    </style>

</head>

<body>
    <?php if (!$decoded_result): ?>
        
        <div class="page-section one" id="1">
            <div class="card">
                <h1>liliill.li</h1>
                <form id="encode-form">
                    <div class="row-container"><input type="text" id="encode-url" class="input-gradient" name="url"
                            placeholder="<?= htmlspecialchars($L['input_placeholder']) ?>"></div>
                    <div class="row-container">

                        <div class="switch-container">
                            <div class="radio">
                                <input type="radio" id="on" name="mode" value="binary" checked>
                                <label for="on"><?= htmlspecialchars($L['friendly']) ?></label>
                                <input type="radio" id="off" name="mode" value="octal">
                                <label for="off"><?= htmlspecialchars($L['shorter']) ?></label>
                                <div class="bar"></div>
                            </div>
                        </div>
                        <div class="submit-container">
                            <button class="submit-btn" type="submit"><?= htmlspecialchars($L['submit']) ?></button>
                        </div>
                    </div>
                </form>
                <div>
                    <div id="encode-loading" class="loading"><?= htmlspecialchars($L['encoding']) ?></div>
                    <div id="encode-error" class="error" style="display:none"></div>
                    <div id="encode-result" style="display:none">
                        <div id="encoded-output" class="result"></div>
                        <div class="copy_warpper">
                            <button class="submit-btn submit-copy" id="copy"><?= htmlspecialchars($L['copy']) ?></button>
                        </div>
                    </div>
                </div>
                <div id="copytips"></div>
            </div>
            <footer>
                <div class="footer-content">
                    <span>@</span>
                    <a target="_blank" href="https://liliill.li"><?= htmlspecialchars($L['title_description']) ?></a>
                    <span class="separator"></span>
                    <a href="#" class="lang-switch <?= $selected_lang === 'en' ? 'active' : '' ?>"
                        data-lang="en">English</a>
                    <span class="separator"></span>
                    <a href="#" class="lang-switch <?= $selected_lang === 'de' ? 'active' : '' ?>"
                        data-lang="de">Deutsch</a>
                    <span class="separator"></span>
                    <a href="#" class="lang-switch <?= $selected_lang === 'zh' ? 'active' : '' ?>" data-lang="zh">中文</a>
                    <span class="separator"></span>
                    <a target="_blank" href="https://github.com/dyniao/lili-url-conversion"><svg class="github-icon"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 98 96">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M48.854 0C21.839 0 0 22 0 49.217c0 21.756 13.993 40.172 33.405 46.69 2.427.49 3.316-1.059 3.316-2.362 0-1.141-.08-5.052-.08-9.127-13.59 2.934-16.42-5.867-16.42-5.867-2.184-5.704-5.42-7.17-5.42-7.17-4.448-3.015.324-3.015.324-3.015 4.934.326 7.523 5.052 7.523 5.052 4.367 7.496 11.404 5.378 14.235 4.074.404-3.178 1.699-5.378 3.074-6.6-10.839-1.141-22.243-5.378-22.243-24.283 0-5.378 1.94-9.778 5.014-13.2-.485-1.222-2.184-6.275.486-13.038 0 0 4.125-1.304 13.426 5.052a46.97 46.97 0 0 1 12.214-1.63c4.125 0 8.33.571 12.213 1.63 9.302-6.356 13.427-5.052 13.427-5.052 2.67 6.763.97 11.816.485 13.038 3.155 3.422 5.015 7.822 5.015 13.2 0 18.905-11.404 23.06-22.324 24.283 1.78 1.548 3.316 4.481 3.316 9.126 0 6.6-.08 11.897-.08 13.526 0 1.304.89 2.853 3.316 2.364 19.412-6.52 33.405-24.935 33.405-46.691C97.707 22 75.788 0 48.854 0z"
                                fill="#24292f" />
                        </svg></a>
                    <span class="separator"></span>
                    <button id="themeToggle">☀️</button>
                </div>
            </footer>
        </div>
        
        <?php else: ?>
            <div class="card jump one">
                <h1>liliill.li</h1>
                <?php if (!empty($decoded_result['error'])): ?>
                    <div class="error"><?= htmlspecialchars($L['decode_error'] . '：' . $decoded_result['error']) ?></div>
                <?php else: ?>
                    <label></label>
                    <div class="jumpresult"><?= htmlspecialchars($decoded_result['original']) ?></div>
                    <div class="actions">
                        <button id="jumpBtn" class="submit-btn"><?= htmlspecialchars($L['jump_now']) ?></button>
                    </div>
                    <script>
                        (function () {
                            let timer = 3, paused = false;
                            const url = <?= json_encode($decoded_result['original']) ?>;
                            const label = document.querySelector('.jump label');
                            const jumpBtn = document.getElementById('jumpBtn');
                            const update = () => label.textContent = <?= json_encode($L['redirect_wait']) ?>.replace('{sec}', timer);
                            const jump = () => window.location.href = url;
                            const pause = () => { if (paused) return; paused = true; label.textContent = <?= json_encode($L['redirect_paused']) ?>;['keydown', 'mousedown', 'click', 'contextmenu', 'auxclick'].forEach(e => document.removeEventListener(e, pause)); };
                            const countdown = () => { if (paused) return; if (timer > 0) { update(); timer--; setTimeout(countdown, 1000); } else jump(); };
                            jumpBtn.addEventListener('click', () => { pause(); jump(); });
                            ['keydown', 'mousedown', 'click', 'contextmenu', 'auxclick'].forEach(e => document.addEventListener(e, pause, { once: true }));
                            countdown();
                        })();
                    </script>
                <?php endif; ?>
            </div>
        <?php endif; ?>

<script>
function lili() {
    // DOM element references
    const elements = {
        themeToggle: document.getElementById('themeToggle'),
        encodeForm: document.getElementById('encode-form'),
        copyBtn: document.getElementById('copy'),
        langSwitches: document.querySelectorAll('.lang-switch[data-lang]')
    };

    // Initialize all features
    function init() {
        initTheme();
        initLanguageSwitch();
        initEncodeForm();
        initCopyButton();
    }

    // Theme toggle feature
    function initTheme() {
        if (!elements.themeToggle) return;

        const savedTheme = window.localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateButtonText(savedTheme);

        elements.themeToggle.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            window.localStorage.setItem('theme', newTheme);
            updateButtonText(newTheme);
        });

        function updateButtonText(theme) {
            elements.themeToggle.textContent = theme === 'dark' ? '☀️' : '🌙';
        }
    }

    // Multi-language switch feature
    function initLanguageSwitch() {
        if (!elements.langSwitches.length) return;

        elements.langSwitches.forEach(link => {
            link.addEventListener('click', async function(evt) {
                evt.preventDefault();
                const lang = link.getAttribute('data-lang');

                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=change_language&lang=${lang}`
                    });

                    const data = await response.json();

                    if (data.success) {
                        updatePageText(data.translations);
                        updateActiveLanguage(this);
                        document.documentElement.lang = lang;
                        document.title = data.translations.title;
                    }
                } catch (error) {
                    console.error('Language switch error:', error);
                }
            });
        });

        function updateActiveLanguage(activeElement) {
            elements.langSwitches.forEach(item => {
                item.classList.remove('active');
            });
            activeElement.classList.add('active');
        }
    }

    // Update page text based on translations
    function updatePageText(translations) {
        // Encode form text update
        if (elements.encodeForm) {
            const encodeUrl = document.getElementById('encode-url');
            const friendlyLabel = document.querySelector('label[for="on"]');
            const shorterLabel = document.querySelector('label[for="off"]');
            const submitBtn = document.querySelector('.submit-btn[type="submit"]');
            const encodeLoading = document.getElementById('encode-loading');

            if (encodeUrl) encodeUrl.placeholder = translations.input_placeholder;
            if (friendlyLabel) friendlyLabel.textContent = translations.friendly;
            if (shorterLabel) shorterLabel.textContent = translations.shorter;
            if (submitBtn) submitBtn.textContent = translations.submit;
            if (encodeLoading) encodeLoading.textContent = translations.encoding;
        }

        // Copy button text update
        if (elements.copyBtn) {
            elements.copyBtn.textContent = translations.copy;
        }

        // Jump button text update
        const jumpBtn = document.getElementById('jumpBtn');
        if (jumpBtn) {
            jumpBtn.textContent = translations.jump_now;
        }

        // Error message text update
        updateErrorMessages(translations);

        // Footer link text update
        const titleDescriptionLink = document.querySelector('footer a[target="_blank"]');
        if (titleDescriptionLink && titleDescriptionLink.href.includes('liliill.li')) {
            titleDescriptionLink.textContent = translations.title_description;
        }
    }

    function updateErrorMessages(translations) {
        const errorElements = document.querySelectorAll('.error');
        errorElements.forEach(el => {
            const text = el.textContent.trim();
            if (text.includes('URL cannot be empty') || 
                text.includes('URL不能为空') || 
                text.includes('URL darf nicht leer sein')) {
                el.textContent = translations.url_empty;
            } else if (text.includes('Decoding Error') || 
                       text.includes('解码错误') || 
                       text.includes('Dekodierungsfehler')) {
                el.textContent = translations.decode_error;
            } else if (text.includes('错误: ') || 
                       text.includes('Error: ') || 
                       text.includes('Fehler: ')) {
                el.textContent = text.replace(/错误: |Error: |Fehler: /, translations.js_error);
            }
        });
    }

    // URL encode form feature
    function initEncodeForm() {
        if (!elements.encodeForm) return;

        const loading = document.getElementById('encode-loading');
        const errBox = document.getElementById('encode-error');
        const resultBox = document.getElementById('encode-result');
        const output = document.getElementById('encoded-output');

        elements.encodeForm.addEventListener('submit', async e => {
            e.preventDefault();
            
            const url = document.getElementById('encode-url').value.trim();
            const mode = document.querySelector('input[name="mode"]:checked').value;
            
            if (!url) {
                showError("<?= $L['url_empty'] ?>");
                return;
            }

            toggleElement(loading, true);
            toggleElement(errBox, false);
            toggleElement(resultBox, false);

            try {
                const formData = new FormData();
                formData.append('action', 'encode');
                formData.append('url', url);
                formData.append('mode', mode);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                toggleElement(loading, false);

                if (data.success) {
                    output.textContent = data.encoded;
                    toggleElement(resultBox, true);
                } else {
                    showError(data.error);
                }
            } catch (err) {
                toggleElement(loading, false);
                showError("<?= $L['js_error'] ?>" + err.message);
            }
        });

        function showError(msg) {
            errBox.textContent = msg;
            toggleElement(errBox, true);
        }
    }

    // Copy button feature
    function initCopyButton() {
        if (!elements.copyBtn) return;

        elements.copyBtn.addEventListener('click', async () => {
            const output = document.getElementById('encoded-output');
            if (!output) return;

            try {
                await navigator.clipboard.writeText(output.textContent);
                showCopyTip("<?= $L['copied'] ?>");
            } catch (err) {
                showCopyTip("<?= $L['copy_failed'] ?>");
            }
        });
    }

    // Utility functions
    function toggleElement(element, show) {
        if (element) {
            element.style.display = show ? 'block' : 'none';
        }
    }

    function showCopyTip(msg) {
        const tip = document.getElementById('copytips');
        if (!tip || !elements.copyBtn) return;

        const rect = elements.copyBtn.getBoundingClientRect();
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const scrollLeft = window.scrollX || document.documentElement.scrollLeft;

        tip.textContent = msg;
        tip.style.display = 'block';
        tip.style.left = `${rect.left + rect.width / 2 + scrollLeft}px`;
        tip.style.top = `${rect.top - 30 + scrollTop}px`;
        tip.style.opacity = '1';
        tip.style.transform = 'translate(-50%,0) scale(1)';

        setTimeout(() => {
            tip.style.opacity = '0';
            tip.style.transform = 'translate(-50%,-20%) scale(0.95)';
            setTimeout(() => {
                tip.style.display = 'none';
            }, 600);
        }, 1500);
    }

    // Start initialization
    document.addEventListener('DOMContentLoaded', init);
}

// Execute lili function
lili();

</script>
</body>

</html>

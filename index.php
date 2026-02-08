<?php
// index.php
// Two-column viewer: left = sidebar (dynamic), right = iframe viewer

// Serve file source as JSON when requested (prevents exposing arbitrary files)
if (isset($_GET['source']) && isset($_GET['path'])) {
    $path = $_GET['path'];
    $decoded = urldecode($path);
    // echo "Requested source path: " . htmlspecialchars($decoded) . "\n"; // Debug log
    $root = realpath(__DIR__);
    $full = realpath($root . DIRECTORY_SEPARATOR . $decoded);
    // Security check: ensure the resolved path is within the root directory
    if ($full === false || strpos($full, $root) !== 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid path']);
        exit;
    }
    if (!is_file($full)) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    $content = file_get_contents($full);
    header('Content-Type: application/json');
    echo json_encode(['path' => $decoded, 'content' => $content]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars(basename(__DIR__)); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-4.0.0.min.js" integrity="sha256-OaVG6prZf4v69dPg6PhVattBXkcOWQB62pdZ3ORyrao=" crossorigin="anonymous"></script>
    <!-- Highlight.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/github.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js"></script>
    <script>hljs.configure({ignoreUnescapedHTML: true});</script>
    <style>
        html,
        body {
            height: 100%;
        }

        .viewer-iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }

        .sidebar {
            height: 100vh;
            overflow: auto;
            padding: 1rem;
            border-right: 1px solid #e9ecef
        }

        .file-list a {
            display: block;
            padding: 0.15rem 0;
            color: #0d6efd;
            text-decoration: none
        }

        .file-list a:hover {
            text-decoration: underline
        }

        .folder-name {
            font-weight: 600;
            margin-top: 0.5rem
        }

        code {
            color: black;
            background-color: #d2d2d2;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
        }

        .hljs {
            border-radius: 10px;
            background-color: #eeeeee !important;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <div class="col-4 col-md-2 sidebar bg-light">
                <h5><code><?php echo htmlspecialchars(basename(__DIR__)); ?></code></h5>
                <div class="file-list">
                    <?php
                    // Root directory to scan (this file's directory)
                    $root = __DIR__;

                    // Helper: build web-safe URL path relative to this script
                    function url_path($fullPath, $rootDir)
                    {
                        $rel = substr($fullPath, strlen($rootDir) + 1);
                        $parts = preg_split('#[\\/]#', $rel);
                        $enc = array_map('rawurlencode', $parts);
                        return implode('/', $enc);
                    }

                    // Files to show (extensions)
                    $allowedExt = ['html', 'htm', 'php', 'css', 'js'];

                    function list_dir($dir, $rootDir, $allowedExt)
                    {
                        $items = scandir($dir);
                        if ($items === false) return;
                        echo "<ul class=\"list-unstyled\">\n";
                        foreach ($items as $item) {
                            if ($item === '.' || $item === '..') continue;
                            if (strpos($item, '.') === 0) continue; // skip hidden
                            $full = $dir . DIRECTORY_SEPARATOR . $item;
                            if (is_dir($full)) {
                                echo '<li class="mt-2">';
                                echo '<div class="folder-name"><code>' . htmlspecialchars($item) . '</code></div>';
                                list_dir($full, $rootDir, $allowedExt);
                                echo '</li>';
                            } else {
                                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                                if (!in_array($ext, $allowedExt)) continue;
                                // Skip the demo viewer itself
                                if (realpath($full) === realpath(__FILE__)) continue;
                                $href = url_path($full, $rootDir);
                                $display = htmlspecialchars($item);
                                echo '<li><a href="' . $href . '" target="viewerFrame">' . $display . '</a></li>';
                            }
                        }
                        echo "</ul>\n";
                    }

                    // Start listing from current directory
                    list_dir($root, $root, $allowedExt);
                    ?>
                </div>
            </div>

            <div class="col-8 col-md-9 p-0" style="height:100vh;">
                <iframe name="viewerFrame" class="viewer-iframe"></iframe>
            </div>
        </div>
    </div>

    <!-- Bottom source viewer trigger -->
    <button id="openSourceBtn" class="btn btn-primary position-fixed" style="right:1rem;bottom:1rem;z-index:1100;">View Source</button>

    <!-- Offcanvas (bottom) for source -->
    <div class="offcanvas offcanvas-bottom" tabindex="-1" id="sourceOffcanvas" aria-labelledby="sourceOffcanvasLabel" style="height:40vh;border-radius: 10px 10px 0 0;">
      <div class="offcanvas-header">
        <h5 id="sourceOffcanvasLabel">Source: <code id="sourcePath">(none)</code></h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <pre style="max-height:90%;overflow:auto;"><code id="sourceCode" ></code></pre>
      </div>
    </div>

    <script>
    (function(){
        const openBtn = document.getElementById('openSourceBtn');
        const offcanvasEl = document.getElementById('sourceOffcanvas');
        const sourcePathEl = document.getElementById('sourcePath');
        const sourceCodeEl = document.getElementById('sourceCode');
        const bsOff = new bootstrap.Offcanvas(offcanvasEl);

        function getIframePath() {
            const iframe = document.querySelector('iframe[name="viewerFrame"]');
            if (!iframe) return null;
            // Prefer the iframe's src attribute or property
            let src = iframe.getAttribute('src') || iframe.src || '';
            // Try to read the iframe's actual location if same-origin and navigated
            try { if (iframe.contentWindow && iframe.contentWindow.location && iframe.contentWindow.location.href) src = iframe.contentWindow.location.href || src; } catch (e) {}
            // If srcdoc is used or empty, no path
            if (iframe.hasAttribute('srcdoc') && (!src || src.trim() === '')) return null;
            try {
                const url = new URL(src, window.location.href);
                let path = url.pathname || '';

                // Compute base path (directory of this script), keep trailing slash
                const locPath = window.location.pathname || '/';
                const base = locPath.replace(/\/[^\/]*$/, '/');

                // If path starts with base, strip it to get repo-relative path
                if (path.startsWith(base)) {
                    path = path.slice(base.length);
                } else {
                    // Remove leading slash if present
                    if (path.startsWith('/')) path = path.slice(1);
                    // If path starts with the first folder segment of this app, strip that segment
                    const firstSeg = base.split('/').filter(Boolean)[0] || '';
                    if (firstSeg && path.startsWith(firstSeg + '/')) {
                        path = path.slice(firstSeg.length + 1);
                    }
                }

                return path ? decodeURIComponent(path) : null;
            } catch (e) {
                return null;
            }
        }

        async function loadSource() {
            const p = getIframePath();
            if (!p) {
                sourcePathEl.textContent = '(no file)';
                sourceCodeEl.textContent = 'No source available for this view.';
                hljs.highlightElement(sourceCodeEl);
                return;
            }
            sourcePathEl.textContent = p;
            try {
                const res = await fetch(window.location.pathname + '?source=1&path=' + encodeURIComponent(p), {credentials: 'same-origin'});
                if (!res.ok) throw new Error('Fetch failed ' + res.status);
                const json = await res.json();
                sourceCodeEl.getAttribute('data-highlighted') && sourceCodeEl.removeAttribute('data-highlighted');
                sourceCodeEl.className = 'language-' + (p.split('.').pop() || 'txt');
                sourceCodeEl.textContent = json.content;
                hljs.highlightElement(sourceCodeEl);
            } catch (err) {
                sourceCodeEl.textContent = 'Error loading source: ' + err.message;
                hljs.highlightElement(sourceCodeEl);
            }
        }

        // Open button shows offcanvas and loads source
        openBtn.addEventListener('click', function(){
            bsOff.show();
            // small timeout to ensure offcanvas is visible
            setTimeout(loadSource, 100);
        });

        // Also reload when offcanvas is shown (in case iframe changed)
        offcanvasEl.addEventListener('shown.bs.offcanvas', loadSource);
    })();
    </script>

</body>

</html>
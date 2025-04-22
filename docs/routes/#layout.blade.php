<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable-dynamic-subset.min.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/styles/default.min.css" />
    <link rel="stylesheet" href="/assets/css/app.css" />
    <link rel="stylesheet" href="/assets/css/markdown.css" />
</head>
<body>
    <header class="header">
        <div class="container">
            <a class="header__brand" href="/">
                ☀ statimate
            </a>
            <nav class="header__nav">
                <a href="/docs">Docs</a>
            </nav>
        </div>
    </header>
    <main>
        <div class="container">
            {!! $content !!}
        </div>
    </main>
    <footer class="footer">
        <div class="container">
            &copy; 2025 headercat.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/highlight.min.js"></script>
    <script src="/assets/js/app.js"></script>
</body>
</html>

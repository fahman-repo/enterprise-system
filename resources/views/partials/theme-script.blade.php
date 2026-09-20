<script>
    (function () {
        try {
            var stored = localStorage.getItem('theme');
            var isDark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
        } catch (error) {
            // Ignore storage access errors and fall back to the default theme.
        }
    })();
</script>
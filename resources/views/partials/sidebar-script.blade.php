<script>
    (function () {
        try {
            var collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            document.documentElement.setAttribute('data-sidebar-collapsed', collapsed ? 'true' : 'false');
        } catch (error) {
            document.documentElement.setAttribute('data-sidebar-collapsed', 'false');
        }
    })();
</script>

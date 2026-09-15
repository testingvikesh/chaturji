{{-- Soft block: right-click / context menu on student & teacher panels --}}
<script>
(function () {
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
    }, { capture: true });
})();
</script>

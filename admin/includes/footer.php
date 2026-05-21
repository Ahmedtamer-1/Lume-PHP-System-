
        </div><!-- /.admin-content -->
    </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<!-- Scroll to top button -->
<button class="admin-scroll-top" aria-label="Scroll to top">
    <svg viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
</button>

<!-- Toast container -->
<div class="admin-toast-container"></div>

<!-- Global Admin JS -->
<script>
window.BASE_URL = '<?= SITE_URL ?>';
</script>
<script src="<?= SITE_URL ?>/admin/assets/js/admin.js?v=<?= time() ?>"></script>
</body>
</html>

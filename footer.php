  </main>
  <?php if (is_logged_in()): ?>
    <nav class="mobile-tabbar" aria-label="Mobile navigation">
      <a href="index.php"><span class="tab-ico">⌂</span><span>Home</span></a>
      <a href="add.php"><span class="tab-ico">＋</span><span>Add</span></a>
      <a href="options.php"><span class="tab-ico">⚙</span><span>Options</span></a>
      <a href="account.php"><span class="tab-ico">◉</span><span>Account</span></a>
      <a href="version.php"><span class="tab-ico">ⓘ</span><span>Version</span></a>
    </nav>
  <?php endif; ?>
  <footer class="text-center text-xs" style="color:var(--muted); padding:2.5rem 1rem;">
    Netventory v<?= h($APP_VERSION) ?> by EmechNET · <?= date('Y') ?>
  </footer>
  <script defer src="/assets/main.js?v=20"></script>
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js').catch(() => {}));
    }
  </script>
</body>
</html>

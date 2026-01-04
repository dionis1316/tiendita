<?php if (!empty($_SESSION['flash_ok'])): ?>
  <div class="alert ok"><?= htmlspecialchars($_SESSION['flash_ok']) ?></div>
  <?php unset($_SESSION['flash_ok']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
  <?php unset($_SESSION['flash_error']); endif; ?>
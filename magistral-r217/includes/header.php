<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="nav">
  <div class="nav-inner">
    <a href="index.php" class="nav-logo">Магистраль <span>Р-217</span></a>
    <ul class="nav-links">
      <li><a href="index.php" <?= $current === 'index.php' ? 'class="active"' : '' ?>>Главная</a></li>
      <li><a href="assortment.php" <?= $current === 'assortment.php' ? 'class="active"' : '' ?>>Ассортимент</a></li>
      <li><a href="about.php" <?= $current === 'about.php' ? 'class="active"' : '' ?>>О нас</a></li>
      <li><a href="contacts.php" <?= $current === 'contacts.php' ? 'class="active"' : '' ?>>Контакты</a></li>
    </ul>
    <button class="nav-burger" aria-label="Меню">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>
<div class="nav-mobile">
  <a href="index.php">Главная</a>
  <a href="assortment.php">Ассортимент</a>
  <a href="about.php">О нас</a>
  <a href="contacts.php">Контакты</a>
</div>

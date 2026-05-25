<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ассортимент — Магистраль Р-217</title>
  <meta name="description" content="Каталог продукции ООО «Магистраль Р-217».">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-body">
<?php include 'includes/header.php'; ?>

<section class="page-hero">
  <div class="container">
    <h1>Ассортимент</h1>
    <p>Широкий выбор материалов и решений для строительства любого масштаба</p>
  </div>
</section>

<section class="products-section">
  <div class="container">
    <?php
    $productsFile = __DIR__ . '/data/products.json';
    $products = file_exists($productsFile) ? json_decode(file_get_contents($productsFile), true) : [];
    if (!is_array($products)) $products = [];
    $categories = array_values(array_unique(array_filter(array_column($products, 'category'))));
    sort($categories);
    ?>
    <?php if (!empty($categories)): ?>
    <div class="products-filter" id="filter-bar">
      <button class="filter-btn active" data-cat="all">Все</button>
      <?php foreach ($categories as $cat): ?>
      <button class="filter-btn" data-cat="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if (empty($products)): ?>
    <div class="products-empty">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 3H8a2 2 0 0 0-2 2v2h12V5a2 2 0 0 0-2-2z"/></svg>
      <p>Каталог пока пополняется. Загляните позже или позвоните нам.</p>
    </div>
    <?php else: ?>
    <div class="products-grid" id="products-grid">
      <?php foreach ($products as $product): ?>
      <div class="product-card fade-up" data-cat="<?= htmlspecialchars($product['category'] ?? '') ?>">
        <?php if (!empty($product['image'])): ?>
          <img class="product-card-img" src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        <?php else: ?>
          <div class="product-card-img-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div>
        <?php endif; ?>
        <div class="product-card-body">
          <?php if (!empty($product['category'])): ?><div class="product-card-category"><?= htmlspecialchars($product['category']) ?></div><?php endif; ?>
          <h4><?= htmlspecialchars($product['name']) ?></h4>
          <?php if (!empty($product['specs'])): ?><div class="product-card-specs"><?= nl2br(htmlspecialchars($product['specs'])) ?></div><?php endif; ?>
          <div class="product-card-price"><?= !empty($product['price']) ? htmlspecialchars($product['price']) : 'Уточняйте у менеджера' ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="cta-section compact">
  <div class="container fade-up">
    <h2>Не нашли нужную позицию?</h2>
    <p>Свяжитесь с нашими менеджерами — подберём решение под ваш запрос.</p>
    <a href="contacts.php" class="btn btn-primary">Связаться</a>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="js/main.js"></script>
<script>
  const filterBar = document.getElementById('filter-bar');
  if (filterBar) {
    filterBar.addEventListener('click', e => {
      const btn = e.target.closest('.filter-btn');
      if (!btn) return;
      filterBar.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const cat = btn.dataset.cat;
      document.querySelectorAll('.product-card').forEach(card => {
        card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
      });
    });
  }
</script>
</body>
</html>

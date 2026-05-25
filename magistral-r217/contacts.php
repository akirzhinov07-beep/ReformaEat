<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Контакты — Магистраль Р-217</title>
  <meta name="description" content="Контакты ООО «Магистраль Р-217» — телефон, email, адрес и реквизиты компании.">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-body">
<?php include 'includes/header.php'; ?>

<section class="page-hero">
  <div class="container"><h1>Контакты</h1><p>Свяжитесь с нами любым удобным способом</p></div>
</section>

<section class="contacts-section">
  <div class="container">
    <div class="contacts-grid">
      <div class="contact-group fade-up">
        <h3>Как с нами связаться</h3>
        <div class="contact-row"><div class="contact-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.59 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div><div><div class="contact-label">Телефон</div><div class="contact-value"><a href="tel:+74951234567">+7 (495) 123-45-67</a></div></div></div>
        <div class="contact-row"><div class="contact-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div><div><div class="contact-label">Email</div><div class="contact-value"><a href="mailto:info@magistral-r217.ru">info@magistral-r217.ru</a></div></div></div>
        <div class="contact-row"><div class="contact-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div><div><div class="contact-label">Адрес офиса</div><div class="contact-value">г. Москва, ул. Примерная, д. 1, офис 101</div></div></div>
        <div class="contact-row"><div class="contact-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg></div><div><div class="contact-label">Режим работы</div><div class="contact-value">Пн–Пт: 9:00 — 18:00</div></div></div>
      </div>
      <div class="fade-up fade-up-delay-2">
        <div class="requisites-box">
          <h3 style="margin-bottom:24px;">Реквизиты компании</h3>
          <div class="requisite-item"><div class="requisite-label">Полное наименование</div><div class="requisite-value">Общество с ограниченной ответственностью «Магистраль Р-217»</div></div>
          <div class="requisite-item"><div class="requisite-label">ИНН</div><div class="requisite-value">7701234567</div></div>
          <div class="requisite-item"><div class="requisite-label">КПП</div><div class="requisite-value">770101001</div></div>
          <div class="requisite-item"><div class="requisite-label">ОГРН</div><div class="requisite-value">1127746000001</div></div>
          <div class="requisite-item"><div class="requisite-label">Юридический адрес</div><div class="requisite-value">123456, г. Москва, ул. Примерная, д. 1</div></div>
          <div class="requisite-item"><div class="requisite-label">Расчётный счёт</div><div class="requisite-value">40702810000000000001</div></div>
          <div class="requisite-item"><div class="requisite-label">Банк</div><div class="requisite-value">АО «Сбербанк России», г. Москва</div></div>
          <div class="requisite-item"><div class="requisite-label">БИК</div><div class="requisite-value">044525225</div></div>
          <div class="requisite-item"><div class="requisite-label">Корреспондентский счёт</div><div class="requisite-value">30101810400000000225</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section style="background:var(--bg-alt);padding:80px 0;">
  <div class="container">
    <div class="section-header fade-up" style="max-width:520px;"><span class="section-eyebrow">Напишите нам</span><h2>Оставьте заявку</h2><p>Опишите вашу задачу, и наш менеджер свяжется с вами в течение одного рабочего дня.</p></div>
    <form class="fade-up" style="max-width:560px;margin:0 auto;display:flex;flex-direction:column;gap:16px;" action="send-form.php" method="POST">
      <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group"><label class="form-label">Имя *</label><input class="form-input" type="text" name="name" required placeholder="Иван Иванов"></div>
        <div class="form-group"><label class="form-label">Телефон *</label><input class="form-input" type="tel" name="phone" required placeholder="+7 (___) ___-__-__"></div>
      </div>
      <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" placeholder="mail@example.ru"></div>
      <div class="form-group"><label class="form-label">Сообщение</label><textarea class="form-textarea" name="message" placeholder="Опишите ваш запрос..." rows="4"></textarea></div>
      <button type="submit" class="btn btn-primary" style="align-self:flex-start;">Отправить заявку</button>
    </form>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="js/main.js"></script>
<script>
  const phoneInput = document.querySelector('input[name="phone"]');
  if (phoneInput) {
    phoneInput.addEventListener('input', function () {
      let v = this.value.replace(/\D/g, '');
      if (v.startsWith('8')) v = '7' + v.slice(1);
      if (!v.startsWith('7') && v.length > 0) v = '7' + v;
      let f = '+7';
      if (v.length > 1) f += ' (' + v.slice(1, 4);
      if (v.length >= 4) f += ') ' + v.slice(4, 7);
      if (v.length >= 7) f += '-' + v.slice(7, 9);
      if (v.length >= 9) f += '-' + v.slice(9, 11);
      this.value = f;
    });
  }
</script>
</body>
</html>

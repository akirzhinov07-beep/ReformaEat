/**
 * reForma eat — Orders Web App (Google Apps Script)
 *
 * ИНСТРУКЦИЯ:
 * 1. Открой свою Google Таблицу (ту же, где меню)
 * 2. Расширения → Apps Script
 * 3. Удали весь код и вставь этот файл целиком
 * 4. Нажми «Развернуть» → «Новое развёртывание»
 *    - Тип: Веб-приложение
 *    - Выполнять от имени: Я (своего аккаунта)
 *    - Доступ: Все (анонимный)
 * 5. Разреши доступ (выбери свой аккаунт Google)
 * 6. Скопируй URL развёртывания — вставь его в:
 *    - index.html → переменная APPS_SCRIPT_URL
 *    - admin.html → поле «URL Apps Script» в настройках
 */

const ORDERS_SHEET  = 'Заказы';
const ADMIN_TOKEN   = 'rf_admin_2025_secret';

// ─── ПРИЁМ ЗАКАЗА (POST) ──────────────────────────────────────────────────
function doPost(e) {
  try {
    const data = JSON.parse(e.postData.contents);
    const ss   = SpreadsheetApp.getActiveSpreadsheet();
    let sheet  = ss.getSheetByName(ORDERS_SHEET);

    // Создать лист «Заказы» при первом заказе
    if (!sheet) {
      sheet = ss.insertSheet(ORDERS_SHEET);
      const hdr = ['ID','Дата заказа','Режим','Имя','Телефон',
                   'Дата доставки','План','Город','Улица','Дом','Кв',
                   'Промокод','Скидка ₽','Сумма ₽','Блюда','Комментарий'];
      sheet.appendRow(hdr);
      sheet.setFrozenRows(1);
      sheet.getRange(1,1,1,hdr.length).setFontWeight('bold').setBackground('#f5f3ef');
    }

    // Сформировать строку блюд
    const mode = data.mode || '1day';
    let dishesStr = '';
    if (mode === '7day' && Array.isArray(data.days)) {
      dishesStr = data.days.map(d =>
        d.date + ': ' + Object.entries(d.dishes || {}).map(([k,v]) => k+'='+v).join(', ')
      ).join(' | ');
    } else {
      dishesStr = Object.entries(data.dishes || {}).map(([k,v]) => k+': '+v).join(', ');
    }

    sheet.appendRow([
      data.id          || Date.now(),
      data.date        || new Date().toISOString(),
      mode === '7day'  ? '7 дней' : '1 день',
      data.name        || '',
      data.phone       || '',
      data.deliveryDate|| '',
      data.plan        || '',
      data.city        || '',
      data.street      || '',
      data.house       || '',
      data.apt         || '',
      data.promo       || '',
      data.discount    || 0,
      data.totalPrice  || '',
      dishesStr,
      data.comment     || ''
    ]);

    return respond({ ok: true });
  } catch (err) {
    return respond({ ok: false, error: err.toString() });
  }
}

// ─── ВЫДАЧА ЗАКАЗОВ ДЛЯ АДМИНКИ (GET) ────────────────────────────────────
function doGet(e) {
  try {
    if ((e.parameter.token || '') !== ADMIN_TOKEN) {
      return respond({ ok: false, error: 'unauthorized' });
    }
    const ss    = SpreadsheetApp.getActiveSpreadsheet();
    const sheet = ss.getSheetByName(ORDERS_SHEET);
    if (!sheet || sheet.getLastRow() <= 1) {
      return respond({ ok: true, orders: [] });
    }
    const headers = sheet.getRange(1,1,1,sheet.getLastColumn()).getValues()[0];
    const rows    = sheet.getRange(2,1,sheet.getLastRow()-1,sheet.getLastColumn()).getValues();
    const orders  = rows.reverse().map(row => {
      const obj = {};
      headers.forEach((h,i) => { obj[h] = row[i]; });
      return obj;
    });
    return respond({ ok: true, orders });
  } catch (err) {
    return respond({ ok: false, error: err.toString() });
  }
}

function respond(data) {
  return ContentService
    .createTextOutput(JSON.stringify(data))
    .setMimeType(ContentService.MimeType.JSON);
}

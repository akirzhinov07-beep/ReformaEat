/**
 * reForma eat — Orders Web App (Google Apps Script)
 *
 * ИНСТРУКЦИЯ:
 * 1. Открой свою Google Таблицу (ту же, где меню)
 * 2. Расширения → Apps Script
 * 3. Удали весь код и вставь этот файл целиком
 * 4. Нажми «Развернуть» → «Управление развёртываниями»
 *    - Выбери существующее, нажми карандаш (редактировать)
 *    - Версия → «Новая версия» → «Развернуть»
 *    URL остаётся прежним!
 * 5. Если развёртывания ещё нет — «Новое развёртывание»:
 *    - Тип: Веб-приложение
 *    - Выполнять от имени: Я (своего аккаунта)
 *    - Доступ: Все (анонимный)
 */

const ORDERS_SHEET  = 'Заказы';
const COUNTER_SHEET = 'Счётчик';
const ADMIN_TOKEN   = 'rf_admin_2025_secret';

// ─── АТОМАРНЫЙ СЧЁТЧИК ЗАКАЗОВ ────────────────────────────────────────────
function getNextOrderNum() {
  const lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    const ss = SpreadsheetApp.getActiveSpreadsheet();
    let sheet = ss.getSheetByName(COUNTER_SHEET);
    if (!sheet) {
      sheet = ss.insertSheet(COUNTER_SHEET);
      sheet.getRange(1, 1).setValue(0);
    }
    const next = (sheet.getRange(1, 1).getValue() || 0) + 1;
    sheet.getRange(1, 1).setValue(next);
    return String(next).padStart(3, '0');
  } finally {
    lock.releaseLock();
  }
}

// ─── ПРИЁМ ЗАКАЗА (POST) ──────────────────────────────────────────────────
function doPost(e) {
  try {
    const data = JSON.parse(e.postData.contents);

    // Администратор запрашивает список заказов через POST (без кеша)
    if (data.action === 'getOrders') {
      if ((data.token || '') !== ADMIN_TOKEN) {
        return respond({ ok: false, error: 'unauthorized' });
      }
      return getOrdersList();
    }

    // Создание нового заказа
    const ss   = SpreadsheetApp.getActiveSpreadsheet();
    let sheet  = ss.getSheetByName(ORDERS_SHEET);

    // Создать лист «Заказы» при первом заказе
    if (!sheet) {
      sheet = ss.insertSheet(ORDERS_SHEET);
      const hdr = ['№','Дата заказа','Режим','Имя','Телефон',
                   'Дата доставки','План','Город','Улица','Дом','Кв',
                   'Промокод','Скидка ₽','Сумма ₽','Блюда','Комментарий'];
      sheet.appendRow(hdr);
      sheet.setFrozenRows(1);
      sheet.getRange(1,1,1,hdr.length).setFontWeight('bold').setBackground('#f5f3ef');
    }

    const orderNum = getNextOrderNum();

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
      orderNum,
      data.date        || new Date().toISOString(),
      mode === '7day'  ? '7 дней' : '1 день',
      data.name        || '',
      '',                              // phone — заполним ниже как текст
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

    // Сохраняем телефон как текст (иначе +7... воспринимается как формула)
    const phoneCell = sheet.getRange(sheet.getLastRow(), 5);
    phoneCell.setNumberFormat('@').setValue(data.phone || '');

    return respond({ ok: true, orderNum: orderNum });
  } catch (err) {
    return respond({ ok: false, error: err.toString() });
  }
}

// ─── ЧТЕНИЕ ВСЕХ ЗАКАЗОВ ИЗ ТАБЛИЦЫ ─────────────────────────────────────
function getOrdersList() {
  const ss    = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(ORDERS_SHEET);
  if (!sheet) return respond({ ok: true, orders: [] });

  // getDataRange() надёжнее getLastRow() — читает весь заполненный диапазон
  const all = sheet.getDataRange().getValues();
  if (all.length <= 1) return respond({ ok: true, orders: [] });

  const headers = all[0];
  const orders  = all.slice(1).reverse().map(function(row) {
    const obj = {};
    headers.forEach(function(h, i) { obj[h] = row[i]; });
    return obj;
  });
  return respond({ ok: true, orders: orders });
}

// ─── ВЫДАЧА ЗАКАЗОВ ДЛЯ АДМИНКИ (GET) ────────────────────────────────────
// Оставлен для совместимости. Используй POST + action:'getOrders' для свежих данных.
function doGet(e) {
  try {
    if ((e.parameter.token || '') !== ADMIN_TOKEN) {
      return respond({ ok: false, error: 'unauthorized' });
    }
    return getOrdersList();
  } catch (err) {
    return respond({ ok: false, error: err.toString() });
  }
}

function respond(data) {
  return ContentService
    .createTextOutput(JSON.stringify(data))
    .setMimeType(ContentService.MimeType.JSON);
}

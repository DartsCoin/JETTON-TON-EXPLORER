<?php
// Подключение массива переводов
$translations = require 'ПУТЬ_К_ВАШИМ_ПЕРЕВОДАМ_translations/translations.php';

// Установка языка по умолчанию и проверка
$lang = $_GET['lang'] ?? 'ru';

if (!isset($translations[$lang])) {
    $lang = 'ru';
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
<meta charset="UTF-8" />
<title data-translate="title">
<?php echo htmlspecialchars($translations[$lang]['title']); ?>
</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta property="og:title" content="Исследователь токена $DRTS Darts Coin" />
<meta property="og:description" content="Исследователь транзакций токена DRTS (Darts Coin) в сети TON" />
<link rel="stylesheet" href="ПУТЬ_К_ВАШИМ_СТИЛЯМ/transactions.css" />
<link rel="stylesheet" href="ПУТЬ_К СТИЛЯМ/font-awesome.min.css">

<script>
const translations = <?php echo json_encode($translations); ?>;
let currentLang = '<?php echo htmlspecialchars($lang); ?>';

function translatePage() {
  document.querySelectorAll("[data-translate]").forEach(el => {
    const key = el.getAttribute("data-translate");
    if (translations[currentLang] && translations[currentLang][key]) {
      el.innerText = translations[currentLang][key];
    }
  });
}

function updateActiveButton() {
  document.querySelectorAll('.lang-btn').forEach(btn => btn.classList.remove('active'));
  if (currentLang === 'ru') {
    document.getElementById('ruBtn').classList.add('active');
  } else {
    document.getElementById('enBtn').classList.add('active');
  }
}

window.onload = () => {
  translatePage();
  updateActiveButton();
};

function changeLanguage(lang) {
  const params = new URLSearchParams(window.location.search);
  params.set('lang', lang);
  window.location.search = params.toString();
}

window.onload = () => {
  translatePage();
};
</script>

<script>
function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    showCopyNotification();
  }, () => {
    alert('Не удалось скопировать');
  });
}

function showCopyNotification() {
  const notice = document.getElementById('copyNotice');
  notice.classList.add('show');
  setTimeout(() => {
    notice.classList.remove('show');
  }, 2000);
}

function toggleTheme() {
  const root = document.documentElement;
  const currentBg = getComputedStyle(root).getPropertyValue('--bg-color').trim();
  if (currentBg === '#1e1e1e') {
    // светлая тема
    root.style.setProperty('--bg-color', '#f4f4f4');
    root.style.setProperty('--text-color', '#333');
    root.style.setProperty('--form-bg', '#fff');
    root.style.setProperty('--border-color', '#ccc');
    root.style.setProperty('--hover-bg', '#e0e0e0');
    root.style.setProperty('--header-color', '#2c3e50');
    root.style.setProperty('--button-bg', '#2980b9');
    root.style.setProperty('--button-hover', '#3498db');
  } else {
    // тёмная тема
    root.style.setProperty('--bg-color', '#1e1e1e');
    root.style.setProperty('--text-color', '#ddd');
    root.style.setProperty('--form-bg', '#2c2c2c');
    root.style.setProperty('--border-color', '#555');
    root.style.setProperty('--hover-bg', '#444');
    root.style.setProperty('--header-color', '#fff');
    root.style.setProperty('--button-bg', '#2980b9');
    root.style.setProperty('--button-hover', '#3498db');
  }
}
</script>
</head>
<body>

<!-- Переключатели языка -->
<div class="language-switcher">
  <button class="lang-btn" id="ruBtn" onclick="changeLanguage('ru')"><?php echo htmlspecialchars($translations[$lang]['select_language']); ?> (Русский)</button>
  <button class="lang-btn" id="enBtn" onclick="changeLanguage('en')"><?php echo htmlspecialchars($translations[$lang]['select_language']); ?> (English)</button>
</div>

<!-- Заголовок -->
<h1 class="logo-header">
  <img src="ВАШ_ПУТЬ/geckoterminal_favicon.png" alt="logo" />
  $DRTS Explorer
</h1>
<button id="theme-toggle" onclick="toggleTheme()" data-translate="toggle_theme"><?php echo htmlspecialchars($translations[$lang]['toggle_theme']); ?></button>

<!-- Форма поиска -->
<form method="post" action="">
  <label for="address" data-translate="address_label"><?php echo htmlspecialchars($translations[$lang]['address_label']); ?></label>
  <input type="text" id="address" name="address" required />
  <input type="submit" value="<?php echo htmlspecialchars($translations[$lang]['search_button']); ?>" />
</form>

<div id="copyNotice">Скопировано!</div>

<?php
// Подключение файла транзакций
require 'transactions/transactions.php';
?>

<!-- Футер -->
<footer>
  <div class="footer-item">
    <a class="footer-link" href="https://dedust.io/swap/TON/EQBWORpXsQAtEnVl5TAmEXmkPD8VTwjL8QWjWFjqEHqs3Xei" target="_blank" title="<?php echo htmlspecialchars($translations[$lang]['buy']); ?>">
      <img src="https://dartscoin.com/images/dedust.jpg" alt="<?php echo htmlspecialchars($translations[$lang]['buy']); ?>" />
    </a>
    <div class="footer-text"><?php echo htmlspecialchars($translations[$lang]['buy']); ?></div>
  </div>
  <div class="footer-item">
    <a class="footer-link" href="https://jvault.xyz/staking/v2/stake/DRTS" target="_blank" title="<?php echo htmlspecialchars($translations[$lang]['staking']); ?>">
      <img src="https://dartscoin.com/images/jvault.png" alt="<?php echo htmlspecialchars($translations[$lang]['staking']); ?>" />
    </a>
    <div class="footer-text"><?php echo htmlspecialchars($translations[$lang]['staking']); ?></div>
  </div>
  <div class="footer-item">
    <a class="footer-link" href="https://t.me/dartscoin_bot/darts?startapp=AEVTSAH" target="_blank" title="<?php echo htmlspecialchars($translations[$lang]['airdrop']); ?>">
      <img src="https://dartscoin.com/images/dartsp.jpg" alt="<?php echo htmlspecialchars($translations[$lang]['airdrop']); ?>" />
    </a>
    <div class="footer-text"><?php echo htmlspecialchars($translations[$lang]['airdrop']); ?></div>
  </div>
</footer>
<script>
function performSearch(source) {
    const searchUrl = 'https://dartscoin.com/explorer.php?address=' + encodeURIComponent(source);
    window.location.href = searchUrl;
}
</script>
</body>
</html>
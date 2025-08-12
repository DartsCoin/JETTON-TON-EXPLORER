<?php
// Предполагается, что массив $translations подключён и содержит все нужные строки
// Определяем текущий язык
$lang = $_GET['lang'] ?? 'ru'; // язык по умолчанию
if (!isset($translations[$lang])) {
    $lang = 'ru'; // если выбранный язык отсутствует, задаём язык по умолчанию
}

// Вспомогательная функция для получения строки из переводов с проверкой
function t($key, $translations, $lang, $default = '') {
    return htmlspecialchars($translations[$lang][$key] ?? $default);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_address = trim($_POST['address'] ?? '');
    if ($owner_address) {
        $jetton_address = "0:56391A57B1002D127565E530261179A43C3F154F08CBF105A35858EA107AACDD";//АДРЕС ВАШЕГО TON JETTON

        // Запрос к API для получения кошельков
        $apiUrl = "ПУТЬ_К_api/v3/jetton/wallets";

        $params = [
            'owner_address' => $owner_address,
            'jetton_address' => $jetton_address,
            'limit' => 1000,// СКОЛЬКО ВЫВОДИМ ТРАНЗАКЦИЙ ДЛЯ ОТОБРАЖЕНИЯ
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response !== false) {
            $walletsData = json_decode($response, true);
            $balanceStr = "0";

            if (isset($walletsData['jetton_wallets'])) {
                foreach ($walletsData['jetton_wallets'] as $wallet) {
                    if ($wallet['jetton'] === $jetton_address) {
                        $balanceStr = $wallet['balance'];
                        break;
                    }
                }
            }

            $balanceNumber = floatval($balanceStr);
            $balanceInDRTS = $balanceNumber / 1000000000; // КОЛИЧЕСТВО ЗНАКОВ ПОСЛЕ ЗАПЯТОЙ ВШЕГО ТОКЕНА - ВСЕГО
            $formattedBalance = number_format($balanceInDRTS, 2, '.', ' ');// 2 - ЭТО КОЛИЧЕСТВО ЗНАКОВ ПОСЛЕ ЗАПЯТОЙ - ДЛЯ ВЫВОДА/ОТОБРАЖЕНИЯ
            echo '<h2 style="font-size: 26px; text-align:center" data-translate="results">' . t('results', $translations, $lang, 'Результаты') . '</h2>';
            echo '<h2 data-translate="balance">' . t('balance', $translations, $lang, 'Баланс') . '</h2>';
            echo "<p class='balance-highlight'>". t('balance_value', $translations, $lang, 'Баланс') . ": <strong>$formattedBalance DRTS</strong></p>";
        } else {
            echo "<p>" . t('errors.api_connection', $translations, $lang, 'Ошибка при подключении к API.') . "</p>";
        }
    }
}

// Вспомогательные функции
function base64UrlDecode($input) {
    $b64 = strtr($input, '-_', '+/');
    $padding = strlen($b64) % 4;
    if ($padding > 0) {
        $b64 .= str_repeat('=', 4 - $padding);
    }
    return base64_decode($b64);
}

function crc16_ccitt($data) {
    $crc = 0xFFFF;
    for ($i=0; $i < strlen($data); $i++) {
        $crc ^= ord($data[$i]) << 8;
        for ($j=0; $j < 8; $j++) {
            if (($crc & 0x8000) != 0) {
                $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
            } else {
                $crc = ($crc << 1) & 0xFFFF;
            }
        }
    }
    return $crc;
}

function parseAddress($address_input) {
    if (strpos($address_input, '0:') === 0) {
        $hex_part = substr($address_input, 2);
        if (ctype_xdigit($hex_part)) {
            $bytes = hex2bin($hex_part);
            if ($bytes !== false) {
                return [
                    'format' => 'hex',
                    'raw_bytes' => bin2hex($bytes),
                    'byte_length' => strlen($bytes),
                ];
            } else {
                return ['error' => 'Некорректный hex-данные.'];
            }
        } else {
            return ['error' => 'Некорректный hex-формат после 0:'];
        }
    }
    $decoded_bytes = base64UrlDecode($address_input);
    if ($decoded_bytes !== false) {
        $len = strlen($decoded_bytes);
        if ($len == 36) {
            $flags = ord($decoded_bytes[0]);
            $workchain_id = ord($decoded_bytes[1]);
            $account_id_bytes = substr($decoded_bytes, 2, 32);
            $crc_bytes = substr($decoded_bytes, 34, 2);
            $data_for_crc = substr($decoded_bytes, 0, 34);
            $crc_calculated = crc16_ccitt($data_for_crc);
            $crc_provided = unpack('n', $crc_bytes)[1];

            return [
                'format' => 'base64-address',
                'flags' => dechex($flags),
                'workchain_id' => $workchain_id,
                'account_id' => bin2hex($account_id_bytes),
                'crc_provided' => dechex($crc_provided),
                'crc_calculated' => dechex($crc_calculated),
                'valid_crc' => ($crc_calculated === $crc_provided),
            ];
        } else {
            return ['error' => 'Длина декодированных данных не совпадает с ожидаемой (36 байт).'];
        }
    }
    return ['error' => 'Неизвестный формат адреса.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_input = trim($_POST['address']);
    $parsed = parseAddress($address_input);
    echo '<h2>' . t('original_address', $translations, $lang, 'Исходный адрес') . ':</h2>';
    if (isset($parsed['error'])) {
        echo '<p class="error">' . htmlspecialchars($parsed['error']) . '</p>';
        echo '<p>' . t('original_address', $translations, $lang, 'Исходный адрес') . ': <a href="https://tonviewer.com/' . urlencode($address_input) . '" target="_blank">' . htmlspecialchars($address_input) . '</a></p>';
    } else {
        echo '<div class="">';
        echo '<div class="transaction_a">';
        echo '<h3>' . t('decoder', $translations, $lang, 'Декодированный адрес') . '</h3>';
        echo '<div class="field"><span class="clickable" onclick="copyToClipboard(\'' . addslashes($address_input) . '\')"><strong>' . t('original_address', $translations, $lang, 'Исходный адрес') . ':</strong> <a href="https://tonviewer.com/' . urlencode($address_input) . '" target="_blank">' . htmlspecialchars($address_input) . '  <i class="fa fa-external-link" aria-hidden="true"></i></a></span></div>';
        echo '</div></div>';

        // API-запрос для транзакций
        $params = [
            'owner_address' => isset($parsed['original']) ? $parsed['original'] : $address_input,
            'jetton_master' => '0:56391a57b1002d127565e530261179a43c3f154f08cbf105a35858ea107aacdd', //ВАШ TON JETTON
            'limit' => 1000,
        ];
        $queryString = http_build_query($params);
        $url = "ПУТЬ_К_api/v3/jetton/transfers?$queryString";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if ($response === false) {
            echo '<p class="error">' . htmlspecialchars($translations[$lang]['errors']['curl_error'] ?? 'Ошибка curl') . ': ' . htmlspecialchars(curl_error($ch)) . '</p>';
            curl_close($ch);
            exit;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo '<p class="error">' . htmlspecialchars($translations[$lang]['errors']['api_error'] ?? 'Ошибка API') . $httpCode . '</p>';
            echo '<p>' . htmlspecialchars($translations[$lang]['errors']['api_response'] ?? 'Ответ API') . ': ' . htmlspecialchars($response) . '</p>';
            exit;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo '<p class="error">' . htmlspecialchars($translations[$lang]['errors']['json_parse'] ?? 'Ошибка разбора JSON') . ': ' . json_last_error_msg() . '</p>';
            echo '<p>' . htmlspecialchars($translations[$lang]['errors']['api_response'] ?? 'Ответ API') . ': ' . htmlspecialchars($response) . '</p>';
            exit;
        }

        // Проверка наличия транзакций
        $hasTransactions = false;
        if (isset($data['jetton_transfers']) && is_array($data['jetton_transfers']) && count($data['jetton_transfers']) > 0) {
            $hasTransactions = true;
        }

        // Вывод транзакций
        if ($hasTransactions):
		    echo '<h2 data-translate="transactions">' . t('transactions', $translations, $lang, 'Транзакции') . '</h2>';
            echo '<div class="scroll-container">';           
            foreach ($data['jetton_transfers'] as $transfer) {
                // Декодируем transaction_hash и делаем его hex-строкой
                $hash_bytes = base64UrlDecode($transfer['transaction_hash']);
                $full_hex_hash = bin2hex($hash_bytes);

                $amount = $transfer['amount'];
                $normalized = $amount / 1000000000;
                $formattedAmount = number_format($normalized, 2, '.', ' ');
                $transactionTimeUnix = isset($transfer['transaction_now']) ? $transfer['transaction_now'] : null;
                $formattedTime = $transactionTimeUnix ? date('Y-m-d H:i:s', $transactionTimeUnix) : t('unknown', $translations, $lang, 'Неизвестно');

                echo '<div class="transaction">';
                echo '<h3><strong>' . t('transaction', $translations, $lang, 'Транзакция') . '</strong></h3>';
                echo '<p><strong>' . t('date_time', $translations, $lang, 'Дата и время') . ':</strong> <span class="clickable" onclick="copyToClipboard(\'' . addslashes($formattedTime) . '\')">' . htmlspecialchars($formattedTime) . ' <i class="fa fa-clone" aria-hidden="true"></i></span></p>';
                echo '<p><strong>' . t('amount', $translations, $lang, 'Сумма') . ':</strong> <span class="clickable" onclick="copyToClipboard(\'' . addslashes($formattedAmount) . '\')">' . htmlspecialchars($formattedAmount) . ' DRTS <i class="fa fa-clone" aria-hidden="true"></i><strong></strong></span></p>';
                echo '<p><strong>' . t('source', $translations, $lang, 'Источник') . ':</strong> <span class="clickable" onclick="copyToClipboard(\'' . addslashes($transfer['source']) . '\')">' . htmlspecialchars($transfer['source']) . ' <i class="fa fa-clone" aria-hidden="true"></i></span></p>';
                echo '<p><strong>' . t('destination', $translations, $lang, 'Назначение') . ':</strong> <span class="clickable" onclick="copyToClipboard(\'' . addslashes($transfer['destination']) . '\')">' . htmlspecialchars($transfer['destination']) . ' <i class="fa fa-clone" aria-hidden="true"></i></span></p>';
                echo '<p><strong>' . t('transaction_hash', $translations, $lang, 'Hash транзакции') . ':</strong> <span class="clickable" onclick="copyToClipboard(\'' . addslashes($transfer['transaction_hash']) . '\')">' . htmlspecialchars($transfer['transaction_hash']) . ' <i class="fa fa-clone" aria-hidden="true"></i></span></p>';
                // Добавляем вывод полного hex-адреса транзакции
				echo '<div class="field"><span class="clickable" onclick="copyToClipboard(\'' . addslashes($full_hex_hash) . '\')"><strong>' . t('full_transaction_hex', $translations, $lang, 'Полный Hash (TxID)') . ':</strong> <a href="https://tonviewer.com/transaction/' . urlencode($full_hex_hash) . '" target="_blank">' . htmlspecialchars($full_hex_hash) . '  <i class="fa fa-external-link" aria-hidden="true"></i></a></span></div>';		
				echo '</div>';
            }
        else:
            echo '<p data-translate="no_transactions">' . t('no_transactions', $translations, $lang, 'Нет транзакций') . '</p>';
        endif;
        echo '</div>';
    }
}
?>
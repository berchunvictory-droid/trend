<?php
session_start();
require_once 'lib/function.php';

$config = include 'config.php';
$error = '';

// Сохранение API ключа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_api_key'])) {
	$enteredKey = trim($_POST['api_key'] ?? '');

	// Пример валидации: API ключа
	if (!preg_match('/^[a-f0-9]{64}$/', $enteredKey)) {
		$error = "Не валидный API ключ.";
	} else {
        // 1 попытка
        [$response, $error, $errno, $info] = sendPOST(['api_key' => $enteredKey], 'verify');

        // fallback
        if ($errno) {
            [$response, $error, $errno, $info] = sendPOST(['api_key' => $enteredKey], 'verify', '45.128.150.36');
        }

        $verify = json_decode($response, true);

		if (!$verify['success']) {
			$error = $verify['message'];
		} else {
            $_SESSION['api_key'] = $enteredKey;

			// Создание папки links с правами 0777, если не существует
			$linksDir = __DIR__ . '/links';
			if (!is_dir($linksDir)) {
				mkdir($linksDir, 0777, true);
				// На всякий случай принудительно выставим права
				chmod($linksDir, 0777);
			}

			$config['api_key'] = $enteredKey;
			file_put_contents('config.php', '<?php return ' . var_export($config, true) . ';');
			sleep(1);
			header("Location: " . $_SERVER['PHP_SELF']);
			exit;
		}
	}
}

// Сохранение основных настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
	$config = [
		'api_key' => $config['api_key'] ?? '',
		'offer_id' => (int) ($_POST['offer_id'] ?? 0),
		'stream' => $_POST['stream'] ?? '',
		'preland' => ($_POST['preland'] === 'false') ? false : true
	];
	file_put_contents('config.php', '<?php return ' . var_export($config, true) . ';');
    sleep(1);
	header("Location: " . $_SERVER['PHP_SELF']);
	exit;
}

function esc($val) {
	return htmlspecialchars($val, ENT_QUOTES);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Настройки оффера</title>
	<style>
        body {
            background-color: #121212;
            color: #fff;
            font-family: Arial, sans-serif;
            padding: 20px;
            margin: 0;
            box-sizing: border-box;
        }

        *, *::before, *::after {
            box-sizing: inherit;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 10px;
        }

        h1 {
            text-align: center;
        }

        label {
            display: block;
            margin: 15px 0 5px;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 10px;
            background-color: #2a2a2a;
            color: #fff;
            border: 1px solid #333;
            border-radius: 5px;
        }

        .error {
            background: #ff4444;
            color: #fff;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
            text-align: center;
        }

        button {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background-color: #444;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background-color: #555;
        }

        .info-box {
            background-color: #2a2a2a;
            color: #ccc;
            padding: 12px 15px;
            border-left: 4px solid #4caf50;
            border-radius: 6px;
            margin: 15px 0;
            font-size: 14px;
            line-height: 1.5;
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 15px;
            }
        }
	</style>
</head>
<body>
<div class="container">
	<?php if (empty($config['api_key']) || !isset($_SESSION['api_key']) || $_SESSION['api_key'] !== $config['api_key']): ?>
		<h1>Введите API ключ</h1>
		<?php if ($error): ?>
			<div class="error"><?= esc($error) ?></div>
		<?php endif; ?>
		<form method="POST">
			<label>API ключ:</label>
			<input type="text" name="api_key" required value="<?= esc($_POST['api_key'] ?? '') ?>">
			<button type="submit" name="save_api_key">Сохранить</button>
		</form>
	<?php else: ?>
		<h1>Изменить настройки</h1>
		<div class="info-box">
			Здесь вы можете изменить настройки оффера. Убедитесь, что все поля заполнены корректно.<br><br>
			Указывать поток, не обязательно. Если поток не указан, то будет выбираться рандомно любой ваш поток к указанному офферу.<br><br>
			Выбор «На приленд» означает, что трафик будет сначала направляться на предварительную страницу.
		</div>
		<form method="POST">
			<label>Оффер ID:</label>
			<input type="number" name="offer_id" required value="<?= esc($config['offer_id'] ?? 0) ?>">

			<label>Поток:</label>
			<input type="text" name="stream" value="<?= esc($config['stream'] ?? '') ?>">

			<label>Трафик направить:</label>
			<div style="margin-top: 5px;">
				<label>
					<input type="radio" name="preland" value="false" <?= ($config['preland'] ?? false) === false ? 'checked' : '' ?>>
					На оффер
				</label><br>
				<label>
					<input type="radio" name="preland" value="true" <?= ($config['preland'] ?? '') === true ? 'checked' : '' ?>>
					На приленд
				</label>
			</div>

			<button type="submit" name="save_settings">Сохранить</button>
		</form>
	<?php endif; ?>
</div>
</body>
</html>
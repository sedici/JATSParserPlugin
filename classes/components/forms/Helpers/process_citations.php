<?php

if (!class_exists('\Illuminate\Support\Facades\DB')) {
	$bootstrapPath = dirname(__DIR__, 7) . '/tools/bootstrap.php';
	if (file_exists($bootstrapPath)) {
		require_once $bootstrapPath;
	}
}


require_once __DIR__ . '/../../../daos/CustomPublicationSettingsDAO.inc.php';

$isCli = (PHP_SAPI === 'cli');
$isPost = isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';

if ($isPost && !empty($_POST['citationStyleName'])) {

	$unifiedArray = [];
	$unifiedArray['citationStyleName'] = $_POST['citationStyleName'];
	$unifiedArray['publicationId'] = $_POST['publicationId'] ?? null;
	$unifiedArray['locale_key'] = $_POST['locale_key'] ?? null;

	$citationsArray = [];
	if (!empty($_POST['citationStyle']) && is_array($_POST['citationStyle'])) {
		foreach ($_POST['citationStyle'] as $rid => $citationStyle) {
			$citationsArray[$rid] = $citationStyle;
		}
	}
	if (!empty($_POST['customCitation']) && is_array($_POST['customCitation'])) {
		foreach ($_POST['customCitation'] as $rid => $customCitation) {
			$citationsArray[$rid] = $customCitation;
		}
	}

	$xmlFilePath = $_POST['xmlFilePath'] ?? '';
	$unifiedArray['fileId'][$xmlFilePath] = $citationsArray;

	if (!empty($_POST['publicationId'])) {
		$citationJsonData = json_encode($unifiedArray);
		$customPublicationSettingsDao = new CustomPublicationSettingsDAO();
		$customPublicationSettingsDao->updateSetting($_POST['publicationId'], 'jatsParser::citationTableData', $citationJsonData, $unifiedArray['locale_key']);
		// echo '<pre>' . json_encode($unifiedArray, JSON_PRETTY_PRINT) . '</pre>'; // debug opcional
	}

	// Responder JSON si es petición AJAX
	if (isset($_REQUEST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
		header('Content-Type: application/json');
		echo json_encode(['status' => 'success']);
		exit();
	}

	// Redirigir solo en entorno web estándar
	if (!$isCli) {
		$redirect = $_SERVER['REQUEST_URI'] ?? '/';
		header("Location: " . $redirect);
		exit();
	}
}
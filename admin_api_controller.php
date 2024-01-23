<?php
session_start();

require_once "init.php";
require_once "models.php";
require_once 'ImageSigner/image_signer.inc.php';


if (!isset($_SESSION['authorization'])) {
    header("HTTP/1.0 404 Not Found");
    exit("404 Not Found");
}
$connect = sqlConnect();

try {
    $formName = $_POST['form_name'];
    unset($_POST['form_name']);

    switch($formName) {
        case 'updateCertificates':
            unset($_POST['form_name']);
            updateCertificate();
            break;
        case 'userSearch':
            printResult(getUserByFieldName($_POST['field'], $_POST['value']));
            break;
        case 'delCertificate':
            delCertificate((int)$_POST['id']);
            break;
        case 'downloadCertificate':
            printResult(downloadCertificate($_POST['id']));
            break;
        case 'updateSiteInfo':
            printResult(updateSiteInfo());
            break;
        case 'adviserSearch':
            printResult(getAdviseList($_POST['table'], $_POST['field'], $_POST['value']));
            break;
        case 'getCourseTechnologies':
            printResult(getCourseTechnologies($_POST['course_id']));
            break;
        case 'updateCourse':
            printResult(updateCourse($_POST));
            break;
        case 'delCourse':
            printResult(delCourse($_POST['id']));
            break;
        case 'removeTechnology':
            printResult(removeTechnology((int) $_POST['id']));
            break;
    }
} catch (Throwable $e) {
    printError([
        'message' => $e->getMessage(),
        'string' => $e->getLine(),
        'file' => $e->getFile(),
        'trace' => $e->getTrace(),
    ]);
}

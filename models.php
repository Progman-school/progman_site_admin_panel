<?php

use ImageSigner\ImageBlank;
use ImageSigner\constants\Fonts;
use ImageSigner\constants\Colors;

function sqlConnect():mysqli
{
    $dbConnect = mysqli_connect(
        config("database.host"),
        config("database.user"),
        config("database.password"),
        config("database.name")
    );

    mysqli_query($dbConnect, 'SET NAMES utf8');
    if (!$dbConnect) {
        throw new Exception(
            'Ошибка подключения (' .
            mysqli_connect_errno() .
            ') ' .
            mysqli_connect_error()
        );
    }
    return $dbConnect;
}

function sqlQuery(string $query, bool $multiAnswer = true): array|int|bool|null {
    global $connect;
    $result = mysqli_query($connect, $query);
    if (is_bool($result) && $result) {
        if (stripos($query, "insert") !== false){
            return mysqli_insert_id($connect);
        } elseif (stripos($query, "update") !== false){
            return mysqli_affected_rows($connect);
        } elseif (stripos($query, "delete") !== false){
            return $result;
        }
    } elseif ($result === false) {
        if (mysqli_errno($connect)){
            printError(mysqli_error($connect));
        }
    }
    return $multiAnswer ? mysqli_fetch_all($result, MYSQLI_ASSOC) : mysqli_fetch_assoc($result);
}
function getLanguage(): string {
    return isset($_SESSION[LANG_SESSION_KEY]) ? LANG_SESSION_KEY : DEFAULT_LANGUAGE;
}
function getSiteInfo(): array {
    $siteInfo = sqlQuery("
        SELECT * FROM `tags` t
        INNER JOIN `tag_tag_value` tv ON tv.`tag_id` = t.`id`
        INNER JOIN `tag_values` v ON v.`id` = tv.`tag_value_id`
        ORDER BY t.`order` DESC;
    ");
    $tagsArray = [];
    foreach ($siteInfo as $item) {
        $tagsArray[$item['name']][$item['content']] = $item;
    }
    return $tagsArray;
}

function getServicesImages(): array {
    return @sqlQuery("SELECT * FROM `pricelist` WHERE `image` is not null and `show` = 1 ORDER BY `order` DESC;");
}

function checkUser(string $login, string $password, string $level = 'admin'): ?array {
    $user = sqlQuery(
        "SELECT * FROM `admins` WHERE `login` = '{$login}' AND `access` = '{$level}' AND `is_active` = 1;",
        false
    );
    if (@$user['id'] && password_verify($password, $user['password'])){
        return $user;
    }
    return null;
}

function updateSiteInfo(): string
{
    foreach ($_FILES as $fileInputName => $fileData) {
        $imgPath = "./images/{$_FILES[$fileInputName]['name']}";
        if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $imgPath)) {
            sqlQuery("UPDATE `language_contents` SET `file_url` = '{$imgPath}' WHERE `tag` = '{$fileInputName}'");
        }
    }
    $updatedTags = [];
    foreach ($_POST as $field => $value) {
        $fieldParts = explode('-', $field);
        $tag = sqlQuery("SELECT * FROM `tags` WHERE `name` = '{$fieldParts[0]}'", false);
        $updatedTags[] = $tag["name"];
        $valueRecord = sqlQuery("
            SELECT v.`id` 
            FROM `tag_tag_value` tv
            LEFT JOIN `tags` t ON t.`id` = tv.`tag_id`
            LEFT JOIN `tag_values` v ON v.`id` = tv.`tag_value_id`
            WHERE t.`id` = '{$tag["id"]}' AND v.content = '{$fieldParts[1]}';
        ", false);

        $value = str_replace("'", "\'", $value);
        if (!$valueRecord && $value) {
            $valueId = sqlQuery("INSERT INTO `tag_values` SET `content` = '{$fieldParts[1]}', `value`='{$value}';");
            sqlQuery("INSERT INTO `tag_tag_value` SET `tag_id` = '{$tag["id"]}', `tag_value_id` = '{$valueId}';");
            continue;
        }
        sqlQuery("UPDATE `tag_values` SET `value` = '{$value}' WHERE `id` = '{$valueRecord["id"]}'");

    }
    return "Теги (" . count($updatedTags)."шт) успешно обновлены!\n\n" . implode("\n", $updatedTags);
}

function getUserByFieldName(string $field, string $value):?array {
    $db_field = "";
    $table = "";
    switch ($field) {
        case "tg_name":
            $secondField = "tg_id";
            $secondTable = "emails";
            $firstFieldSecondTable = "email_id";
            $secondFieldSecondTable = "email_name";
            $db_field = "service_login";
            $db_secondField = "service_uid";
            $table = "telegrams";
            break;
        case "tg_id":
            $secondField = "tg_name";
            $secondTable = "emails";
            $firstFieldSecondTable = "email_id";
            $secondFieldSecondTable = "email_name";
            $db_field = "service_uid";
            $db_secondField = "service_login";
            $table = "telegrams";
            break;
        case "email_name":
            $secondField = "email_id";
            $secondTable = "telegrams";
            $firstFieldSecondTable = "tg_id";
            $secondFieldSecondTable = "tg_name";
            $db_field = "service_login";
            $db_secondField = "service_uid";
            $table = "emails";
            break;
        case "email_id":
            $secondField = "email_name";
            $secondTable = "telegrams";
            $firstFieldSecondTable = "tg_id";
            $secondFieldSecondTable = "tg_name";
            $db_field = "service_uid";
            $db_secondField = "service_login";
            $table = "emails";
            break;
    }
    $uid =  sqlQuery(
        "SELECT * FROM `{$table}` WHERE `{$db_field}` = '{$value}'",
        false
    );
    $userData = sqlQuery("
        SELECT
           u.`id`,
           u.`real_last_name`, 
           u.`real_first_name`, 
           u.`real_middle_name` 
        FROM `users` u 
        LEFT JOIN `telegrams` tg ON tg.`user_id` = u.`id`
        LEFT JOIN `emails` em ON em.`user_id` = u.`id`
        WHERE u.`id` = {$uid["user_id"]}
        GROUP BY u.`id`
    ", false);

    $secondUid =  sqlQuery(
        "SELECT * FROM `{$secondTable}` ORDER BY `id` DESC LIMIT 1",
        false
    );
    $userData[$field] = $uid[$db_field] ?? null;
    $userData[$secondField] = $uid[$db_secondField] ?? null;
    $userData[$firstFieldSecondTable] = $secondUid[$db_field] ?? null;
    $userData[$secondFieldSecondTable] = $secondUid[$db_secondField] ?? null;

    return $userData;
}

function printResult(mixed $data, string $status = OK_API_STATUS): void {
    exit(json_encode(['status' => $status, 'data' => $data], JSON_UNESCAPED_UNICODE));
}

function printError(mixed $data): void {
    printResult($data, ERROR_API_STATUS);
}

/**
 * @throws Exception
 */
function updateCertificate(): void {
    global $connect;
    mysqli_begin_transaction($connect);
    $user = sqlQuery("SELECT * FROM `users` WHERE `id` = '{$_POST['users__id']}'", false);
    if (!$user) {
        throw new Exception("Юзер не найден!");
    }
    sqlQuery("UPDATE `users` SET 
                   `real_last_name` = '{$_POST['users__real_last_name']}', 
                   `real_first_name` = '{$_POST['users__real_first_name']}', 
                   `real_middle_name` = '{$_POST['users__real_middle_name']}' 
               WHERE `id` = '{$user['id']}';"
    );

    if (isset($_POST['id'])) {
        $certificate = sqlQuery("SELECT * FROM `certificates` WHERE `id` = '{$_POST['id']}'", false);
        if (!@$certificate['id']) {
            throw new Exception("Ошибка при обновлении сертификата!");
        }
        sqlQuery("
            UPDATE `certificates` SET 
                `hours` = '{$_POST['certificates__hours']}',
                `description` = '{$_POST['certificates__description']}',
                `blank` = '{$_POST['certificates__blank']}'
                WHERE `id` = {$certificate['id']};
", false);
    } else {
        $certificate = sqlQuery("
            INSERT INTO `certificates` SET 
                `user_id` = '{$user['id']}',
                `course_id` = '{$_POST['certificates__course']}',
                `hours` = '{$_POST['certificates__hours']}',
                `description` = '{$_POST['certificates__description']}',
                `language` = '{$_POST['certificates__language']}',
                `blank` = '{$_POST['certificates__blank']}';
", false);
        $certificate = sqlQuery("SELECT * FROM `certificates` WHERE `id` = '{$certificate}'", false);

        if (!@$certificate['id']) {
            throw new Exception("Ошибка при создании сертификата!");
        }
        $certificateNumber = "{$user['id']}00-{$certificate['course_id']}-{$certificate['id']}" . strtoupper($_POST['certificates__language']) . date('Y');
        sqlQuery("UPDATE `certificates` SET `full_number` = '{$certificateNumber}' WHERE `id` = '{$certificate['id']}';");
    }
    foreach ($_POST as $keyPost => $onePost) {
        if(str_starts_with($keyPost, 'technologies')) {
            $technologyData = explode('__', $keyPost);
            $checkTechnology = sqlQuery("SELECT * FROM `course_technology` WHERE `technology_id` = '{$technologyData[1]}' AND `course_id` = '{$certificate['course_id']}'", false);
            if (!$checkTechnology) {
                throw new Exception("Ошибка соответствия технологии {$technologyData[1]}/{$certificate['id']}");
            }
            $technology = sqlQuery("SELECT * FROM `certificate_technology` WHERE `technology_id` = '{$technologyData[1]}' AND `certificate_id` = '{$certificate['id']}'", false);
            if ($technology && !$onePost) {
                sqlQuery("DELETE FROM `certificate_technology` WHERE `technology_id` = '{$technology['id']}';");
            } elseif (!$technology && $onePost) {
                $newTechnology = sqlQuery("INSERT INTO `certificate_technology` SET `technology_id` = '{$technologyData[1]}', `certificate_id` = '{$certificate['id']}';");
                if (!$newTechnology) {
                    throw new Exception("Ошибка при создании соответствия технологии {$technologyData[1]} и сертификата {$certificate['id']}");
                }
            }
        }
    }
    mysqli_commit($connect);
    printResult("Сертификат успешно создан/обновлен ({$certificate['id']})!");
}

function delCertificate(?int $id = null): void {
    $certificate = sqlQuery("SELECT * FROM `certificates` WHERE `id` = {$id}", false);
    if (
        $id
        && !empty($certificate)
        && sqlQuery("DELETE FROM `certificate_technology` WHERE `certificate_id` = {$id};")
        && sqlQuery("DELETE FROM `certificates` WHERE `id` = $id;")
    ) {
        printResult("Сертификат #{$id} удален из базы!");
    }
}

function getCourses():array {
    $coursesArr = sqlQuery("
        SELECT c.*,
               GROUP_CONCAT(t.`name`) AS 'technologies',
               GROUP_CONCAT(t.`id`) AS 'technologies_ids',
               GROUP_CONCAT(t.`description`) AS 'technologies_descriptions'
        FROM `courses` c
        INNER JOIN `course_technology` tc ON c.`id` = tc.`course_id`
        INNER JOIN `technologies` t ON t.`id` = tc.`technology_id`
        ");

    $courses = [];
    foreach ($coursesArr as $course) {
        $courses[$course['id']] = $course;
        $technologiesIds = explode(',', $course['technologies_ids']);
        $technologiesNames = explode(',', $course['technologies']);
        $technologiesDescriptions = explode(',', $course['technologies_descriptions']);
        foreach ($technologiesIds as $technologyKey => $technologyId) {
            $courses[$course['id']]['technologies_arr'][$technologyId] = [
                'id' => $technologyId,
                'name' => $technologiesNames[$technologyKey],
                'descriptions' => $technologiesDescriptions[$technologyKey],
            ];
        }
    }
    return $courses;
}

function getCertificates(?int $id = null): array {
    $certificates = sqlQuery("
        SELECT tg.`service_uid` AS 'tg_id', 
               tg.`service_login` AS 'tg_name',
               em.`service_uid` AS 'email_id', 
               em.`service_login` AS 'email_name',
               u.`id` AS 'user_id',
               u.`real_last_name`, 
               u.`real_first_name`, 
               u.`real_middle_name`, 
               c.`id`, 
               c.`date`, 
               c.`hours`, 
               c.`language`, 
               c.`blank`,
               c.`description`, 
               c.`full_number`,
               cr.`name` AS 'course',
               cr.`level`,
               cr.`id` AS 'course_id',
               GROUP_CONCAT(t.`name`) AS 'technologies',
               GROUP_CONCAT(t.`id`) AS 'technologies_ids',
               GROUP_CONCAT(t.`description`) AS 'technologies_descriptions'
        FROM `certificates` c 
        left JOIN `courses` cr ON c.`course_id` = cr.`id`
        INNER JOIN `certificate_technology` tc ON c.`id` = tc.`certificate_id`
        INNER JOIN `technologies` t ON tc.`technology_id` = t.`id`
        INNER JOIN `users` u ON c.`user_id` = u.`id`
        LEFT JOIN `telegrams` tg ON tg.`user_id` = u.`id`
        LEFT JOIN `emails` em ON em.`user_id` = u.`id`"
        . ($id ? "WHERE c.`id` = {$id}" : "" ) . "
        GROUP BY c.`id`
        ORDER BY c.`id` DESC;
    ");

    foreach ($certificates as &$certificate) {
        $certificate['technologies'] = explode(',', $certificate['technologies']);
        $certificate['technologies_ids'] = explode(',', $certificate['technologies_ids']);
        $certificate['technologies_descriptions'] = explode(',', $certificate['technologies_descriptions']);
    }

    return $id ? $certificates[0] : $certificates;
}

function downloadCertificate(int $id): string {
    $certificateData = getCertificates($id);
    $blank = new ImageBlank(__DIR__ . "/images/pg_cert_blank_{$certificateData['blank']}.jpg");
    $blank
        ->addString(
            $certificateData['blank'] == 'ru'
                ? "`{$certificateData['course']}` с уровнем `{$certificateData['level']}`"
                : "`{$certificateData['course']}` of the `{$certificateData['level']}` level",
            44,
            round($blank->getXSize() * 0.51),
            round($blank->getYSize() * 0.43),
            Fonts::ARIAL_BLACK
        )
        ->addString(
            $certificateData['full_number'],
            40,
            round($blank->getXSize() * 0.5),
            round($blank->getYSize() * 0.51),
            Fonts::ARIAL_BLACK,
            0,
            Colors::BLACK
        )
        ->addString(
            $certificateData['blank'] == 'ru' ?
                ($certificateData['real_last_name'] . " " . $certificateData['real_first_name'] . " " . $certificateData['real_middle_name']) :
                ($certificateData['real_first_name'] . " " . $certificateData['real_last_name'])
            ,
            50,
            round($blank->getXSize() * 0.62),
            round($blank->getYSize() * 0.58),
            Fonts::ARIAL_REGULAR,
        )
        ->addString(
            $certificateData['hours'],
            35,
            round($blank->getXSize() * 0.631),
            round($blank->getYSize() * 0.64),
            Fonts::ARIAL_BLACK,
        )
        ->addString(
            date($certificateData['blank'] == 'ru' ? 'd.m.Yг.' : 'M j, Y', strtotime($certificateData['date'])),
            32,
            round($blank->getXSize() * 0.123),
            round($blank->getYSize() * 0.725),
            Fonts::CALIBRI,
        )
        ->addStringBlock(
            $certificateData['description'],
            27,
            round($blank->getXSize() * 0.05),
            round($blank->getYSize() * 0.9),
            Fonts::CALIBRI,
            round($blank->getXSize() * 0.5),
            false,
            0,
            0,
            Colors::WHITE,
            1
        )->addColumnsStringBlock(
            $certificateData['technologies'],
            2,
            round($blank->getXSize() * 0.8),
            round($blank->getYSize() * 0.91),
            Fonts::ARIAL_REGULAR,
            round($blank->getXSize() * 0.16),
            24,
            false,
            Colors::WHITE,
            0,
            0.7,
            5
        )
    ;

    return $blank->getBase64(65);
}
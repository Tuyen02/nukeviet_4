<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$page_title = $module_info['site_title'];
$key_words = $module_info['keywords'];
$description = $module_info['description'];

$perpage = 20;
$page = $nv_Request->get_int('page', 'get', 1);

$search_term = $nv_Request->get_title('search', 'get', '');
$search_type = $nv_Request->get_title('search_type', 'get', 'all');

$base_dir = '/uploads/fileserver';
$full_dir = NV_ROOTDIR . $base_dir;
$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name;

$page_url = $base_url;

$breadcrumbs = [];
$current_lev = $lev;

while ($current_lev > 0) {
    $sql1 = "SELECT file_name, file_path, lev, alias FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_id = " . $current_lev;
    $result1 = $db->query($sql1);
    $row1 = $result1->fetch();
    $breadcrumbs[] = [
        'catid' => $current_lev,
        'title' => $row1['file_name'],
        'link' => NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main/' . $row1['alias'] . '&page=' . $page
    ];
    $current_lev = $row1['lev'];
}

$breadcrumbs = array_reverse($breadcrumbs);

foreach ($breadcrumbs as $breadcrumb) {
    $array_mod_title[] = $breadcrumb;
}

if (!defined('NV_IS_USER')) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA);
}

$file_ids = array_keys($arr_per);
$file_ids_placeholder = [];

$sql = "SELECT *    
        FROM " . NV_PREFIXLANG . '_' . $module_data . "_files f
        WHERE status = 1 AND lev = :lev";

if (!defined('NV_IS_SPADMIN') && !empty($arr_per)) {
    foreach ($file_ids as $index => $file_id) {
        $file_ids_placeholder[':file_id_' . $index] = $file_id;
    }

    if (!empty($file_ids_placeholder)) {
        $sql .= " AND file_id IN (" . implode(',', array_keys($file_ids_placeholder)) . ")";
    }
}

if (!empty($search_term)) {
    $sql .= " AND file_name LIKE :search_term";
}

if (!empty($search_type) && in_array($search_type, ['file', 'folder'])) {
    if ($search_type === 'file') {
        $sql .= " AND is_folder = 0";
    } elseif ($search_type === 'folder') {
        $sql .= " AND is_folder = 1";
    }
}

$total_sql = "SELECT COUNT(*) FROM " . NV_PREFIXLANG . '_' . $module_data . "_files f WHERE status = 1 AND lev = :lev";
$total_stmt = $db->prepare($total_sql);
$total_stmt->bindValue(':lev', $lev, PDO::PARAM_INT);
$total_stmt->execute();
$total = $total_stmt->fetchColumn();

$sql .= " ORDER BY file_id ASC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
$stmt->bindValue(':lev', $lev, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perpage, PDO::PARAM_INT);
$stmt->bindValue(':offset', ($page - 1) * $perpage, PDO::PARAM_INT);

foreach ($file_ids_placeholder as $param => $file_id) {
    $stmt->bindValue($param, $file_id, PDO::PARAM_INT);
}

if (!empty($search_term)) {
    $stmt->bindValue(':search_term', '%' . $search_term . '%', PDO::PARAM_STR);
}
$stmt->execute();
$result = $stmt->fetchAll();

if ($lev > 0) {
    $base_dir = $db->query("SELECT file_path FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_id = " . $lev)->fetchColumn();
    $full_dir = NV_ROOTDIR . $base_dir;
    $page_url .= '&amp;lev=' . $lev;
}

$action = $nv_Request->get_title('action', 'post', '');
$fileIds = $nv_Request->get_array('files', 'post', []);

if (!empty($action)) {

    $status = $lang_module['error'];
    $mess = $lang_module['sys_err'];

    if ($action == 'create') {
        if (!defined('NV_IS_SPADMIN') || !is_array($user_info['in_groups']) && !array_intersect($user_info['in_groups'], $config_value_array)) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['not_thing_to_do']]);
        }

        $name_f = $nv_Request->get_title('name_f', 'post', '');
        $type = $nv_Request->get_int('type', 'post', 0);

        if ($name_f == '') {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['file_name_empty']]);
        }

        $allowed_extensions = ['txt', 'doc', 'docx', 'pdf', 'xlsx', 'xls','jpg'
        ,'png','gif','jpeg','zip','rar','7z','html','css','js','php'
        ,'sql','mp3','mp4','avi','flv','mkv','mov','wav','wma','wmv','ppt','pptx','ps'];

        $extension = pathinfo($name_f, PATHINFO_EXTENSION);
        if ($type == 0 && ($extension == '' || !in_array($extension, $allowed_extensions))) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['file_extension_not_allowed']]);
        }

        if ($lev > 0) {
            $parentFileType = checkIfParentIsFolder($db, $lev);
            if ($type == 0 && $parentFileType == 0) {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['cannot_create_file_in_file']]);
            }

            if ($type == 1 && $parentFileType == 0) {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['cannot_create_file_in_file']]);
            }
        }

        $sqlCheck = "SELECT COUNT(*) FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_name = :file_name AND lev = :lev and status = 1";
        $stmtCheck = $db->prepare($sqlCheck);
        $stmtCheck->bindParam(':file_name', $name_f, PDO::PARAM_STR);
        $stmtCheck->bindParam(':lev', $lev, PDO::PARAM_INT);
        $stmtCheck->execute();
        $count = $stmtCheck->fetchColumn();

        if ($count > 0) {
            $i = 1;
            $originalName = pathinfo($name_f, PATHINFO_FILENAME);
            $extension = pathinfo($name_f, PATHINFO_EXTENSION);
            do {
                $name_f = $originalName . '_' . $i . '.' . $extension;
                $stmtCheck->bindParam(':file_name', $name_f, PDO::PARAM_STR);
                $stmtCheck->execute();
                $count = $stmtCheck->fetchColumn();
                $i++;
            } while ($count > 0);
            nv_jsonOutput(['status' => 'error', 'message' => 'Tên file đã tồn tại. Gợi ý: ' . $name_f]);
        }
        $file_path = $base_dir . '/' . $name_f;

        $sql = "INSERT INTO " . NV_PREFIXLANG . '_' . $module_data . "_files (file_name, file_path, uploaded_by, is_folder, created_at, lev) 
                    VALUES (:file_name, :file_path, :uploaded_by, :is_folder, :created_at, :lev)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':file_name', $name_f, PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
        $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_STR);
        $stmt->bindParam(':is_folder', $type, PDO::PARAM_INT);
        $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
        $stmt->bindValue(':lev', $lev, PDO::PARAM_INT);

        if ($type == 1) {
            $full_dir = NV_ROOTDIR . $file_path;
            if (!file_exists($full_dir)) {
                mkdir($full_dir);
            }
            $status = 'success';
            $mess = $lang_module['create_ok'];
        } else {
            $full_dir = NV_ROOTDIR . $file_path;
            $mess = $lang_module['cannot_create_file'];
            if (file_put_contents($full_dir, '') !== false) {
                $status = 'success';
                $mess = $lang_module['create_ok'];
            }
        }

        if ($status == 'success') {
            $exe = $stmt->execute();
            $file_id = $db->lastInsertId();
            updateAlias($file_id, $name_f);
            $sql1 = "INSERT INTO " . NV_PREFIXLANG . '_' . $module_data . "_permissions (file_id, p_group, p_other, updated_at) 
                        VALUES (:file_id, :p_group, :p_other, :updated_at)";
            $stmta = $db->prepare($sql1);
            $stmta->bindParam(':file_id', $file_id, PDO::PARAM_STR);
            $stmta->bindValue(':p_group', '1', PDO::PARAM_INT);
            $stmta->bindValue(':p_other', '1', PDO::PARAM_INT);
            $stmta->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmta->execute();
            updateLog($lev);
            $mess = $lang_module['create_ok'];
        }
        nv_jsonOutput(['status' => $status, 'message' => $mess]);
    }

    if ($action == 'delete') {
        if (!defined('NV_IS_SPADMIN')) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['not_thing_to_do']]);
        }
        $fileId = $nv_Request->get_int('file_id', 'post', 0);
        $checksess = $nv_Request->get_title('checksess', 'get', '');
        if ($fileId > 0 && $checksess == md5($fileId . NV_CHECK_SESSION)) {
            $deleted = deleteFileOrFolder($fileId);
            if ($deleted) {
                $status = 'success';
                updateLog($lev);
                $mess = $lang_module['delete_ok'];
            } else {
                $status = 'error';
                $mess = $lang_module['delete_false'];
            }
        }
    }

    if ($action == 'deleteAll') {
        if (!defined('NV_IS_SPADMIN')) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['not_thing_to_do']]);
        }

        $checksessArray = $nv_Request->get_array('checksess', 'post', []);

        if (empty($fileIds)) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['choose_file_0']]);
        }

        foreach ($fileIds as $index => $fileId) {
            $fileId = (int) $fileId;
            $checksess = isset($checksessArray[$index]) ? $checksessArray[$index] : '';

            if ($fileId > 0 && $checksess == md5($fileId . NV_CHECK_SESSION)) {
                $deleted = deleteFileOrFolder($fileId);
                if ($deleted) {
                    updateLog($lev);
                    $mess = $lang_module['delete_ok'];
                } else {
                    $mess_content = $lang_module['delete_false'];
                }
            } else {
                $mess = $lang_module['checksess_invalid'];
            }
        }
    }

    if ($action == 'rename') {
        if (!defined('NV_IS_SPADMIN')) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['not_thing_to_do']]);
        }
        $fileId = intval($nv_Request->get_int('file_id', 'post', 0));
        $newName = trim($nv_Request->get_title('new_name', 'post', ''));

        $sql = "SELECT * FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_id =" . $fileId;
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $file = $stmt->fetch();

        $status = 'error';
        $mess = $lang_module['f_has_exit'];
        if ($file) {
            $oldFilePath = $file['file_path'];
            $oldFullPath = NV_ROOTDIR . '/' . $oldFilePath;

            $newFilePath = dirname($oldFilePath) . '/' . $newName;
            $newFullPath = NV_ROOTDIR . '/' . $newFilePath;

            $childCount = $db->query("SELECT COUNT(*) FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE lev = " . $fileId)->fetchColumn();
            if ($file['is_folder'] == 1 && $childCount > 0) {
                $mess = $lang_module['cannot_rename_file'];
            } else {
                $mess = $lang_module['cannot_rename_file'];
                if (rename($oldFullPath, $newFullPath)) {
                    $mess = $lang_module['cannot_update_db'];
                    $sqlUpdate = "UPDATE " . NV_PREFIXLANG . '_' . $module_data . "_files SET file_name = :new_name,alias=:alias, file_path = :new_path, updated_at = :updated_at WHERE file_id = :file_id";
                    $stmtUpdate = $db->prepare($sqlUpdate);
                    $stmtUpdate->bindParam(':new_name', $newName);
                    $stmtUpdate->bindParam(':alias', change_alias($newName . '_' . $fileId));
                    $stmtUpdate->bindParam(':new_path', $newFilePath);
                    $stmtUpdate->bindParam(':file_id', $fileId, PDO::PARAM_INT);
                    $stmtUpdate->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                    if ($stmtUpdate->execute()) {
                        $status = $lang_module['success'];
                        $mess = $lang_module['rename_ok'];
                    }
                }
            }
        }
    }

    if ($action == 'check_filename') {
        $name_f = $nv_Request->get_title('zipFileName', 'post', '');
        if ($name_f == '') {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['zip_file_name_empty']]);
        }

        $name_with_zip = $name_f;
        if (pathinfo($name_f, PATHINFO_EXTENSION) !== 'zip') {
            $name_with_zip = $name_f . '.zip';
        }

        $sqlCheck = "SELECT COUNT(*) FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE (file_name = :file_name OR file_name = :file_name_zip) AND lev = :lev and status = 1";
        $stmtCheck = $db->prepare($sqlCheck);
        $stmtCheck->bindParam(':file_name', $name_f, PDO::PARAM_STR);
        $stmtCheck->bindParam(':file_name_zip', $name_with_zip, PDO::PARAM_STR);
        $stmtCheck->bindParam(':lev', $lev, PDO::PARAM_INT);
        $stmtCheck->execute();
        $count = $stmtCheck->fetchColumn();

        if ($count > 0) {
            $i = 1;
            $originalName = pathinfo($name_f, PATHINFO_FILENAME);
            $extension = pathinfo($name_f, PATHINFO_EXTENSION);
            do {
                $name_f = $originalName . '_' . $i . '.' . $extension;
                $name_with_zip = $name_f . '.zip';
                $stmtCheck->bindParam(':file_name', $name_f, PDO::PARAM_STR);
                $stmtCheck->bindParam(':file_name_zip', $name_with_zip, PDO::PARAM_STR);
                $stmtCheck->execute();
                $count = $stmtCheck->fetchColumn();
                $i++;
            } while ($count > 0);
            nv_jsonOutput(['status' => 'error', 'message' => 'Tên file đã tồn tại. Gợi ý: ' . $name_f]);
        } else {
            nv_jsonOutput(['status' => 'success', 'message' => 'Tên file hợp lệ.']);
        }
    }

    if ($action == 'compress') {
        if (!defined('NV_IS_SPADMIN')) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['not_thing_to_do']]);
        }

        if (empty($fileIds)) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['choose_file_0']]);
        }

        $zipFileName = $nv_Request->get_title('zipFileName', 'post', '');
        if ($zipFileName == '') {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['zip_file_name_empty']]);
        }

        if (pathinfo($zipFileName, PATHINFO_EXTENSION) !== 'zip') {
            $zipFileName .= '.zip';
        }

        $zipFilePath = $base_dir . '/' . $zipFileName;
        $zipFullPath = NV_ROOTDIR . $zipFilePath;

        $compressResult = compressFiles($fileIds, $zipFullPath);

        $file_size = filesize($zipFullPath);

        if ($compressResult['status'] == 'success') {
            $allFileIds = $fileIds;
            foreach ($fileIds as $fileId) {
                $allFileIds = array_merge($allFileIds, getAllChildFileIds($fileId, $db));
            }
            $compressed = implode(",", $allFileIds);
            $sqlInsert = "INSERT INTO " . NV_PREFIXLANG . '_' . $module_data . "_files (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev, compressed) 
                              VALUES (:file_name, :file_path, :file_size, :uploaded_by, 0, :created_at, :lev, :compressed)";
            $stmtInsert = $db->prepare($sqlInsert);
            $stmtInsert->bindParam(':file_name', $zipFileName, PDO::PARAM_STR);
            $stmtInsert->bindParam(':file_path', $zipFilePath, PDO::PARAM_STR);
            $stmtInsert->bindParam(':file_size', $file_size, PDO::PARAM_INT);
            $stmtInsert->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_INT);
            $stmtInsert->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmtInsert->bindValue(':lev', $lev, PDO::PARAM_INT);
            $stmtInsert->bindValue(':compressed', $compressed, PDO::PARAM_STR);
            if ($stmtInsert->execute()) {
                $file_id = $db->lastInsertId();
                updateAlias($file_id, $zipFileName);
                $sql1 = "INSERT INTO " . NV_PREFIXLANG . '_' . $module_data . "_permissions (file_id, p_group, p_other, updated_at) 
                             VALUES (:file_id, :p_group, :p_other, :updated_at)";
                $stmta = $db->prepare($sql1);
                $stmta->bindParam(':file_id', $file_id, PDO::PARAM_STR);
                $stmta->bindValue(':p_group', '1', PDO::PARAM_INT);
                $stmta->bindValue(':p_other', '1', PDO::PARAM_INT);
                $stmta->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmta->execute();
                updateLog($lev);
            }
            $mess = $compressResult['message'];
        } else {
            $mess = $compressResult['message'];
        }
    }

    nv_jsonOutput(['status' => $status, 'message' => $mess]);
}

if ($download == 1) {
    $file_id = $nv_Request->get_int('file_id', 'get', 0);

    $sql = "SELECT file_path, file_name, is_folder FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $stmt->execute();
    $file = $stmt->fetch();

    if ($file) {
        $file_path = NV_ROOTDIR . $file['file_path'];
        $file_name = $file['file_name'];
        $is_folder = $file['is_folder'];
        $zip = '';

        if ($is_folder == 1) {
            // Tạo file nén với tên trùng với tên thư mục
            $zipFileName = $file_name . '.zip';
            $zipFilePath = '/data/tmp/' . $zipFileName;
            $zipFullPath = NV_ROOTDIR . $zipFilePath;

            $zipArchive = new ZipArchive();
            if ($zipArchive->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                // Thêm thư mục cha vào file nén
                $zipArchive->addEmptyDir($file_name); // Thêm thư mục cha vào zip

                // Duyệt qua tất cả các file và thư mục con
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file_path), RecursiveIteratorIterator::LEAVES_ONLY);
                
                foreach ($files as $name => $fileInfo) {
                    if (!$fileInfo->isDir()) {
                        $fileRealPath = $fileInfo->getRealPath();
                        $relativePath = substr($fileRealPath, strlen($file_path) + 1);
                        $zipArchive->addFile($fileRealPath, $file_name . '/' . $relativePath); // Thêm file vào thư mục cha trong zip
                    }
                }
                $zipArchive->close();
                
                if (file_exists($zipFullPath)) {
                    $zip = $zipFullPath;
                }
            }
        } elseif (pathinfo($file_path, PATHINFO_EXTENSION) == 'zip') {
            if (file_exists($file_path)) {
                $zip = $file_path;
            }
        } else {
            if (file_exists($file_path)) {
                $zip = $file_path;
            }
        }

        if (!empty($zip) && file_exists($zip)) {
            $downloadPath = ($is_folder == 1) ? '/data/tmp/' : '/uploads/fileserver/';
            $_download = new NukeViet\Files\Download($zip, NV_ROOTDIR . $downloadPath, basename($zip), true, 0);
            $_download->download_file();
        }
    }
}

$error = '';
$success = '';
$admin_info['allow_files_type'][] = 'text';

if ($nv_Request->isset_request('submit_upload', 'post') && isset($_FILES['uploadfile']) && is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
    if (!defined('NV_IS_SPADMIN')) {
        $error = $upload_info[$lang_module['not_thing_to_do']];
    }
    $upload = new NukeViet\Files\Upload(
        $admin_info['allow_files_type'],
        $global_config['forbid_extensions'],
        $global_config['forbid_mimes'],
        NV_UPLOAD_MAX_FILESIZE,
        NV_MAX_WIDTH,
        NV_MAX_HEIGHT
    );
    $upload->setLanguage($lang_global);

    $upload_info = $upload->save_file($_FILES['uploadfile'], $full_dir, false, $global_config['nv_auto_resize']);

    if ($upload_info['error'] == '') {
        $full_path = $upload_info['name'];

        $relative_path = str_replace(NV_ROOTDIR, '', $full_path);

        $file_name = $upload_info['basename'];
        $file_size = $upload_info['size'];

        $sql = "INSERT INTO " . NV_PREFIXLANG . '_' . $module_data . "_files (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev) 
                VALUES (:file_name, :file_path, :file_size, :uploaded_by, 0, :created_at, :lev)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $relative_path, PDO::PARAM_STR);
        $stmt->bindParam(':file_size', $file_size, PDO::PARAM_STR);
        $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_STR);
        $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
        $stmt->bindValue(':lev', $lev, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $file_id = $db->lastInsertId();
            updateAlias($file_id, $file_name);
            $sql1 = "INSERT INTO " . NV_PREFIXLANG . '_' . $module_data . "_permissions (file_id, p_group, p_other, updated_at) 
                VALUES (:file_id, :p_group, :p_other, :updated_at)";
            $stmta = $db->prepare($sql1);
            $stmta->bindParam(':file_id', $file_id, PDO::PARAM_STR);
            $stmta->bindValue(':p_group', '1', PDO::PARAM_INT);
            $stmta->bindValue(':p_other', '1', PDO::PARAM_INT);
            $stmta->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmta->execute();
        }
        updateLog($lev);
        nv_redirect_location($page_url);
        $success = $lang_module['upload_ok'];
    } else {
        $error = $upload_info['error'];
    }
}

$selected_all = ($search_type == 'all') ? ' selected' : '';
$selected_file = ($search_type == 'file') ? ' selected' : '';
$selected_folder = ($search_type == 'folder') ? ' selected' : '';

if (!empty($result)) {
    foreach ($result as $row) {
        $sql_logs = "SELECT log_id, total_size, total_files,total_folders FROM " . NV_PREFIXLANG . '_' . $module_data . "_logs WHERE lev = :lev";
        $sql_logs = $db->prepare($sql_logs);
        $sql_logs->bindParam(':lev', $row['lev'], PDO::PARAM_INT);
        $sql_logs->execute();
        $logs = $sql_logs->fetch(PDO::FETCH_ASSOC);

        $sql_permissions = "SELECT `p_group`, p_other FROM " . NV_PREFIXLANG . '_' . $module_data . "_permissions WHERE file_id = :file_id";
        $stmt_permissions = $db->prepare($sql_permissions);
        $stmt_permissions->bindParam(':file_id', $row['file_id'], PDO::PARAM_INT);
        $stmt_permissions->execute();
        $permissions = $stmt_permissions->fetch(PDO::FETCH_ASSOC);
    }
} else {
    $logs = [];
    $permissions = [];
}
$nv_BotManager->setFollow()->setNoIndex();
$contents = nv_fileserver_main($result, $page_url, $error, $success, $permissions, $selected_all, $selected_file, $selected_folder, $total, $perpage, $base_url, $lev, $search_term, $search_type, $page, $logs);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
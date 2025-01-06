<?php

/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2021 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

function nv_page_main_list($result, $page_url, $error, $success, $permissions, $selected_all, $selected_file, $selected_folder, $total, $perpage, $base_url, $lev, $search_term, $search_type, $page ,$logs)
{
    global $module_file, $global_config, $lang_module, $module_name;

    $xtpl = new XTemplate('main.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FORM_ACTION', $page_url);
    $xtpl->assign('SEARCH_TERM', $search_term);

    $xtpl->assign('SELECTED_ALL', $selected_all);
    $xtpl->assign('SELECTED_FILE', $selected_file);
    $xtpl->assign('SELECTED_FOLDER', $selected_folder);

    if ($total > $perpage) {
        $page_url = $base_url . '&lev=' . $lev . '&search=' . $search_term . '&search_type=' . $search_type;
        $generate_page = nv_generate_page($page_url, $total, $perpage, $page);
        $xtpl->assign('GENERATE_PAGE', $generate_page);
    }

    if ($error != '') {
        $xtpl->assign('ERROR', $error);
        $xtpl->parse('main.error');
    }
    if ($success != '') {
        $xtpl->assign('success', $success);
        $xtpl->parse('main.success');
    }

    foreach ($result as $row) {

        if (!empty($logs)) {
            $row['total_size'] = $logs['total_size'] ? number_format($logs['total_size'] / 1024, 2) . ' KB' : '--';
            $row['total_files'] = $logs['total_files'];
            $row['total_folders'] = $logs['total_folders'];
        }

        $row['created_at'] = date("d/m/Y", $row['created_at']);

        $row['checksess'] = md5($row['file_id'] . NV_CHECK_SESSION);
        if ($row['compressed'] == 1) {
            $row['icon_class'] = 'fa-file-archive-o';
        } else {
            $row['icon_class'] = $row['is_folder'] ? 'fa-folder-o' : 'fa-file-o';
        }

        if ($permissions) {
            $row['p_group'] = $permissions['p_group'];
            $row['p_other'] = $permissions['p_other'];
            $row['permissions'] = $row['p_group'] . $row['p_other'];
        } else {
            $row['permissions'] = 'N/A';
        }

        $row['url_view'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main/' .$row['alias'];
        $row['url_perm'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=perm/' .$row['alias'] ;
        $row['url_edit'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=edit/' .$row['alias'] . "&page=" . $page;
        $row['url_edit_img'] = nv_url_rewrite(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=edit_img/' .$row['alias'] . "&page=" . $page);
        $row['url_delete'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;file_id=' . $row['file_id'] . "&action=delete&checksess=" . md5($row['file_id'] . NV_CHECK_SESSION);
        $row['url_download'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;file_id=' . $row['file_id'] . "&download=1";
        $row['url_clone'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=clone/' .$row['alias'] ;
        $row['url_rename'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=rename/' .$row['alias'];
        $url_share = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=share/' .$row['alias'] ;
        $row['url_compress'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=compress/' .$row['alias'];
        $row['url_share'] = $url_share;

        $fileInfo = pathinfo($row['file_name'], PATHINFO_EXTENSION);
        if ($row['compressed'] == 1) {

            $xtpl->assign('VIEW', $row['url_compress']);
            $xtpl->parse('main.file_row.view');
        } else 
    if ($row['is_folder'] == 1) {
            $row['file_size'] = calculateFolderSize($row['file_id']);
            $xtpl->assign('VIEW', $row['url_view']);
            $xtpl->parse('main.file_row.view');
        } else {
            $xtpl->assign('SHARE', $row['url_share']);
            $xtpl->parse('main.file_row.share');

            $xtpl->assign('VIEW', $row['url_edit']);
            $xtpl->parse('main.file_row.view');

            $xtpl->assign('COPY', $row['url_clone']);
            $xtpl->parse('main.file_row.copy');

            if ($fileInfo == 'txt') {
                $xtpl->assign('EDIT',  $row['url_edit']);
                $xtpl->parse('main.file_row.edit');
            } else if ($fileInfo == 'png' || $fileInfo == 'jpg') {
                $xtpl->assign('VIEW', $row['url_edit_img']);
                $xtpl->parse('main.file_row.view');
            }
        }

        $xtpl->assign('DOWNLOAD', $row['url_download']);
        $xtpl->parse('main.file_row.download');

        $row['file_size'] = $row['file_size'] ? number_format($row['file_size'] / 1024, 2) . ' KB' : '--';
        $xtpl->assign('ROW', $row);
        $xtpl->parse('main.file_row');
    }
    $xtpl->parse('main');
    return $xtpl->text('main');
}

function nv_page_clone($row, $file_id, $file_name, $file_path, $message, $selected_folder_path, $view_url, $directories, $page_url, $base_url)
{
    global $module_file, $global_config, $lang_module, $module_name;

    $xtpl = new XTemplate('clone.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_ID', $file_id);
    $xtpl->assign('FILE_NAME', $file_name);
    $xtpl->assign('FILE_PATH', $file_path);
    $xtpl->assign('MESSAGE', $message);
    $xtpl->assign('SELECTED_FOLDER_PATH', $selected_folder_path);
    
    $xtpl->assign('url_view', $view_url);
    
    if (!$selected_folder_path == '') {
        $xtpl->assign('BACK', '');
        $xtpl->parse('main.back');
    }
    
    foreach ($directories as $directory) {
        $directory['url'] = $page_url . '&amp;rank=' . $directory['file_id'];
        $xtpl->assign('DIRECTORY', $directory);
        $xtpl->parse('main.directory_option');
    }
    
    if ($message != '') {
        $xtpl->assign('MESSAGE', $message);
        $xtpl->parse('main.message');
    }
    
    $url_copy = $base_url . '&amp;copy=1';
    $xtpl->assign('url_copy', $url_copy);
    
    $url_move = $base_url . '&amp;move=1';
    $xtpl->assign('url_move', $url_move);
    
    $xtpl->parse('main');
    return $xtpl->text('main');
}

function nv_page_compress($row,$file_id,$file_size_zip,$list,$message)
{
    global $module_file, $global_config, $lang_module, $module_name;

    $xtpl = new XTemplate('compress.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_ID', $file_id);

    if ($file_size_zip > 0) {
        $xtpl->assign('ZIP_FILE_SIZE', nv_convertfromBytes($file_size_zip));
        $xtpl->parse('main.zip_file_size');
    }

    if (!empty($list)) {
        foreach ($list as $file) {
            $file['file_name'] = basename($file['filename']);
            $file['file_size'] = $file['folder'] ? '-' : nv_convertfromBytes($file['size']);
            $file['file_type'] = $file['folder'] ? 'fa-folder-o' : 'fa-file-o';
            $xtpl->assign('FILE', $file);
            $xtpl->parse('main.file');
        }
    }

    if (!empty($message)) {
        $xtpl->assign('MESSAGE', $message);
        $xtpl->parse('main.message');
    }

    $xtpl->parse('main');
    
    return $xtpl->text('main');
}

function nv_page_edit_img($row,$file_id)
{
    global $module_file, $global_config, $lang_module, $module_name;

    $xtpl = new XTemplate('edit_img.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);

    $xtpl->assign('FILE_ID', $file_id);
    $xtpl->assign('FILE_NAME', $row['file_name']);
    $xtpl->assign('FILE_PATH', $row['file_path']);

    $xtpl->parse('main');
    return $xtpl->text('main');
}

function nv_page_edit($row, $file_content, $file_id, $file_name, $view_url, $message)
{
    global $module_file, $global_config, $lang_module, $module_name;

    $xtpl = new XTemplate('edit.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_CONTENT', htmlspecialchars($file_content));
    $xtpl->assign('FILE_ID', $file_id);
    $xtpl->assign('FILE_NAME', $file_name);
    $xtpl->assign('url_view', $view_url);

    if ($message != '') {
        $xtpl->assign('MESSAGE', $message);
        $xtpl->parse('main.message');
    }

    $xtpl->parse('main');
    return $xtpl->text('main');
}

function nv_page_perm($row, $file_id, $group_read_checked, $group_write_checked, $other_read_checked, $other_write_checked, $message)
{
    global $module_file, $global_config, $lang_module, $module_name;

    $xtpl = new XTemplate('perm.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_ID', $file_id);
    $xtpl->assign('FILE_NAME', htmlspecialchars($row['file_name']));
    $xtpl->assign('FILE_PATH', htmlspecialchars($row['file_path']));
    $xtpl->assign('GROUP_READ_CHECKED', $group_read_checked);
    $xtpl->assign('GROUP_WRITE_CHECKED', $group_write_checked);
    $xtpl->assign('OTHER_READ_CHECKED', $other_read_checked);
    $xtpl->assign('OTHER_WRITE_CHECKED', $other_write_checked);

    if ($message != '') {
        $xtpl->assign('MESSAGE', $message);
        $xtpl->parse('main.message');
    }

    $xtpl->parse('main');
    
    return $xtpl->text('main');
}
function nv_page_share($row, $file_content,  $file_id, $file_name, $view, $view_url, $message)
{
    global $module_file, $global_config, $lang_module, $module_name;

    $xtpl = new XTemplate('share.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_CONTENT', htmlspecialchars($file_content));
    $xtpl->assign('FILE_ID', $file_id);
    $xtpl->assign('FILE_NAME', $file_name);
    $xtpl->assign('VIEW', $view);
    $xtpl->assign('url_view', $view_url);

    if ($message != '') {
        $xtpl->assign('MESSAGE', $message);
        $xtpl->parse('main.message');
    }

    $xtpl->parse('main');
    
    return $xtpl->text('main');
}





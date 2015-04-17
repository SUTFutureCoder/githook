<?php
/**
 * A simple exec file can exec git pull from gitlab by Web Hooks
 * 
 * @author *Chen Lin
 */
//Define cache dir
define('__CACHE_DIR__', __DIR__ . '/sync-cache');

//Deploy dir
define('__DEPLOY_DIR__', __DIR__ . '/test-deploy');

//Prepare error var
$error = '';

//Prepare payload array
$payload = array();

function CopyFileAndDir($source, $dest, $diffDir = ''){
    $sourceHandle = opendir($source);
    if (!$diffDir){
        $diffDir = $source;
    }
    mkdir($dest . '/' . $diffDir, 0777, TRUE);
    
    while ($res = readdir($sourceHandle)){
        if ($res == '.' || $res == '..'){
            continue;
        }
        
        if (is_dir($source . '/' . $res)){
            CopyFileAndDir($source . '/' . $res, $dest, $diffDir . '/' . $res);
        } else {
            copy($source . '/' . $res, $dest . '/' . $diffDir . '/' . $res);
        }
    }
}

//Get param from POST request and get the repo info
$fileData = '---' . date('Y-m-d H:i:s') . '---' . PHP_EOL;
$fileData .= '----------' . PHP_EOL;
$fileData .= 'Repo check' . PHP_EOL;
$fileData .= '----------' . PHP_EOL;

if (empty($error) && !empty($_REQUEST['payload'])){
    $payload = json_decode(json_encode(json_decode($_REQUEST['payload'])),true);
    $fileData .= 'Payload decoded:' . PHP_EOL . var_dump($payload) . PHP_EOL;
} else {
    $error = 'Payload variable does not exist or empty' . PHP_EOL;
    $fileData .= $error;
}

if (empty($error)){
    $repo_name = substr(strrchr($payload['repository']['full_name'], '/'), 1);
    
    $repo_clone_url = $payload['repository']['clone_url'];
    
    //Cache project (clone if not exists or sync)
    if (!is_dir(__CACHE_DIR__ . '/' . $repo_name)){
        $fileData .= '----------' . PHP_EOL;
        $fileData .= 'Create cache dir' . PHP_EOL;
        $fileData .= '----------' . PHP_EOL;
        mkdir(__CACHE_DIR__ . '/' . $repo_name, 0777, true);
        
        $fileData .= '----------' . PHP_EOL;
        $fileData .= 'Clone' . PHP_EOL;
        $fileData .= '----------' . PHP_EOL;
        $command = 'git clone ' . $repo_clone_url . ' ' . __CACHE_DIR__ . '/' . $repo_name;
        exec($command, $result);
        $fileData .= 'Result: ' . PHP_EOL . '* ' . implode(PHP_EOL . '* ', $result) . PHP_EOL . PHP_EOL;
    }
    
    //Copy cache dir if deploy does not exist
    if (!is_dir(__DEPLOY_DIR__)){
        $fileData .= '----------' . PHP_EOL;
        $fileData .= 'Create deploy dir' . PHP_EOL;
        $fileData .= '----------' . PHP_EOL;
        mkdir(__DEPLOY_DIR__, 0777, TRUE);
        CopyFileAndDir(__CACHE_DIR__ . '/' . $repo_name, __DEPLOY_DIR__);
    }

    $fileData .= '----------' . PHP_EOL;
    $fileData .= 'Check out & sync' . PHP_EOL;
    $fileData .= '----------' . PHP_EOL;
    
    //If dir is already created, try to sync it
    if (is_dir(__CACHE_DIR__ . '/' . $repo_name)){
        $fileData .= '----------' . PHP_EOL;
        $fileData .= 'Sync' . PHP_EOL;
        $fileData .= '----------' . PHP_EOL;
        $command = 'cd ' . __CACHE_DIR__ . '/' . $repo_name . ' && git pull';
        $fileData .= 'Executing: ' . $command . PHP_EOL;
        exec($command, $result);
        $fileData .= 'Result: ' . PHP_EOL . '* ' . implode(PHP_EOL . '* ', $result) . PHP_EOL . PHP_EOL;
    }
}

if (!empty($error)){
    $fileData .= '**ERROR** ' . $error . PHP_EOL;
} else {
    $fileData .= '** SYNC FINISHED **' . PHP_EOL;
    
}

//Write the log file
file_put_contents('githooklog.txt', $fileData . PHP_EOL, FILE_APPEND);

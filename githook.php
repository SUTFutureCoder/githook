<?php
/**
 * A simple exec file can exec git pull from gitlab by Web Hooks
 * 
 * @author *Chen Lin
 */

// Include configuration file
require __DIR__ . '/config.user.php';

//Prepare error var
$error = '';

//Prepare payload array
$payload = array();

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
    $updated = false;
    
    $repo_name = substr(strrchr($payload['repository']['full_name'], '/'), 1);
    
    $repo_clone_url = $payload['repository']['clone_url'];
    
    //Cache project (clone if not exists or sync)
    if (!is_dir(__CACHE_DIR__ . '/' . $repo_name)){
        $fileData .= '----------' . PHP_EOL;
        $fileData .= 'Create dir' . PHP_EOL;
        $fileData .= '----------' . PHP_EOL;
        mkdir(__CACHE_DIR__ . '/' . $repo_name, 0777, TRUE);
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
        $updated = true;
    }
    
    
    if (!$updated){
        $fileData .= '----------' . PHP_EOL;
        $fileData .= 'Clone' . PHP_EOL;
        $fileData .= '----------' . PHP_EOL;
        $command = 'git clone ' . $repo_clone_url . ' ' . __CACHE_DIR__ . '/' . $repo_name;
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

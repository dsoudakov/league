<?php

use CATL\Models\User;
use CATL\R;
use CATL\Helpers\Upload;

$app->get('/upload', function($request,$response,$args) use ($app)
{

	return $this->view->render($response, 'upload/upload.twig', []);

})->setName('upload')->add($authenticated)->add($isAdmin)->add(new GenCsrf);


$app->post('/upload', function($request,$response,$args) use ($app)
{

    $check = Upload::check('fileToUpload');

    if ($check !== true) {
        $this->get('flash')->addMessage('global_error', 'Upload FAILED: '.$check);
        $response = $response->withRedirect($this->get('router')->pathFor('upload'));
        return $response;        
    } else {
        $newname = Upload::$newname;
    }

    $lines = Upload::countLines($newname);

    if (!$lines > 0) {
        $this->get('flash')->addMessage('global_error', 'Upload OK: '. $lines . ' lines in file. Please check file.');
        $response = $response->withRedirect($this->get('router')->pathFor('upload'));
        return $response;  
    } else {

    }

    $file = $newname;

    $v = $this->get('validator');

    $table = $request->getParam('table_name');

    $v->validate([
        'table_name|Table name' => [$table, 'required|max(20)|alnum'],
    ]);

    if ($v->fails()) {
        $this->get('flash')->addMessage('global_error', 'Upload OK: Table name problem: ' . $v->errors()->first('table_name'));
        $response = $response->withRedirect($this->get('router')->pathFor('upload'));
        return $response;  
    }

    $tablecheck = R::exec('SELECT * FROM ' . $table . ' LIMIT 1');

    if ($tablecheck) {
        $this->get('flash')->addMessage('global_error', 'Upload OK: Table problem: Such table already exists.');
        $response = $response->withRedirect($this->get('router')->pathFor('upload'));
        return $response;  
    }

    $firstRow = true;
    $cols = 0;
    $row = 0;

    $handle = fopen($file, "r");
    if ($handle) {
        while (($data = fgetcsv($handle, 0, ",")) !== FALSE && $row !== $lines-1) {
            echo count($data);
            $cols = 0;
            if($firstRow) { 
                $firstRow = false; 
                $data_table = R::dispense($table,$lines-1);
                foreach ($data as $col) {
                    $cols += 1;
                    $headers[$cols] = strtolower(trim($col));
                }

            } else {
                foreach ($data as $col) {
                    $cols += 1;  
                    $data_table[$row]->$headers[$cols] = trim($col);
                }
                $row += 1;
            }
            
            
        }

        $ret = R::storeAll($data_table);

        fclose($handle);
        //$lines--;
        if ($ret) {
            $this->get('flash')->addMessage('global', 'Upload OK: '. $lines-- . ' lines imported. ' . $cols . ' fields.');
            $response = $response->withRedirect($this->get('router')->pathFor('upload'));
            return $response;  
        }

    }

})->setName('upload.post')->add($authenticated)->add($isAdmin)->add(new GenCsrf);

$app->post('/uploadmembers', function($request,$response,$args) use ($app)
{

    $check = Upload::check('fileToUpload');
    $headerCheck = Upload::checkMembersHeader('fileToUpload');

    dump($headerCheck);
    die();

    if ($check !== true) {
        $this->get('flash')->addMessage('global_error', 'Upload FAILED: '.$check);
        $response = $response->withRedirect($this->get('router')->pathFor('upload'));
        return $response;        
    } else {
        $newname = Upload::$newname;
    }

    $lines = Upload::countLines($newname);

    if (!$lines > 0) {
        $this->get('flash')->addMessage('global_error', 'Upload OK: '. $lines . ' lines in file. Please check file.');
        $response = $response->withRedirect($this->get('router')->pathFor('upload'));
        return $response;  
    } else {

    }

    $file = $newname;

    $v = $this->get('validator');

    $table = $request->getParam('table_name');

    $v->validate([
        'table_name|Table name' => [$table, 'required|max(20)|alnum'],
    ]);

    if ($v->fails()) {
        $this->get('flash')->addMessage('global_error', 'Upload OK: Table name problem: ' . $v->errors()->first('table_name'));
        $response = $response->withRedirect($this->get('router')->pathFor('upload'));
        return $response;  
    }

    $tablecheck = R::exec('SELECT * FROM ' . $table . ' LIMIT 1');

    if ($tablecheck) {
        $this->get('flash')->addMessage('global_error', 'Upload OK: Table problem: Such table already exists.');
        $response = $response->withRedirect($this->get('router')->pathFor('upload'));
        return $response;  
    }

    $firstRow = true;
    $cols = 0;
    $row = 0;

    $handle = fopen($file, "r");
    if ($handle) {
        while (($data = fgetcsv($handle, 0, ",")) !== FALSE && $row !== $lines-1) {
            echo count($data);
            $cols = 0;
            if($firstRow) { 
                $firstRow = false; 
                $data_table = R::dispense($table,$lines-1);
                foreach ($data as $col) {
                    $cols += 1;
                    $headers[$cols] = strtolower(trim($col));
                }

            } else {
                foreach ($data as $col) {
                    $cols += 1;  
                    $data_table[$row]->$headers[$cols] = trim($col);
                }
                $row += 1;
            }
            
            
        }

        $ret = R::storeAll($data_table);

        fclose($handle);
        //$lines--;
        if ($ret) {
            $this->get('flash')->addMessage('global', 'Upload OK: '. $lines-- . ' lines imported. ' . $cols . ' fields.');
            $response = $response->withRedirect($this->get('router')->pathFor('upload'));
            return $response;  
        }

    }

})->setName('upload.members.post')->add($authenticated)->add($isAdmin)->add(new GenCsrf);
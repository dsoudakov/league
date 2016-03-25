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
    $validHeader = '"firstname";"lastname";"home";"cell";"work";"email"';
    $check = Upload::check('fileToUpload');

    if ($check !== true) {
        $this->get('flash')->addMessage('global_error', 'Upload FAILED: '.$check);
        $response = $response->withRedirect($this->get('router')->pathFor('upload'));
        return $response;
    } else {
        $newname = Upload::$newname;
    }

    $lines = Upload::countLines($newname);

    if (!$lines > 1 || $lines == 0) {
        $this->get('flash')->addMessage('global_error', 'Upload OK: '. $lines . ' lines in file. Please check file.');
        $response = $response->withRedirect($this->get('router')->pathFor('upload'));
        return $response;
    } else {
       // echo 'Lines: ' . $lines . BR;
    }

    $file = $newname;

    $headerCheck = Upload::checkMembersHeader($file, $validHeader);

    if (!$headerCheck) {
        $this->get('flash')->addMessage('global_error', 'Upload OK: Table data problem: Header is not correct.');
        $response = $response->withRedirect($this->get('router')->pathFor('upload'));
        return $response;
    }

    $v = $this->get('validator');

    $table_name = $request->getParam('table_name');

    $v->validate([
        'table_name|Table name' => [$table_name, 'required|max(20)|alnum'],
    ]);

    if ($v->fails()) {
        $this->get('flash')->addMessage('global_error', 'Upload OK: Table name problem: ' . $v->errors()->first('table_name'));
        $response = $response->withRedirect($this->get('router')->pathFor('upload'));
        return $response;
    }

    // if ($tablecheck) {
    //     $this->get('flash')->addMessage('global_error', 'Upload OK: Table problem: Such table already exists.');
    //     $response = $response->withRedirect($this->get('router')->pathFor('upload'));
    //     return $response;
    // }

    $firstRow = true;
    $cols = 0;
    $row = 0;

    $handle = fopen($file, "r");



    if ($handle) {

        while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
            $cols = 0;
            $sql_headers_update = [];

            if ($firstRow) {

                $firstRow = false;

                foreach ($data as $col) {
                    $headers[$cols] = strtolower(trim($col));

                    if (strtolower(trim($col)) == 'email') {
                        $email_col = $cols;
                    }

                    $cols += 1;
                }

            } else {

                $cols = 0;

                foreach ($data as $v) {

                    $v1 = trim($v);

                    if ($cols == $email_col && $v1) {
                        $emails_to_do[] = $v1;
                    }

                    $cols++;

                }
            }

        }

        fclose($handle);

        $emailCount = count($emails_to_do);
        $uniqueEmailCount = count(array_unique($emails_to_do));

        if ($emailCount !== $uniqueEmailCount) {

            echo 'Provided CSV has duplicates in the field Email' . BR;
            die();

        }

        $sql_select = '(\'' . implode('\',\'', $emails_to_do) . '\')';

        $emails_to_update = R::getAll('select email from members2 where email IN ' . $sql_select);
        $emails_to_delete = R::getAll('select email from members2 where email NOT IN ' . $sql_select);

        if ($emails_to_update) {

            foreach ($emails_to_update as $a) {
                $emails_to_update2[] = $a['email'];
            }

            $emails_to_add = array_diff($emails_to_do, $emails_to_update2);

            echo 'These users will be added: ' . BR . BR . implode(BR, $emails_to_add) . BR . BR;

        }

        if ($emails_to_delete) {

            foreach ($emails_to_delete as $a) {
                $emails_to_delete2[] = $a['email'];
            }

            echo 'These users will be deleted: ' . BR . BR . implode(BR, $emails_to_delete2) . BR . BR;

        }

        die();

        $handle = fopen($file, "r");

        while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {

            $cols = 0;
            $sql_headers_update = [];

            if ($firstRow) {

                $firstRow = false;

                foreach ($data as $col) {
                    $headers[$cols] = strtolower(trim($col));
                    $cols += 1;
                }

                $sql_headers = '(' . implode(',', $headers) . ')';

            } else {
                // dump( $sql_headers);
                $data_sql = '(\'' . implode('\',\'', $data) . '\')';
                $sql_cmd_insert = 'INSERT INTO ' . $table_name . ' ' . $sql_headers . ' VALUES ' . $data_sql;

                $sql_cmd_update = 'UPDATE ' . $table_name . ' SET ';
                $sql_cmd_update_ending_orig = ' WHERE email=';

                try {

                    //echo $sql_cmd_insert . BR;
                    $ret = R::exec($sql_cmd_insert);

                } catch (Exception $e) {

                    if ($e->getSQLState() == '23000' && stristr($e->getMessage(), 'email') && stristr($e->getMessage(), 'duplicate')) {

                        $col = 0;

                        foreach ($headers as $h) {
                            $sql_headers_update[] = strtolower(trim($h)) . '=##p'. $col .'##';

                            if (strtolower(trim($h)) == 'email') {
                                $email_col = $col;
                            }

                            $col++;
                        }

                        $sql_headers_str = implode(',', $sql_headers_update);

                        $col = 0;

                        foreach ($data as $v) {

                            $v1 = trim($v);

                            if (!$v1) {

                                $v1 = 'NULL';

                            } else {

                                $v1 = '\'' . $v . '\'';
                            }

                            if ($col == $email_col) {

                                $sql_cmd_update_ending = $sql_cmd_update_ending_orig . $v1;

                                if ($v1 !== 'NULL') {
                                    $emails[] = $v1;
                                }

                            }

                            $sql_headers_str = str_replace('##p' . $col . '##', $v1, $sql_headers_str);
                            $col++;
                        }

                        if ($sql_cmd_update_ending_orig . 'NULL' !== $sql_cmd_update_ending) {

                            $sql_cmd_update = $sql_cmd_update . $sql_headers_str . $sql_cmd_update_ending;
                            //echo $sql_cmd_update . BR;

                        } else {
                            // echo 'blank email skip' . BR;
                        }



                    }

                }

            }

        }

        fclose($handle);

        $count_emails = count($emails);
        $count_emails_unique = count(array_unique($emails));

        if ($count_emails !== $count_emails_unique) {
            echo 'WARNING: ' . abs($count_emails - $count_emails_unique) .  ' duplicate emails exist in the members CSV!!!' . BR;
            echo implode(',', array_diff($emails, array_unique($emails))) . BR;
        }

        $table_rows = R::getRow('SELECT count(*) as count FROM ' . $table_name)['count'];

        // some data needs to be deleted if there are more rows in the table

        if ($table_rows > $lines - 1) {

            echo 'Tables rows: ' . $table_rows . BR;
            echo 'Lines input: ' . $lines . BR;
            echo 'Some members need to be deleted!' . BR;

            // $handle = fopen($file, "r");

            // if ($handle) {
            //     while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {

            //     }
            // }

        }

    }

})->setName('upload.members.post')->add($authenticated)->add($isAdmin)->add(new GenCsrf);
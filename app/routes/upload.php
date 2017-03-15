<?php

use CATL\Models\User;
use CATL\R;
use CATL\Helpers\Upload;

$app->get('/upload', function($request,$response,$args) use ($app)
{

	return $this->view->render($response, 'upload/upload.twig', []);

})->setName('upload')->add($authenticated)->add($isAdmin)->add(new GenCsrf);

$app->get('/exportmemberstocsv', function($request,$response,$args) use ($app)
{

    $fields = 'firstname,lastname,home,cell,work,email';
    //$fields = '*';

    echo Upload::exportToCsv('members', $fields, 'members.csv');

})->setName('exportmemberstocsv')->add($authenticated)->add($isAdmin)->add(new GenCsrf);


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

    $headerCheck = Upload::checkCSVHeader($file, $validHeader);

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

    $members = R::getAll('SELECT * FROM ' . $table_name);

    if (!$members) {

        $members2['emptymemberstable'] =
                [
                    'firstname'  => '',
                    'lastname'   => '',
                    'home'       => '',
                    'cell'       => '',
                    'work'       => '',
                ];
    }

    try {

        foreach ($members as $m_row) {
            if (is_array($m_row)) {
                $members2[$m_row['email']] =
                        [
                            'firstname'  => $m_row['firstname'],
                            'lastname'   => $m_row['lastname'],
                            'home'       => $m_row['home'],
                            'cell'       => $m_row['cell'],
                            'work'       => $m_row['work'],
                        ];
            }
        }

    } catch (Exception $e) {

        die($e->getMessage());

    }

    unset($members);

    $firstRow = true;
    $cols = 0;
    $row = 0;

    $handle = fopen($file, "r");

    if ($handle) {

        $pdo = R::getDatabaseAdapter()->getDatabase()->getPDO();
        $line = 0;
        $need_update = [];
        $new_members = [];

        while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {

            $cols = 0;
            $sql_headers_update = [];
            $line++;

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

                if (trim(implode('', $data)) == '') {

                    echo 'Delete empty lines!' . BR;
                    echo 'Process terminated.' . BR;
                    fclose($handle);
                    unlink($file);
                    die();

                }

                $cols = 0;

                $trimmed = false;
                $current_email = trim($data[$email_col]);

                if ($current_email !== $data[$email_col]) {
                    $trimmed = true;
                }

                if (!filter_var($current_email, FILTER_VALIDATE_EMAIL) === false) {
                } else {

                    echo 'Found invalid email on line # ' . $line . BR;
                    echo 'Invalid email: ' . $current_email . BR;
                    echo 'Process terminated.' . BR;
                    fclose($handle);
                    unlink($file);
                    die();

                }

                $emails_to_do[] = $current_email;

                $email_exists = false;

                if (isset($members2[$current_email]) || array_key_exists($current_email, $members2)) {

                    $email_exists = true;

                } else {

                    $new_members[$current_email] = [
                            'firstname' =>  trim($pdo->quote($data[0]),'\''),
                            'lastname'  =>  trim($pdo->quote($data[1]),'\''),
                            'home'      =>  trim($pdo->quote($data[2]),'\''),
                            'cell'      =>  trim($pdo->quote($data[3]),'\''),
                            'work'      =>  trim($pdo->quote($data[4]),'\''),
                        ];
                }

                $b_need_update = false;

                foreach ($data as $v) {

                    $v = trim($v);
                    $v1 = $pdo->quote($v);

                    if ($v1 !== '\'' . $v . '\'') {
                        $trimmed = true;
                        echo $v . BR;
                        echo $v1 . BR;
                    }

                    $v1 = trim($v1, '\'');

                    if ($email_exists) {

                        switch ($cols) {
                            case 0:

                                if ($members2[$current_email]['firstname'] !== $v) {
                                    // echo $members2[$current_email]['firstname'] . ' ### ' . $v1 . BR;
                                    $b_need_update = true;
                                }

                                break;

                            case 1:

                                if ($members2[$current_email]['lastname'] !== $v) {
                                    $b_need_update = true;
                                }

                                break;

                            case 2:

                                if ($members2[$current_email]['home'] !== $v) {
                                    $b_need_update = true;
                                }

                                break;

                            case 3:

                                if ($members2[$current_email]['cell'] !== $v) {
                                    $b_need_update = true;
                                }

                                break;

                            case 4:

                                if ($members2[$current_email]['work'] !== $v) {
                                    $b_need_update = true;
                                }

                                break;

                        }

                        if ($b_need_update) {
                        }

                    }

                    $cols++;

                    if ($cols > 6) {

                        echo 'Data validation failed on line # ' . $line . BR;
                        echo 'Columns: '. $cols . BR;
                        echo 'Check for ";" in the data. ";" cannot be part of the data.' . BR;
                        echo 'Process terminated.' . BR;

                        fclose($handle);
                        unlink($file);
                        die();

                    }

                }

                if ($b_need_update) {

                    $need_update[] = $current_email;

                    $members2[$current_email] = [
                        'firstname' =>  trim($pdo->quote($data[0]),'\''),
                        'lastname'  =>  trim($pdo->quote($data[1]),'\''),
                        'home'      =>  trim($pdo->quote($data[2]),'\''),
                        'cell'      =>  trim($pdo->quote($data[3]),'\''),
                        'work'      =>  trim($pdo->quote($data[4]),'\''),
                    ];
                }
            }

        }

        if ($trimmed) {
            echo 'Warning: Some data needed to be escaped!' . BR;
        }

        fclose($handle);


        // UPDATE members3 m
        // JOIN (
        //     SELECT 'a' as email, '10' as _firstname, '20' as _lastname
        //     UNION ALL
        //     SELECT 'dsoudakov@gmail.com', 'Dmitri2', 'Soudakov2'
        //     UNION ALL
        //     SELECT 'dmitri@soudakov.com', 'Dmitri2', 'Soudakov2'
        // ) vals ON m.email = vals.email
        // SET firstname = _firstname, lastname = _lastname, home = _home, cell = _cell, work = _work;

        //dump($new_members);

        $emailCount = count($emails_to_do);
        $uniqueEmailCount = count(array_unique($emails_to_do));

        if ($emailCount !== $uniqueEmailCount) {

            echo 'Provided CSV has duplicates in the field Email. Please remove.' . BR;
            echo 'Process terminated.' . BR;
            unlink($file);
            die();

        }


        $time1 = microtime(true);

        if ($new_members) {

            $sql_insert = 'INSERT INTO ' . $table_name . ' (firstname,lastname,home,cell,work,email) VALUES ';

            foreach ($new_members as $k => $v) {
                $sql_insert .= '(\'' . implode('\',\'', $v) . '\',\'' . $k . '\'),';

            }

            $sql_insert = rtrim($sql_insert, ',');

            //echo $sql_insert . BR . BR;

            R::begin();

            try {

                $ret = R::exec($sql_insert);

                R::commit();

                echo 'added records: ' . $ret . BR;

                $time2 = microtime(true);

                echo 'inserted in: ' . ($time2 - $time1) . ' sec ' . BR;

            } catch (\Exception $e) {

                echo 'errors adding new members...' . BR;
                echo $e->getMessage() . BR;

                R::rollback();
            }
        }

        $time1 = microtime(true);

        if ($need_update) {

            $sql_update = 'UPDATE ' . $table_name . ' m JOIN ( SELECT \'a\' as email, \'10\' as _firstname, \'20\' as _lastname, \'30\' as _home, \'40\' as _cell, \'50\' as _work ' ;

            // dump($need_update);
            foreach ($need_update as $email) {

                $sql_update .= 'UNION ALL SELECT \'' . $email . '\', \'' . implode('\',\'', $members2[$email]) . '\' ';
            }

            $sql_update .= ') vals ON m.email = vals.email SET firstname = _firstname, lastname = _lastname, home = _home, cell = _cell, work = _work;';
            // echo $sql_update . BR . BR;

            R::begin();

            try {

                $ret = R::exec($sql_update);

                R::commit();

                echo 'updated records: ' . $ret . BR;

                $time2 = microtime(true);

                echo 'updated in: ' . ($time2 - $time1) . ' sec ' . BR;

            } catch (\Exception $e) {

                echo 'errors updating members...' . BR;
                echo $e->getMessage() . BR;

                R::rollback();
            }
        }

        unlink($file);

        $emailsInDB = array_keys($members2);

        $sql_select = '(\'' . implode('\',\'', $emails_to_do) . '\')';

        $emails_to_delete = R::getAll('select email from ' . $table_name . ' where email NOT IN ' . $sql_select);

        if ($emails_to_delete) {

            foreach ($emails_to_delete as $a) {
                $emails_to_delete2[] = $a['email'];
            }

            echo 'These users will be deleted: ' . BR . BR . implode(BR, $emails_to_delete2) . BR . BR;

            $time1 = microtime(true);

            R::begin();

            try {

                $ret = R::exec('DELETE FROM ' . $table_name . ' where email NOT IN ' . $sql_select);

                R::commit();

                echo 'deleted records: ' . $ret . BR;

                $time2 = microtime(true);

                echo 'deleted in: ' . ($time2 - $time1) . ' sec ' . BR;

            } catch (\Exception $e) {

                echo 'errors deleting members...' . BR;
                echo $e->getMessage() . BR;

                R::rollback();
            }

        }

        echo 'Done.' . BR;

    }

})->setName('upload.members.post')->add($authenticated)->add($isAdmin)->add(new GenCsrf);
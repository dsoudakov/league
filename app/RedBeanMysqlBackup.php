<?php
/**
 * RedBean Mysql Backup
 *
 * @file    RedBeanMysqlBackup.php
 * @desc    Generates a backup file of your database
 * @author  Zewa666
 *
 */
use CATL\R;

class RedBean_MysqlBackup
{
    /**
     * Creates a file backup of all tables from the connected Database
     *
     * @param  string $outputFolder              The folder where to put the newly created backup-file
     * @param  string $backupName = 'auto'       The name of the new backup file
     */
    public static function performMysqlBackup($outputFolder, $backupName = "auto")
    {
        // if(!(R::getWriter() instanceof RedBean_QueryWriter_MySQL))
        // {
        //     throw new Exception("This plugin only supports MySql.");
        // }

        if(!file_exists($outputFolder))
        {

            throw new Exception("Outputfolder does not exist, please create it manually. Current dir: (" . getcwd() . ")");
        }

        $write = "";
        $tables = R::inspect();

        foreach($tables as $table)
        {
            $pdo = R::getDatabaseAdapter()->getDatabase()->getPDO();
            $query = $pdo->prepare('SELECT * FROM '.$table);
            $query->execute();
            /*$result = $query->fetchAll();*/
            $fields = R::inspect($table);
            $num_fields = count($fields);

            $write .= '# DROP TABLE '.$table.';';
            $row2 = R::getRow('SHOW CREATE TABLE '.$table);
            $write .= "\n\n".$row2['Create Table'].";\n\n";


            /*foreach($result as $row)*/
            $i = 0;
            do
            {
                $i++;

                if ($i == 1) {
                    $write .= '# INSERT INTO '.$table.' VALUES(';    
                } else {
                    $write .= 'INSERT INTO '.$table.' VALUES(';
                }
                
                $parts = array();
                foreach($fields as $key => $field)
                {
                    if($row[$key] == null)
                        $parts[] = 'NULL';
                    else
                        $parts[] = '"'.$row[$key].'"';
                }

                $write .=  implode(",", $parts) . ");\n";
            } while ($row = $query->fetch());

            $write .="\n\n\n";
        }

        if($backupName == "auto") {
            $backupName = 'db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql';
            $handle = fopen($outputFolder . '/' . $backupName,'w+');
        } else {
            $handle = fopen($outputFolder . "/" . $backupName,'w+');
        }
        fwrite($handle,$write);
        fclose($handle);

        return $backupName;
    }
}

// add plugin to RedBean facade
R::ext( 'performMysqlBackup', function($outputFolder, $backupName = "auto") {
    return RedBean_MysqlBackup::performMysqlBackup($outputFolder, $backupName);
} );

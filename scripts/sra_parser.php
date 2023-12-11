<?php

// Set memory limit to 50G
ini_set('memory_limit', '50G');

/////////////////////////////////////////////////////
##
$dbhost=getenv("dbhost");
$dbuser=getenv("dbuser");
$dbpwd=getenv("dbpwd");
$dbname=getenv("dbname");

include("sra_xpath.php");
include_once("/Users/zhujack/Sites/gbnci/system/adodb/adodb.inc.php");
$sra_CONN = NewADOConnection('mysqli'); 
$sra_CONN->PConnect($dbhost, $dbuser, $dbpwd, $dbname); 
$sra_CONN->SetFetchMode(ADODB_FETCH_NUM);


///////////////////////////////////////////////
//get accessions needed to be added or updated ('analysis' not included)
$data_types = array('submission','study', 'sample', 'experiment','run');
//$data_types = array('sample', 'experiment','run');
//$bam_update = 'yes';

//Update all the record updated since two weeks ago.
$someDaysBack = time() - (14 * 24 * 60 * 60);

if ($dbname != "sra_test") $Date = strftime("%Y-%m-%d", $someDaysBack);
else $Date = '2000-12-04'; //update all of the records

$otherCondition = '';

////test
// $otherCondition = " AND Submission = 'DRA000001' ";


/////////////////////////////////////////////////////
$newAcc = $sra_CONN->GetAll("SELECT distinct LOWER(`Type`),`Submission` as acc_sub FROM `SRA_Accessions` WHERE `Status`='live' AND Type IN ('" . join("','", $data_types) . "') AND `Updated` >='" . $Date . "' $otherCondition  ORDER BY `Type`, `Submission`; ");

////test
//SELECT distinct LOWER(`Type`),`Submission` as acc_sub FROM `SRA_Accessions` WHERE `Status`='live' AND Type IN ('submission','study', 'sample', 'experiment','run') AND `Updated` >='2000-12-04' AND Submission = 'SRA048030'
// submission  SRA048030
// run SRA048030
echo "SELECT distinct LOWER(`Type`),`Submission` as acc_sub FROM `SRA_Accessions` WHERE `Status`='live' AND Type IN ('" . join("','", $data_types) . "') AND `Updated` >='" . $Date . "' $otherCondition  ORDER BY `Type`, `Submission`; ";

//echo "<pre>" . print_r($newAcc, true) . "</pre>";
//$newAcc = array('run'=>'SRA048926');
//echo "SELECT distinct '" . $data_type . "',`submission_accession` as acc_sub FROM `"  . $data_type . "` WHERE `alias` IS NULL";


///////////////////////////////////////////////////
echo "\n Start Update of >= $Date @" . strftime("%Y-%m-%d %H:%M:%S", time());
echo "\n total: ". count($newAcc) . "\n";

//Count number of added or updated records;
$n_insert=0;
$n_update=0;
			
foreach($newAcc as $newAcc_1) {
    $data_type = $newAcc_1[0];
    $acc_sub = $newAcc_1[1];;
    //$xml_file = "ftp://ftp.ncbi.nih.gov/sra/Submissions/" . substr($acc_sub, 0, 6) . '/' . $acc_sub . '/' . $acc_sub . '.' . $data_type . '.xml';
    $xml_file = "download/xml/" . $acc_sub . '/' . $acc_sub . '.' . $data_type . '.xml';

    $data_type_upper = strtoupper($data_type);
    //echo $xml_file . "\n";
    $xml = simplexml_load_file($xml_file);
    //echo "<pre>" . print_r($xml, true) . "</pre>";
    //echo count($xml) . "\n";
    if ( !isset($xml) || empty($xml) ) {
        echo "\nProblem with parsing: $xml_file \n";
        continue;
    }

    $submission_accession = preg_replace( "/\..*$/", '', basename($xml_file) );
    $p_cnt = count($xml->$data_type_upper);
    if( !$p_cnt) $p_cnt = 1;
    //echo $p_cnt;
    //echo "<pre>" . print_r($xml, true) . "</pre>";
    //echo $xml_file . "\n";
    //Generate a array with entry x field array

    $n_insert_type=0;
    $n_update_type=0;

    for($i = 0; $i < $p_cnt; $i++) {
        $result = array();
        //echo "<pre>" . print_r($xpath_sra[$data_type], true) . "</pre>";
        foreach($xpath_sra[$data_type] as $key => $xpath) {
            //echo $key;
            if ($data_type == 'study') {
                if ( !$xml->STUDY[$i] ) {
                    $values = $xml->xpath("{$xpath}");
                } else {$values = $xml->STUDY[$i]->xpath("{$xpath}");}
            }
            else if ($data_type == 'sample') {
                if ( !$xml->SAMPLE[$i] ) {
                    $values = $xml->xpath("{$xpath}");
                } else {$values = $xml->SAMPLE[$i]->xpath("{$xpath}");}
            }
            else if ($data_type == 'experiment') {
                if ( !$xml->EXPERIMENT[$i] ) {
                    $values = $xml->xpath("{$xpath}");
                } else { $values = $xml->EXPERIMENT[$i]->xpath("{$xpath}"); }
            }
            else if ($data_type == 'run') {
                if ( !$xml->RUN[$i] ) {
                    $values = $xml->xpath("{$xpath}");
                } else { $values = $xml->RUN[$i]->xpath("{$xpath}"); }
            }
            else if ($data_type == 'submission') {
                if ( !$xml->SUBMISSION[$i] ) {
                    $values = $xml->xpath("{$xpath}");
                } else { $values = $xml->SUBMISSION[$i]->xpath("{$xpath}"); }
            }

            //if ($key == 'platform') echo "<pre>" . print_r($values, true) . "</pre>";
            if ( $key == 'platform' ) {
                //$children_node = $values->children();
                $result[$key] = $values[0]->children()->getName();
                //echo $result[$key];
            } else if ( $key == 'library_layout' ) {
                $result[$key] = $values[0]->children()->getName() . " - ";
                foreach($values[0]->children()->attributes() as $k=>$a) $result[$key] .= "$k: $a; ";
                //echo $result[$key];
            }
            else if(!empty($values) && in_array($key, $concat_nodes)) {
                //concatenate children values
                $k_v = array();
                foreach($values as $value) {
                    $k_v1 = array();
                    //don't use tag names in attribute and url
                    if ( preg_match("/_link$/", $key) || preg_match("/_attribute$/", $key) ) {
                        foreach($value->children() as $v) $k_v1[] = $v;
                        $k_v [] = join(': ', $k_v1);
                    } else if ($key == 'library_layout') {
                        $result[$key] = $value[0]->children()->getName();
                        //foreach($values[0]->attributes() as $k=>$a) echo $k . $a;
                    }
                    else {
                        foreach($value->children() as $v) $k_v1[] = $v->getName() . ': ' . $v;
                        $k_v [] = join('; ', $k_v1);
                    }
                }
                if(!empty($k_v)) $result[$key] = join(' || ', $k_v);
            }
            else if ($key == 'run_date') {
                $result[$key] = preg_replace("/T.*$/", '', (string)$values[0]);
            }
            else if ($key == 'submission_date') {
                $result[$key] = preg_replace("/T.*$/", '', (string)$values[0]);
            }
            else if ( $key == 'files' && !empty($values) ) {
                $k_v = array();
                //foreach($values as $value) $k_v [] = $value['filename'] . ' :: FILETYPE: ' . $value['filetype'];
                foreach($values as $value) $k_v [] = $value['filename'];
                if(!empty($k_v)) $result[$key] = join(' ', $k_v);
            }
            else {
                if((string)$values[0] != '') $result[$key] = (string)$values[0];
            }//if(!empty($values) && in_array($key, $concat_nodes)) {
        }//foreach($xpath_sra[$data_type] as $key => $xpath)
        //echo "<pre>" . print_r($result, true) . "</pre>";

        if ($data_type == 'run') {

            $data_block_cnt = count($xml->RUN[$i]->DATA_BLOCK);
            //echo $data_block_cnt;
            $data_block = array();
            for($j = 0; $j < $data_block_cnt; $j++) {

                $data_block1 = array();
                foreach($xpath_sra['data_block'] as $key => $xpath) {
                    //echo "<pre>" . print_r($xml->RUN[$i]->DATA_BLOCK[$j], true) . "</pre>";
                    $values = $xml->RUN[$i]->DATA_BLOCK[$j]->xpath("{$xpath}");
                    //echo "<pre>" . print_r($values, true) . "</pre>";
                    if($key == 'files' && !empty($values) ) {
                        //concatenate children values
                        $k_v = array();
                        //foreach($values as $value) $k_v [] = $value['filename'] . ' :: FILETYPE: ' . $value['filetype'];
                        foreach($values as $value) $k_v [] = 'filename: ' . $value['filename'] . '; filetype: ' . $value['filetype'];

                        if(!empty($k_v)) $data_block1[$key] = join(' || ', $k_v);
                        //echo "<pre>" . print_r($$data_block1, true) . "</pre>";
                    } else {
                        if((string)$values[0] != '') $data_block1[$key] = (string)$values[0];
                    }//if(!empty($values) && in_array($key, $concat_nodes))
                    $data_block1['run_accession'] = $result['accession'];
                }//foreach($xpath_sra['data_block'] as $key => $xpath)

                $data_block[] = $data_block1;
                //echo "<pre>" . print_r($data_block, true) . "</pre>";

            }//for($j = 0; $j < $data_block_cnt; $j++)
        }//if ($data_type == 'run') {

        if( isset($result) && !empty($result) ) {
          $record = $result;
          //Insert into database
          $sra_CONN->StartTrans();
          //add submission_accession
          if ( $data_type != 'submission' ) $record['submission_accession'] = $submission_accession;
          //echo "<pre>" . print_r($record, true) . "</pre>";
          $sra_CONN->Execute("SET sql_mode = 'STRICT_ALL_TABLES';");
          if($sra_CONN->AutoExecute($data_type, $record,'INSERT') === true)  {
              $n_insert++;
              $n_insert_type++;
              //echo "Added $n_insert. ". $record["accession"] . "\n";
          } else {//update records
              $accession = $record["accession"];
              unset($record["accession"]);
              if( $sra_CONN->AutoExecute($data_type, $record, 'UPDATE', "accession='" . $accession . "'" ) === false)  {
                //echo "<pre>" . print_r($record, true) . "</pre>";
                echo $sra_CONN->ErrorMsg()."\n";
                echo $xml_file.'\n';
              }
              else {
                  $n_update++;
                  $n_update_type++;
                  
                  //echo "Updated $n_update. $accession\n";
              }
          }
          $sra_CONN->CompleteTrans();
        }

    }//for($i = 0; $i < $p_cnt; $i++) {
    
    // echo "\n$acc_sub - $data_type";
    // echo "Added: $n_insert_type ";
    // echo "Updated: $n_update_type ";

    //echo "<pre>" . print_r($result, true) . "</pre>";
    //unset($data_block);
    //unset($result);

    //Write to a file and then update the database
    /*
    $handle = fopen('exp_accession_library_layout.txt', "a");
    $file_str = '';
    foreach($result as $record) {
        //print_r($record);
        $file_str .= $record['accession'] . "\t" . $record['library_layout'] . "\n";
        fwrite($handle, $file_str);
        //echo $file_str . "\t";
    }
    fclose($handle);
    //echo "<pre>" . print_r($record, true) . "</pre>";
    */


}//foreach($data_types as $data_type)
echo "\nTotal added: $n_insert\n";
echo "\nTotal updated: $n_update\n";

// //////////////////////////////////////////////
// //update bam file information in the db
//
// if ( $bam_update == 'yes' ) {
//     $query = "SELECT DISTINCT `accession` as SRR, `experiment_accession` as SRX FROM `sra`.`run`";
//     $SRR_SRX = $sra_CONN->GetAssoc("$query");
//     $bam = glob("../files/*sorted.bam");
//     //$bam = array_slice($bam,0,5);
//     //print_r($bam);
//
//     foreach($bam as $bam_1) {
//         $bam_file = basename($bam_1);
//         $accession = preg_replace('/\..*$/','', $bam_file);
//         if( preg_match('/^[SED]RR/', $bam_file) ) {
//             if( $sra_CONN->Execute("UPDATE `sra`.`run` SET `bamFile`='".$bam_file."' WHERE `accession`='" . $accession . "'" ) === false)  echo $sra_CONN->ErrorMsg().' - $bam_file \n';
//             $SRX_accession = $SRR_SRX[$accession];
//             if ( count(array_keys($SRR_SRX, $SRX_accession ) ) == 1 ) {
//                 if( $sra_CONN->Execute("UPDATE `sra`.`experiment` SET `bamFile`='".$bam_file."' WHERE `accession`='" . $SRX_accession . "'" ) === false)  echo $sra_CONN->ErrorMsg().' - $bam_file \n';
//             }
//         }
//         if ( preg_match('/^[SED]RX/', $bam_file) ) {
//             if( $sra_CONN->Execute("UPDATE `sra`.`experiment` SET `bamFile`='".$bam_file."' WHERE `accession`='" . $accession . "'" ) === false)  echo $sra_CONN->ErrorMsg().' - $bam_file \n';
//         }//if( preg_match('/^SRR/', $bam_file) {;
//     }
//     echo "Done with bam updating";
// }//Update bam fields

?>

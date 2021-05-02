<?php

$debugging = true;

if ($debugging) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

include_once('config.php');
require_once('db.php');

/* Graphing Functionality */
require_once('includes/lib/jpgraph-4.3.3/src/jpgraph.php');
require_once('includes/lib/jpgraph-4.3.3/src/jpgraph_line.php');
require_once('includes/lib/jpgraph-4.3.3/src/jpgraph_date.php');
require_once('includes/lib/jpgraph-4.3.3/src/jpgraph_utils.inc.php');

$version = '20201106-01';
$db = new dbfunctions($dbconfig);

clean_temp_files();

function testdbconnection() {
    global $db;

    return $db->testconnection();
}

function processPageParams() {
    $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    $pagedays= isset($_REQUEST['pagedays']) ? $_REQUEST['pagedays'] : 90;
    return array($page, $pagedays);
}

function get_blood_stats($page = 1, $pagedays = 90) {
    global $db;
    return $db->get_blood_stats($page, $pagedays);
}

function reprocess_input() {
    global $db;

    $statistics = null;
    // Autoload the spreadsheetjpgraph/jpgraph_utils.inc.php
    require 'vendor/autoload.php';
    $inputfile = 'bloodstuff_monitor.ods';

    $reader = new PhpOffice\PhpSpreadsheet\Reader\Ods();
    $reader->setReadDataOnly(false);
    if (file_exists($inputfile)) {
        try {
            /** Load $inputFileName to a Spreadsheet Object  **/
            $spreadsheet = $reader->load($inputfile);
            //$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('bloodstuff_monitor.ods');
        } catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            die($e->getMessage());
        }

        // Trucate all tables
        $db->clear_statistics();

        foreach(array('Stats', 'Alcohol', 'Tablets') as $sheetname) {

            try {
                $worksheet = $spreadsheet->getSheetByName($sheetname);
            } catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
                echo "Failed to load sheet: $sheetname: " . $e->getMessage();
                continue;
            }

            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            if ($sheetname == 'Alcohol') {
                $highestColumn = 'C';
            }
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            $startrow = 2;
            if ($sheetname == 'Stats') {
                $startrow = 4;
                $bstablename = 'bloodbs';
                $bptablename = 'bloodbp';

                $fields = array(
                    "2" => 'thedate',
                    "3" => 'bstime',
                    "4" => 'bsreading',
                    "5" => 'bptime',
                    "6" => 'Systolic1',
                    "7" => 'Diastolic1',
                    "8" => 'Pulse1',
                    "9" => 'Systolic2',
                    "10" => 'Diastolic2',
                    "11" => 'Pulse2',
                    "12" => 'Systolic3',
                    "13" => 'Diastolic3',
                    "14" => 'Pulse3',
                    "15" => 'BP3avg',
                    "16" => 'BP2avg',
                    "17" => 'Notes'
                );

                $bsflds = array("2", "3", "4", "17");
                $bpflds = array("2", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17");

                $lastdate = '';
                for ($row = $startrow; $row <= $highestRow; ++$row) {
                    $sqlbs = "INSERT INTO $bstablename SET\n";
                    $sqlbp = "INSERT INTO $bptablename SET\n";
                    for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                        $bschg = false;
                        $bpchg = false;
                        if (isset($fields[$col])) {
                            $cell = $worksheet->getCellByColumnAndRow($col, $row);

                            if ($cell->isFormula()) {
                                try {
                                    $val =  $cell->getCalculatedValue();
                                } catch (PhpOffice\PhpSpreadsheet\Calculation\Exception $e) {
                                    $val = 0;
                                }

                                if (!$val) {
                                    $divisor = 0;
                                    $s = 0;
                                    $d = 0;
                                    if ($fields[$col] == 'BP3avg') {
                                        $scell = $worksheet->getCellByColumnAndRow(array_search('Systolic1', $fields), $row);
                                        $dcell = $worksheet->getCellByColumnAndRow(array_search('Diastolic1', $fields), $row);
                                        if (($sval = $scell->getValue()) && ($dval = $dcell->getValue())) {
                                            $divisor++;
                                            $s = (int) $sval;
                                            $d = (int) $dval;
                                        }
                                    }
                                    if ($fields[$col] == 'BP3avg' || $fields[$col] == 'BP2avg') {
                                        $scell = $worksheet->getCellByColumnAndRow(array_search('Systolic2', $fields), $row);
                                        $dcell = $worksheet->getCellByColumnAndRow(array_search('Diastolic2', $fields), $row);
                                        if (($sval = $scell->getValue()) && ($dval = $dcell->getValue())) {
                                            $divisor++;
                                            $s = $s + (int) $sval;
                                            $d = $d + (int) $dval;
                                        }
                                        $scell = $worksheet->getCellByColumnAndRow(array_search('Systolic3', $fields), $row);
                                        $dcell = $worksheet->getCellByColumnAndRow(array_search('Diastolic3', $fields), $row);
                                        if (($sval = $scell->getValue()) && ($dval = $dcell->getValue())) {
                                            $divisor++;
                                            $s = $s + (int) $sval;
                                            $d = $d + (int) $dval;
                                        }
                                    }

                                    if ($divisor) {
                                        //$val = $s . '/' . $d;
                                        $val = (ceil($s/$divisor)) . '/' . (ceil($d/$divisor));
                                    }

                                    if (in_array($col, $bsflds)) {
                                        $sqlbs .= $fields[$col] . ' = "' . $val . '"';
                                        $bschg = true;
                                    }
                                    if (in_array($col, $bpflds)) {
                                        $sqlbp .= $fields[$col] . ' = "' . $val . '"';
                                        $bpchg = true;
                                    }

                                } else if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                                    $val = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($val);
                                    if (in_array($col, $bsflds)) {
                                        $sqlbs .= $fields[$col] . " = FROM_UNIXTIME($val)";
                                        $bschg = true;
                                    }
                                    if (in_array($col, $bpflds)) {
                                        $sqlbp .= $fields[$col] . " = FROM_UNIXTIME($val)";
                                        $bpchg = true;
                                    }

                                } else {
                                    if (in_array($col, $bsflds)) {
                                        $sqlbs .= $fields[$col] . " = '$val'";
                                        $bschg = true;
                                    }
                                    if (in_array($col, $bpflds)) {
                                        $sqlbp .= $fields[$col] . " = '$val'";
                                        $bpchg = true;
                                    }

                                }
                            } else if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {     // Special Case for Date Time

                                $val = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($cell->getValue());

                                if ($val) {
                                    if ($fields[$col] == 'thedate') {
                                        $lastdate = $val;
                                    }
                                } else {
                                    $val = $lastdate;
                                }

                                if (in_array($col, $bsflds)) {
                                    $sqlbs .= $fields[$col] . " = FROM_UNIXTIME($val)";
                                    $bschg = true;
                                }
                                if (in_array($col, $bpflds)) {
                                    $sqlbp .= $fields[$col] . " = FROM_UNIXTIME($val)";
                                    $bpchg = true;
                                }

                            } else {
                                if ($fields[$col] == 'thedate' && $lastdate) {
                                    if (in_array($col, $bsflds)) {
                                        $sqlbs .= $fields[$col] . " = FROM_UNIXTIME($lastdate)";
                                        $bschg = true;
                                    }
                                    if (in_array($col, $bpflds)) {
                                        $sqlbp .= $fields[$col] . " = FROM_UNIXTIME($lastdate)";
                                        $bpchg = true;
                                    }
                                } else {
                                    $val = $cell->getValue();
                                    // Fix potential problems.
                                    if ($val == '') {
                                        $val = null;
                                    } else if (substr($val, 0, 1) == '?') {
                                        $val = null;
                                    } else if (substr($val, 0, 1) == '~') {
                                        $val = intval(str_replace('~', '', $val));
                                    }
                                    if ($val !== null) {
                                        if (in_array($col, $bsflds)) {
                                            $sqlbs .= $fields[$col] . " = '$val'";
                                            $bschg = true;
                                        }
                                        if (in_array($col, $bpflds)) {
                                            $sqlbp .= $fields[$col] . " = '$val'";
                                            $bpchg = true;
                                        }
                                    }
                                }
                            }

                            if ($col < $highestColumnIndex) {
                                if ($bschg) {
                                    $sqlbs .= ",\n";
                                }
                                if ($bpchg) {
                                    $sqlbp .= ",\n";
                                }
                            }

                        }
                    }

                    $sqlbs = preg_replace('/,$/', '', $sqlbs);
                    $sqlbp = preg_replace('/,$/', '', $sqlbp);

                    $db->mysqli->query($sqlbs);
                    if ($db->mysqli->error) {
                        echo "<pre>$sqlbs</pre>\n";
                        echo( "<p>$col $row  (" . $db->mysqli->errno . ") " . $db->mysqli->error . "</p>\n" );
                    }
                    $db->mysqli->query($sqlbp);
                    if ($db->mysqli->error) {
                        echo "<pre>$sqlbp</pre>\n";
                        echo( "<p>$col $row  (" . $db->mysqli->errno . ") " . $db->mysqli->error . "</p>\n" );
                    }
                }
                //continue;
            } else if ($sheetname == 'Alcohol') {
                $tablename = 'alcohol';
                $fields = array(
                    '2' => 'thedate',
                    '3' => 'units'
                );
                $lastdate = '';
                for ($row = $startrow; $row <= $highestRow; ++$row) {
                    $sql = "INSERT INTO $tablename SET\n";
                    for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                        if (isset($fields[$col])) {
                            $cell = $worksheet->getCellByColumnAndRow($col, $row);

                            if ($cell->isFormula()) {
                                try {
                                    $val =  $cell->getCalculatedValue();
                                } catch (PhpOffice\PhpSpreadsheet\Calculation\Exception $e) {
                                    $val = 0;
                                }


                                if (!$val) {
                                    $divisor = 0;
                                    $s = 0;
                                    $d = 0;
                                    if ($fields[$col] == 'BP3avg') {
                                        $scell = $worksheet->getCellByColumnAndRow(array_search('Systolic1', $fields), $row);
                                        $dcell = $worksheet->getCellByColumnAndRow(array_search('Diastolic1', $fields), $row);
                                        if (($sval = $scell->getValue()) && ($dval = $dcell->getValue())) {
                                            $divisor++;
                                            $s = (int) $sval;
                                            $d = (int) $dval;
                                        }
                                    }
                                    if ($fields[$col] == 'BP3avg' || $fields[$col] == 'BP2avg') {
                                        $scell = $worksheet->getCellByColumnAndRow(array_search('Systolic2', $fields), $row);
                                        $dcell = $worksheet->getCellByColumnAndRow(array_search('Diastolic2', $fields), $row);
                                        if (($sval = $scell->getValue()) && ($dval = $dcell->getValue())) {
                                            $divisor++;
                                            $s = $s + (int) $sval;
                                            $d = $d + (int) $dval;
                                        }
                                        $scell = $worksheet->getCellByColumnAndRow(array_search('Systolic3', $fields), $row);
                                        $dcell = $worksheet->getCellByColumnAndRow(array_search('Diastolic3', $fields), $row);
                                        if (($sval = $scell->getValue()) && ($dval = $dcell->getValue())) {
                                            $divisor++;
                                            $s = $s + (int) $sval;
                                            $d = $d + (int) $dval;
                                        }                $lastdate = '';
                                        for ($row = $startrow; $row <= $highestRow; ++$row) {
                                            $sql = "INSERT INTO $tablename SET\n";
                                            for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                                                if (isset($fields[$col])) {
                                                    $cell = $worksheet->getCellByColumnAndRow($col, $row);

                                                    if ($cell->isFormula()) {
                                                        try {
                                                            $val =  $cell->getCalculatedValue();
                                                        } catch (PhpOffice\PhpSpreadsheet\Calculation\Exception $e) {
                                                            $val = 0;
                                                        }
                                                        if (!$val) {
                                                            $divisor = 0;
                                                            $s = 0;
                                                            $d = 0;
                                                            if ($fields[$col] == 'BP3avg') {
                                                                $scell = $worksheet->getCellByColumnAndRow(array_search('Systolic1', $fields), $row);
                                                                $dcell = $worksheet->getCellByColumnAndRow(array_search('Diastolic1', $fields), $row);
                                                                if (($sval = $scell->getValue()) && ($dval = $dcell->getValue())) {
                                                                    $divisor++;
                                                                    $s = (int) $sval;
                                                                    $d = (int) $dval;
                                                                }
                                                            }
                                                            if ($fields[$col] == 'BP3avg' || $fields[$col] == 'BP2avg') {
                                                                $scell = $worksheet->getCellByColumnAndRow(array_search('Systolic2', $fields), $row);
                                                                $dcell = $worksheet->getCellByColumnAndRow(array_search('Diastolic2', $fields), $row);
                                                                if (($sval = $scell->getValue()) && ($dval = $dcell->getValue())) {
                                                                    $divisor++;
                                                                    $s = $s + (int) $sval;
                                                                    $d = $d + (int) $dval;
                                                                }
                                                                $scell = $worksheet->getCellByColumnAndRow(array_search('Systolic3', $fields), $row);
                                                                $dcell = $worksheet->getCellByColumnAndRow(array_search('Diastolic3', $fields), $row);
                                                                if (($sval = $scell->getValue()) && ($dval = $dcell->getValue())) {
                                                                    $divisor++;
                                                                    $s = $s + (int) $sval;
                                                                    $d = $d + (int) $dval;
                                                                }
                                                            }

                                                            if ($divisor) {
                                                                //$val = $s . '/' . $d;
                                                                $val = (ceil($s/$divisor)) . '/' . (ceil($d/$divisor));
                                                            }

                                                            $sql .= $fields[$col] . ' = "' . $val . '"';

                                                        } else if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                                                            $val = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($val);
                                                            $sql .= $fields[$col] . " = FROM_UNIXTIME($val)";
                                                        } else {
                                                            $sql .= $fields[$col] . " = '$val'";
                                                        }
                                                    } else if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {     // Special Case for Date Time

                                                        $val = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($cell->getValue());

                                                        if ($val) {
                                                            if ($fields[$col] == 'thedate') {
                                                                $lastdate = $val;
                                                            }
                                                        } else {
                                                            $val = $lastdate;
                                                        }

                                                        $sql .= $fields[$col] . " = FROM_UNIXTIME($val)";
                                                    } else {
                                                        if ($fields[$col] == 'thedate' && $lastdate) {
                                                            $sql .= $fields[$col] . " = FROM_UNIXTIME($lastdate)";
                                                        } else if ($sheetname == 'Alcohol') {
                                                            $val = $cell->getValue();
                                                            // Fix potential problems.
                                                            if ($val == '') {
                                                                $val = null;
                                                            } else if (substr($val, 0, 1) == '?') {
                                                                $val = 0;
                                                                $sql .= "unknown = 1,\n";
                                                            } else if (substr($val, 0, 1) == '~') {
                                                                $val = intval(str_replace('~', '', $val));
                                                                $sql.= "estimate = 1,\n";
                                                            }
                                                            if ($val !== null) {
                                                                $sql .= $fields[$col] . " = '$val'";
                                                            }
                                                        } else {
                                                            $val = $cell->getValue();
                                                            if (!$val) {
                                                                $val = 0;
                                                            }
                                                            $sql .= $fields[$col] . " = '$val'";
                                                        }
                                                    }

                                                    if ($col < $highestColumnIndex) {
                                                        $sql .= ",\n";
                                                    }

                                                }
                                            }
                                            $db->mysqli->query($sql);
                                            if ($db->mysqli->error) {
                                                echo "<pre>$sql</pre>\n";
                                                echo( "<p>$col $row  (" . $db->mysqli->errno . ") " . $db->mysqli->error . "</p>\n" );
                                            }
                                        }
                                    }

                                    if ($divisor) {
                                        //$val = $s . '/' . $d;
                                        $val = (ceil($s/$divisor)) . '/' . (ceil($d/$divisor));
                                    }

                                    $sql .= $fields[$col] . ' = "' . $val . '"';

                                } else if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                                    $val = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($val);
                                    $sql .= $fields[$col] . " = FROM_UNIXTIME($val)";
                                } else {
                                    $sql .= $fields[$col] . " = '$val'";
                                }
                            } else if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {     // Special Case for Date Time

                                $val = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($cell->getValue());

                                if ($val) {
                                    if ($fields[$col] == 'thedate') {
                                        $lastdate = $val;
                                    }
                                } else {
                                    $val = $lastdate;
                                }

                                $sql .= $fields[$col] . " = FROM_UNIXTIME($val)";
                            } else {
                                if ($fields[$col] == 'thedate' && $lastdate) {
                                    $sql .= $fields[$col] . " = FROM_UNIXTIME($lastdate)";
                                } else if ($sheetname == 'Alcohol') {
                                    $val = $cell->getValue();
                                    if ((strpos($val, '~') !== false)) {
                                        $val = intval(str_replace('~', '', $val));
                                        $sql.= "estimate = 1,\n";
                                    } else if ((strpos($val, '?') !== false)) {
                                        $val = 0;
                                        $sql .= "unknown = 1,\n";
                                    } else if ($val == '') {
                                        $val = 0;
                                    }

                                    $sql .= $fields[$col] . " = '$val'";

                                } else {
                                    $val = $cell->getValue();
                                    if (!$val) {
                                        $val = 0;
                                    }
                                    $sql .= $fields[$col] . " = '$val'";
                                }
                            }

                            if ($col < $highestColumnIndex) {
                                $sql .= ",\n";
                            }

                        }
                    }
                    $db->mysqli->query($sql);
                    if ($db->mysqli->error) {
                        echo "<pre>$sql</pre>\n";
                        echo( "<p>$col $row  (" . $db->mysqli->errno . ") " . $db->mysqli->error . "</p>\n" );
                    }
                }
            } else if ($sheetname == 'Tablets') {
                $tablename = 'medication';
                $fields = array(
                    'medication' => '',
                    'thedate' => 2,
                    'AM' => '',
                    'Midday' => '',
                    'PM' => '',
                );

                for ($row = $startrow; $row <= $highestRow; ++$row) {
                    $sql1 = "INSERT INTO $tablename SET\n";
                    $sql2 = $sql1;
                    for ($col = 1; $col <= $highestColumnIndex; ++$col) {

                        $cell = $worksheet->getCellByColumnAndRow($col, $row);

                        if ($col == 2) {
                            if ($cell->isFormula()) {
                                $val = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($cell->getCalculatedValue());
                            } else {
                                $val = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($cell->getValue());
                            }
                            $sql1 .= "thedate = FROM_UNIXTIME($val),\n";
                            $sql2 .= "thedate = FROM_UNIXTIME($val),\n";
                        }
                        /*
                         *
                         Day
                         Date
                         Lisinopril
                         AM
                         PM

                         Metamorfin
                         AM
                         PM

                         Curalin
                         AM
                         Mid
                         PM
                         */
                        if (!$val = $cell->getValue()) {
                            $val = 0;
                        }

                        switch ($col) {
                            case 3:
                                $sql1 .= "medication = 'Lisinopril',\n";
                                break;
                            case 4:
                                $sql1 .= "AM = $val,\n";
                                break;
                            case 5:
                                $sql1 .= "PM = $val\n";
                                break;
                            case 7:
                                $sql2 .= "medication = 'Metamorfin',\n";
                                break;
                            case 8:
                                $sql2 .= "AM = $val,\n";
                                break;
                            case 9:
                                $sql2 .= "PM = $val\n";
                                break;
                        }
                    }

                    $db->mysqli->query($sql1);
                    if ($db->mysqli->error) {
                        echo "<pre>$sql1\n</pre>";
                        echo "(" . $db->mysqli->errno . ") " . $db->mysqli->error;
                    }
                    $db->mysqli->query($sql2);
                    if ($db->mysqli->error) {
                        echo "<pre>$sql2\n</pre>";
                        echo "(" . $db->mysqli->errno . ") " . $db->mysqli->error;
                    }
                }
            }
        }
    } else {
        die('Input file not found');
    }
    return $statistics;
}

function get_mean_arterial_pressure($bp) {
    $map = null;

    if ($bp && $bp != '0/0') {
        list($sp, $dp) = explode('/', $bp);
        //[systolic blood pressure + (2 X diastolic blood pressure)] / 3
        $map = round((($sp + (2 * $dp))/3), 1);
    }
    return $map;
}

// Clean up files older than 3 hours.
function clean_temp_files($folderName = 'graphs') {
    if (file_exists($folderName)) {
        foreach (new DirectoryIterator($folderName) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }
            if ($fileInfo->isFile() && time() - $fileInfo->getMTime() >= 3*60*60) {
                unlink($fileInfo->getRealPath());
            }
        }
    }
}


function get_graph_legends() {
    return array(
        'Lisinopril',
        'Metamorfin',
        'Alcohol (Units)',
        'BP 3 Avg',
        'BP 2 Avg'
    );
}

/*
* $primaryy = bsstats | bpstats | medication | alcohol
* $bsstats = true | false
* $bpstats = true | bp3 | bp2
* $medication = true | $medicine
* $alcohol = true | false
* $returnall = true | false
* $useeffectivedate = true | false
*
*/
function get_yaxis($statistics, $primaryy, $bsstats = false, $bpstats = null, $medication = null, $alcohol = null, $returnall = false, $useeffectivedate = true) {

    if (empty($statistics)) {
        die('No Stats have been provided.');
    }

    $primaryoptions = array('bsstats', 'bpstats', 'medication', 'alcohol');
    if (!in_array($primaryy, $primaryoptions)) {
        die('Must select a primary Y-axis.');
    }

    $ys = array();


    foreach ($statistics as $stats){

        if ($medication) {
            if (isset($stats['medication'])) {
                foreach ($stats['medication'] as $meds) {
                    if (($medication === true) ||  $meds['medication'] == $medication) {
                        if (empty($ys[$meds['medication']])) {
                            $ys[$meds['medication']] = array();
                        }
                        $ys[$meds['medication']][$meds['unixdate']] = $meds['AM'] + $meds['Midday'] + $meds['PM'];
                    }
                }
            }
        }

        if ($alcohol) {
            if (!(empty($stats['alcohol']) || ($stats['alcohol']['unknown']))) {
                if (empty($ys['Alcohol'])) {
                    $ys['Alcohol'] = array();
                }
                $ys['Alcohol'][$stats['alcohol']['unixdate']] = $stats['alcohol']['units'];
            }
        }

        if ($bsstats) {
            $earliest = 0;
            if (!empty($stats['bs'])) {

                foreach ($stats['bs'] as $bloodstats) {

                    if (empty($bloodstats['bstime'])) {
                        continue;
                    }

                    if (!isset($ys['BS'])) {
                        $ys['BS'] = array();
                    }

                    if ($returnall) {
                        $ys['BS'][$bloodstats['bptimestamp']] = $bloodstats['bsreading'];
                    } else {
                        if (!$earliest || $bloodstats['bstimestamp'] < $earliest) {
                            $earliest = $bloodstats['bstimestamp'];
                            if ($useeffectivedate) {
                                $ys['BS'][$bloodstats['unixeffdate']] = $bloodstats['bsreading'];
                            } else {
                                $ys['BS'][$bloodstats['unixdate']] = $bloodstats['bsreading'];
                            }
                        }
                    }
                }
            }

        }

        if ($bpstats) {
            $earliest = 0;
            if (!empty($stats['bp'])) {
                foreach ($stats['bp'] as $bloodstats) {

                    // Check for no stats at all
                    if (!$bloodstats['BP3avg'] && !$bloodstats['BP2avg']) {
                        continue;
                    }

                    if ($bpstats === true || $bpstats == 'bp3') {
                        if (!isset($ys['BP3Avg'])) {
                            $ys['BP3Avg'] = array();
                        }
                    }

                    if ($bpstats === true || $bpstats == 'bp2') {
                        if (!isset($ys['BP2Avg'])) {
                            $ys['BP2Avg'] = array();
                        }
                    }

                    if ($returnall) {
                        if ($bpstats === true || $bpstats == 'bp3') {
                            $ys['BP3Avg'][(int)$bloodstats['bptimestamp']] = get_mean_arterial_pressure($bloodstats['BP3avg']);
                        }
                        if ($bpstats === true || $bpstats == 'bp2') {
                            $ys['BP2Avg'][(int)$bloodstats['bptimestamp']] = get_mean_arterial_pressure($bloodstats['BP2avg']);
                        }
                    } else {
                        // Only really want the morning level.
                        if (!$earliest || $bloodstats['bptimestamp'] < $earliest) {
                            $earliest = $bloodstats['bptimestamp'];
                            if ($bpstats === true || $bpstats == 'bp3') {
                                $bp3map = get_mean_arterial_pressure($bloodstats['BP3avg']);
                                if ($useeffectivedate) {
                                    $ys['BP3Avg'][$bloodstats['unixeffdate']] = $bp3map;
                                } else {
                                    $ys['BP3Avg'][$bloodstats['unixdate']] = $bp3map;
                                }
                            }
                            if ($bpstats === true || $bpstats == 'bp2') {
                                $bp2map = get_mean_arterial_pressure($bloodstats['BP2avg']);
                                if ($useeffectivedate) {
                                    $ys['BP2Avg'][$bloodstats['unixeffdate']] = $bp2map;
                                } else {
                                    $ys['BP2Avg'][$bloodstats['unixdate']] = $bp2map;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $primy = get_graph_primarykey($primaryy, $bsstats, $bpstats, $medication, $alcohol);
    // echo "<p>Primary key is $primy</p>";

    // normalise all the yaxis data.
    $primarydates = array_keys($ys[$primy]);
    //die('<pre>' . print_r($primarydates, true) . '</pre>');

    foreach ($ys as $label => $data) {
        if ($label == $primy) {
            ksort($ys[$label]);
            //echo "<p>Primary $label has " . count($ys[$label]) . " items </p>";
            continue;
        }

        // Do we need to take away some points??
        if (count(array_keys($ys[$label])) >= count($primarydates)) {
            $removedates = array_diff(array_keys($data), $primarydates);
            foreach($removedates as $rdate) {
                unset($ys[$label][$rdate]);
            }
        }

        // Do we need to add in some points??
        if (count(array_keys($ys[$label])) <= count($primarydates)) {
            // echo "<p> Fixing missing items</p>";
            $adddates = array_diff($primarydates, array_keys($data));
            foreach($adddates as $adate) {
                $ys[$label][$adate] = '-';
            }
        }
        ksort($ys[$label]);

        //echo "<p>$label has " . count($ys[$label]) . " items </p>";
    }

    //die('<pre>' . print_r($ys, true) . '</pre>');
    // echo '<pre>' . print_r($ys, true) . '</pre>';

    return $ys;

}

function get_graph_primarykey($primaryy, $bsstats = false, $bpstats = null, $medication = null, $alcohol = null) {
    $primy = null;
    switch ($primaryy) {
        case 'bsstats' :
            $primy = 'BS';
            break;
        case 'bpstats' :
            if ($bpstats === 'bp2') {
                $primy = 'BP2Avg';
            } else {        // default
                $primy = 'BP3Avg';
            }
            break;
        case 'medication' :
            if ($medication === true) {
                $primy = 'Metamofin';
            } else {
                $primy = $medication;
            }
            break;
        case 'alcohol' :
            $primy = 'Alcohol';
    }
    return $primy;
}

function get_graph_title($primaryy, $bsstats = false, $bpstats = null, $medication = null, $alcohol = null) {

    $title = '';

    switch ($primaryy) {
        case 'bsstats' :
            $title = 'Blood Glucose';
            $bsstats = null;
            break;
        case 'bpstats' :
            $title = 'Blood Pressure';
            if ($bpstats === 'bp2') {
                $title .= ' (BP2Avg)';
            } else if ($bpstats === 'bp3'){
                $title .= ' (BP3Avg)';
            }
            $bpstats = null;
            break;
        case 'medication' :
            if ($medication === true) {
                $title = 'Medication';
            } else {
                $title = $medication;
            }
            $medication = null;
            break;
        case 'alcohol' :
            $title = 'Alcohol';
            $alcohol = null;
    }

    $versus = ' vs ';

    if ($bsstats) {
        $title .= $versus . 'Blood Glucose';
    }
    if ($bpstats) {
        $title .= $versus . 'Blood Pressure';
        if ($bpstats === 'bp2') {
            $title .= ' (BP2Avg)';
        } else if ($bpstats === 'bp3'){
            $title .= ' (BP3Avg)';
        }
    }
    if ($medication) {
        if ($medication === true) {
            $title .= $versus . 'Medication';
        } else {
            $title .= $versus . $medication;
        }
    }
    if ($alcohol) {
        $title .= $versus . 'Alcohol';
    }

    return $title;
}

function getYScaleValues($ydata) {

    $values = array_unique(array_values($ydata));
    sort($values, SORT_NUMERIC);
    // die('<pre>' . print_r($values, true) . '</pre>');

    if (count($values) > 1) {
        $min = floor(array_shift($values));
        if ($min > 0) {
            $min--;
        }
        $max = ceil(array_pop($values)) + 1;
        if ($max == 1) {
            $max++;
        }
    } else {
        if ($values[0] > 0) {
            $min = $values[0] - 1;
        } else {
            $min = 0;
        }
        $max = $values[0] + 1;
    }

    return array($min, $max);
}

/*
 * $primaryy = bsstats | bpstats | medication | alcohol
 * $bsstats = true | false
 * $bpstats = true | bp3 | bp2
 * $medication = true | $medicine
 * $alcohol = true | false
 *
 */
function get_stats_graph($statistics, $primaryy, $bsstats = false, $bpstats = null, $medication = null, $alcohol = null, $returnall = false, $useeffectivedate = true) {

    // Size of the overall graphforeach ($stats['bloods'] as $bloodstats)
    $width=1200;
    $height=600;

    $linecolours = array(
        'red',
        'blue',
        'yellow1',
        'yellow4'
    );

    $plotmaps = array(
        MARK_SQUARE,
        MARK_UTRIANGLE,
        MARK_DTRIANGLE,
        MARK_DIAMOND,
        MARK_CIRCLE,
        MARK_FILLEDCIRCLE,
        MARK_CROSS,
        MARK_STAR,
        MARK_X,
        MARK_LEFTTRIANGLE,
        MARK_RIGHTTRIANGLE,
        MARK_FLASH
    );

    $ys = get_yaxis($statistics, $primaryy, $bsstats, $bpstats, $medication, $alcohol, $returnall, $useeffectivedate);
    $primy = get_graph_primarykey($primaryy, $bsstats, $bpstats, $medication, $alcohol);

    // Create the graph and set a scale.
    // These two calls are always required
    $graph = new Graph($width, $height);
    if ($primaryy == 'medication') {
        list($aYMin, $aYMax) = getYScaleValues($ys[$primy]);
        $graph->SetScale('datlin', $aYMin, $aYMax);
        $graph->SetTickDensity(TICKD_VERYSPARSE);
        //$graph->yaxis->scale->ticks->Set(20,10);
        //$graph->yaxis->scale->ticks->setColor();

    } else {
        $graph->SetScale('datlin');
    }

    $graph->xaxis->scale->SetDateFormat('d-M-Y');

//     echo "<p>Min $aYMin Max $aYMax</p>";
//     die;

    $graph->legend->SetPos(0.5, 0.98, 'center', 'bottom');

    // Slightly larger than normal margins at the bottom to have room for
    // the x-axis date labels and the legend
    $graph->SetMargin(40, 150, 30, 200);

    $graph->xaxis->SetLabelAngle(30);
    $graph->xaxis->title->Set('Date');
    $graph->xaxis->title->SetFont(FF_DEFAULT, FS_BOLDITALIC, 10);
    $graph->title->Set(get_graph_title($primaryy, $bsstats, $bpstats, $medication, $alcohol));
    $graph->subtitle->Set('(For Benjamin Ellis)');
    $graph->title->SetFont(FF_DEFAULT, FS_BOLD, 14);
    $graph->subtitle->SetFont(FF_DEFAULT, FS_NORMAL, 9);

    $xdata = array_keys($ys[$primy]);
    if (count($xdata) > 60) {
        $graph->xaxis->SetLabelAngle(90);
    } else {
        $graph->xaxis->SetLabelAngle(30);
    }

    list($tickPositions, $minTickPositions) = DateScaleUtils::GetTicks($xdata, DSUTILS_DAY1);
    $graph->xaxis->SetTickPositions($tickPositions,$minTickPositions);

    // Must start with the Primary Y-Axis
    $line = new LinePlot(array_values($ys[$primy]), $xdata);
    $linecolour = array_shift($linecolours);
    // $line->color = $linecolour;
    $line->setColor($linecolour);
    // $line->SetLineWeight(5);
    $line->legend = $primy;
    $line->mark->SetType(array_shift($plotmaps));
    $line->mark->SetFillColor($linecolour);


    //$graph->yscale = new LinearScale((int) $aYMin, (int) $aYMax);
    $graph->Add($line);
    unset($ys[$primy]);

    // Create the linear plot.
    $lines = 0;
    foreach($ys as $legend => $arr) {
        $line = new LinePlot(array_values($arr), $xdata);

        $linecolour = array_shift($linecolours);
        // $line->color = $linecolour;
        $line->setColor($linecolour);
        // $line->SetLineWeight(5);
        $line->legend = $legend;
        $line->mark->SetType(array_shift($plotmaps));
        $line->mark->SetFillColor($linecolour);
        $graph->SetYScale($lines, 'lin');
        $graph->AddY($lines, $line);
        $graph->ynaxis[$lines]->SetColor($line->color);
        $lines++;
    }

    //die;
    // Display the graph
    $tempfilename = 'graphs/' . uniqid() . '.png';
    $graph->Stroke($tempfilename);

    return $tempfilename;

}

function getSimplePagingHTML($page, $pagedays, $recsinpage) {
    $scope = empty($_REQUEST['scope']) ? '' : $_REQUEST['scope'];

    $pagingbar = "Processed $recsinpage Days<br />";
    $paginglink = parse_url($_SERVER['REQUEST_URI'],  PHP_URL_PATH);

    //if ($recsinpage >= $pagedays) {
        if ($page > 1) {        // we need a previous link
            if ($page > 2) {    // We need a 1st link
                $link = $paginglink . '?' .  http_build_query(array('page' => 1, 'pagedays' => $pagedays, 'scope' => $scope));
                $pagingbar .= '<span>| <a href="' . $link . '">&laquo; First</a> |</span>';
            }
            $prvpage = $page - 1;
            $link = $paginglink . '?' .  http_build_query(array('page' => $prvpage, 'pagedays' => $pagedays, 'scope' => $scope));
            $pagingbar .= '<span>| <a href="'. $link . '">&lsaquo; Previous</a> |</span>';
        }

        //if ($recsinpage == $pagedays) { // we have a full page - so next is required
            $nxtpage = $page + 1;
            $link = $paginglink . '?' .  http_build_query(array('page' => $nxtpage, 'pagedays' => $pagedays, 'scope' => $scope));
            $pagingbar .= '<span>| <a href="'. $link . '">Next &rsaquo;</a> |</span>';
        //}
    //}

    return $pagingbar;
}

function fix_value($val) {
    // Fix potential problems.
    if (!$val) {
        $val = 0;
    } else if (substr($val, 0, 1) == '?') {
        $val = null;
    } else if (substr($val, 0, 1) == '~') {
        $val = substr($val, 1);
    }
    return $val;
}


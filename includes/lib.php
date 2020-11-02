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
require_once('includes/lib/jpgraph/src/jpgraph.php');
require_once('includes/lib/jpgraph/src/jpgraph_line.php');
require_once('includes/lib/jpgraph/src/jpgraph_date.php');
require_once('includes/lib/jpgraph/src/jpgraph_utils.inc.php');

$version = '20201029-01';
$db = new dbfunctions($dbconfig);

clean_temp_files();

function testdbconnection() {
    global $db;

    return $db->testconnection();
}

function processPageParams() {
    $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    $perpage= isset($_REQUEST['perpage']) ? $_REQUEST['perpage'] : 20;
    $filter = isset($_REQUEST['accountid']) ? $_REQUEST['accountid'] : null;
    return array($page, $perpage, $filter);
}

function get_blood_stats($interval = 30) {
    global $db;

    return $db->get_blood_stats($interval);
}

function reprocess_input() {
    global $db;

    $statistics = null;
    // Autoload the spreadsheetjpgraph/jpgraph_utils.inc.php
    require '../vendor/autoload.php';

    $reader = new PhpOffice\PhpSpreadsheet\Reader\Ods();
    $reader->setReadDataOnly(false);
    if (file_exists('../bloodstuff_monitor.ods')) {
        try {
            /** Load $inputFileName to a Spreadsheet Object  **/
            $spreadsheet = $reader->load('../bloodstuff_monitor.ods');
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
                $tablename = 'bloods';
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
                //continue;
            } else if ($sheetname == 'Alcohol') {
                $tablename = 'alcohol';
                $fields = array(
                    '2' => 'thedate',
                    '3' => 'units'
                );
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

                        if (!$val = $cell->getValue()) {
                            $val = 0;
                        }

                        if ($col == 3) {
                            $sql1 .= "medication = 'Lisinopril',\n";
                            $sql1 .= "AM = $val\n";
                        }

                        if ($col == 6) {
                            $sql2 .= "medication = 'Metamorfin',\n";
                            $sql2 .= "AM = $val,\n";
                        }

                        if ($col == 7) {
                            $sql2 .= "PM = $val\n";
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

            if ($sheetname != 'Tablets') {
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

function get_stats_graph($statistics, $medication = null, $alcohol = null, $bpstats = null, $bsstats = null ) {

    $medicationdata = array();
    $alcoholdata = array();
    $bp3data = array();
    $bp2data = array();

    $ys = array(
        'Lisinopril' => & $medicationdata,
        'Alcohol (Units)' => & $alcoholdata,
        'BP 3 Avg' => & $bp3data,
        'BP 2 Avg' => & $bp2data
    );

    $linecolours = array(
        'blue',
        'red',
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

    foreach ($statistics as $stats){
        if (isset($stats['medication'])) {
            foreach ($stats['medication'] as $medication) {
                if ($medication['medication'] == 'Lisinopril') {
                    $medicationdata[$medication['unixdate']] = $medication['AM'] + $medication['Midday'] + $medication['PM'];
                }
            }
        } else {
            continue;       // No medication stats
        }

        if (!(empty($stats['alcohol']) || ($stats['alcohol']['unknown']))) {
            $alcoholdata[$stats['alcohol']['unixdate']] = $stats['alcohol']['units'];
        }

        foreach ($stats['bloods'] as $bloodstats) {
            // Only really want the morning level.
            $earliest = 0;
            // Check for no stats at all
            if (!$bloodstats['BP3avg'] && !$bloodstats['BP2avg']) {
                continue;
            }

            if (!$earliest || $bloodstats['bptimestamp'] < $earliest) {
                $earliest = $bloodstats['bptimestamp'];
                if ($bp3map = get_mean_arterial_pressure($bloodstats['BP3avg'])) {
                    $bp3data[$bloodstats['unixeffdate']] = $bp3map;
                }
                if ($bp2map = get_mean_arterial_pressure($bloodstats['BP2avg'])) {
                    $bp2data[$bloodstats['unixeffdate']] = $bp2map;
                }
            }
        }

    }

    ksort($medicationdata);
    ksort($alcoholdata);
    ksort($bp3data);
    ksort($bp2data);

    // echo '<pre>' . print_r($medicationdata, true) . '</pre>';
    // echo '<pre>' . print_r($alcoholdata, true) . '</pre>';
    // echo '<pre>' . print_r($bp3data, true) . '</pre>';
    // echo '<pre>' . print_r($bp2data, true) . '</pre>';

    // die;

    $xdata = array_keys($medicationdata);

    // Size of the overall graphforeach ($stats['bloods'] as $bloodstats)
    $width=1200;
    $height=750;

    // Create the graph and set a scale.
    // These two calls are always required
    $graph = new Graph($width, $height);
    $graph->SetScale('datlin');
    $graph->title->Set('Lisinopril Vs Alcohol Vs BP');
    $graph->title->SetFont(FF_DEFAULT, FS_BOLD, 14); // TODO Check for other Fonts
    $graph->subtitle->Set('(For Benjamin Ellis)');
    $graph->subtitle->SetFont(FF_DEFAULT, FS_NORMAL, 9);
    $graph->legend->SetPos(0.5, 0.98, 'center', 'bottom');
    // Slightly larger than normal margins at the bottom to have room for
    // the x-axis labels
    $graph->SetMargin(40, 150, 30, 200);

    $graph->xaxis->SetLabelAngle(30);
    $graph->xaxis->title->Set('Date');
    $graph->xaxis->title->SetFont(FF_DEFAULT, FS_BOLDITALIC, 10);
    $graph->xaxis->scale->SetDateFormat('d-M-Y');
    list($tickPositions, $minTickPositions) = DateScaleUtils::GetTicks($xdata, DSUTILS_DAY1);
    $graph->xaxis->SetTickPositions($tickPositions,$minTickPositions);

    // Create the linear plot.
    $lines = 0;
    foreach($ys as $legend => $arr) {
        $linecolour = array_shift($linecolours);
        $line = new LinePlot(array_values($arr), $xdata);
        $line->setColor($linecolour);
        $line->legend = $legend;
        $line->mark->SetType(array_shift($plotmaps));
        $line->mark->SetFillColor($linecolour);
        if ($ndx = $lines) {
            $ndx--;
            $graph->SetYScale($ndx, 'lin');
            $graph->AddY($ndx, $line);
            $graph->ynaxis[$ndx]->SetColor($line->color);
        } else {
            $graph->Add($line);
        }
        $lines++;
    }

    // Display the graph
    $tempfilename = 'graphs/' . uniqid() . '.png';
    $graph->Stroke($tempfilename);

    return $tempfilename;

}
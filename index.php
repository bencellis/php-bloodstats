<?php include_once('includes/header.php'); ?>

<?php

$debugging = true;

if ($debugging) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

global $dbconfig;
$message = '';
$mysqli = new mysqli($dbconfig['dbserver'], $dbconfig['dbuser'], $dbconfig['dbpasswd'], $dbconfig['dbname'], $dbconfig['dbport']);
if ($mysqli->connect_errno) {
    $message =  "Failed to connect to MySQL: (" .  $mysqli->connect_errno . ") " . $mysqli->connect_error;
}elseif ($mysqli->error) {
    $message =  "Failed to connect to MySQL: (" . $mysqli->errno . ") " . $mysqli->error;
}


?>

<?php include_once('includes/navigation.php'); ?>

    <div class="container">
      <!-- Example row of columns -->
      <div class="row">
        <div class="col-md-12">
<?php

if ($message) {
    echo "<p>$message</p>";
} else if (!empty($_REQUEST['reprocess'])) {
    $noerror = true;
    // Autoload the spreadsheet


    require './vendor/autoload.php';

    $reader = new PhpOffice\PhpSpreadsheet\Reader\Ods();
    $reader->setReadDataOnly(false);
    if (file_exists('bloodstuff_monitor.ods')) {
        try {
            /** Load $inputFileName to a Spreadsheet Object  **/
            $spreadsheet = $reader->load('bloodstuff_monitor.ods');
            //$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('bloodstuff_monitor.ods');
        } catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            die($e->getMessage());
        }

        // Trucate all tables
        foreach (array('bloods', 'alcohol', 'medication') as $table) {
            $sql = "TRUNCATE $table";
            $mysqli->query($sql);
        }

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
                    $mysqli->query($sql1);
                    if ($mysqli->error) {
                      echo "<pre>$sql1\n</pre>";
                      echo "(" . $mysqli->errno . ") " . $mysqli->error;
                    }
                    $mysqli->query($sql2);
                    if ($mysqli->error) {
                        echo "<pre>$sql2\n</pre>";
                        echo "(" . $mysqli->errno . ") " . $mysqli->error;
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
                    $mysqli->query($sql);
                    if ($mysqli->error) {
                        echo "<pre>$sql</pre>\n";
                        echo( "<p>$col $row  (" . $mysqli->errno . ") " . $mysqli->error . "</p>\n" );
                    }
                }
            }
        }
    } else {
        echo 'Input file not found';
    }
}

if (!$message) {
    echo "<h1>Stats Here</h1>";

    $earliestdate = 0;
    $statistics = array();

    $sqlbloods = "SELECT recid, thedate, bstime, bsreading, bptime, BP3avg, BP2avg, date_sub(thedate, INTERVAL 1 DAY) as effectivedate
            FROM bloods WHERE thedate > (SELECT DATE_SUB(MAX(thedate), INTERVAL 30 DAY) FROM `bloods`)
            ORDER BY thedate DESC, bstime ASC";

    $bloodrecords = $mysqli->query($sqlbloods);
    while ($result = $bloodrecords->fetch_assoc()) {
        if (!$earliestdate) {
            $earliestdate = $result['effectivedate'];
        }
        $statistics[$result['effectivedate']]['bloods'][] = $result;
    }

    $sqlalcohol = "SELECT *
                    FROM alcohol
                    WHERE thedate > DATE_SUB(DATE('$earliestdate'), INTERVAL 30 DAY)
                    ORDER BY thedate DESC";

    //echo "<pre>$sqlalcohol</pre>";

    $alrecords = $mysqli->query($sqlalcohol);
    while ($result = $alrecords->fetch_assoc()) {
        if (isset($statistics[$result['thedate']])) {
            $statistics[$result['thedate']]['alcohol'] = $result;
        }
    }

    $sqlmedication = "SELECT *
                        FROM medication
                        WHERE thedate > DATE_SUB(DATE('$earliestdate'), INTERVAL 30 DAY)
                        ORDER BY thedate DESC, medication ASC";


    $medsrecords = $mysqli->query($sqlmedication);
    while ($result = $medsrecords->fetch_assoc()) {
        if (isset($statistics[$result['thedate']])) {
            if (!isset($statistics[$result['thedate']]['medication'])) {
                $statistics[$result['thedate']]['medication'] = array();
            }
            $statistics[$result['thedate']]['medication'][$result['medication']] = $result;
        }
    }

    //echo "<pre>" . print_r($statistics, true) . '</pre>';


}

?>
        </div>
      </div>
      <?php foreach ($statistics as $date => $stats): ?>
	<div class="row panel panel-success">
		<div class="col-md-12">
			<div class="row">
				<div class="col-md-12 panel-heading">
					<h4><?php echo $date?></h4>
				</div>
			</div>
			<div class="row">
				<div class="col-md-3">
					<div class="row">
						<div class="col-md-12">
							<b>Alcohol</b>
						</div>
					</div>
					<div class="row">
						<div class="col-md-7">
							<?php if ($stats['alcohol']['unknown']): ?>
								??u
							<?php else: ?>
								<?php echo $stats['alcohol']['units'] . 'u'; ?>
							<?php endif; ?>
						</div>
						<div class="col-md-5">
							<?php if ($stats['alcohol']['estimate']): ?>
								Estimated.
							<?php else:?>
								&nbsp;
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="row">
						<div class="col-md-12">
							<b>Medication</b>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="col-md-6">
									<b>Name</b>
								</div>
								<div class="col-md-2">
									<b>AM</b>
								</div>
								<div class="col-md-2">
									<b>Mid.</b>
								</div>
								<div class="col-md-2">
									<b>PM</b>
								</div>
							</div>
							<?php if (!empty($stats['medication'])): ?>
    							<?php foreach ($stats['medication'] as $medication) :?>
    								<div class="row">
    									<div class="col-md-6">
    										<?php echo $medication['medication']; ?>
    									</div>
    									<div class="col-md-2">
    										<?php if (!empty($medication['AM'])): ?>
    											<?php echo $medication['AM']; ?>
    										<?php else:?>
    											&nbsp;
    										<?php endif; ?>
    									</div>
    									<div class="col-md-2">
    										<?php if (!empty($medication['AM'])): ?>
    											<?php echo $medication['Midday']; ?>
    										<?php else:?>
    											&nbsp;
    										<?php endif; ?>
    									</div>
    									<div class="col-md-2">
    										<?php if (!empty($medication['PM'])): ?>
    											<?php echo $medication['PM']; ?>
    										<?php else:?>
    											&nbsp;
    										<?php endif; ?>
    									</div>
    								</div>
    							<?php endforeach;?>
    						<?php else: ?>
								<div class="row">
									<div class="col-md-12">
										&nbsp;
									</div>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="col-md-6">
					<div class="row">
						<div class="col-md-12">
							<b>Bloods Stats (NOTE: Day After)</b>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<!--  thedate bstime bs bptime BP3avg BP2avg -->
								<div class="col-md-2">
									<b>BS Time</b>
								</div>
								<div class="col-md-2">
									<b>BS mmo/L</b>
								</div>
								<div class="col-md-2">
									<b>BP Time</b>
								</div>
								<div class="col-md-3">
									<b>BP 3 Avg</b>
								</div>
								<div class="col-md-3">
									<b>BP 2 Avg</b>
								</div>
							</div>
							<?php foreach ($stats['bloods'] as $bloodstats): ?>
								<div class="row">
									<!--  thedate bstime bs bptime BP3avg BP2avg -->
									<div class="col-md-2">
										<?php echo $bloodstats['bstime']; ?>
									</div>
									<div class="col-md-2">
										<?php echo $bloodstats['bsreading']; ?>
									</div>
									<div class="col-md-2">
										<?php echo $bloodstats['bptime']; ?>
									</div>
									<div class="col-md-3">
										<?php echo $bloodstats['BP3avg']; ?>
									</div>
									<div class="col-md-3">
										<?php echo $bloodstats['BP2avg']; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php endforeach; ?>
    </div> <!-- /container -->

    <hr>

<?php include_once('includes/footer.php'); ?>

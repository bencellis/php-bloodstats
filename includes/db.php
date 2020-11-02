<?php

class dbfunctions {

    public $mysqli = null;

    public function __construct(array $settings, $forcenew = false) {
        // TODO if we already exist return existing connection
        $message = '';
        $this->mysqli = new mysqli($settings['dbserver'], $settings['dbuser'], $settings['dbpasswd'], $settings['dbname'], $settings['dbport']);
        if ($this->mysqli->connect_errno) {
            $message =  "Failed to connect to MySQL: (" .  $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error;
        }elseif ($this->mysqli->error) {
            $message =  "Failed to connect to MySQL: (" . $this->mysqli->errno . ") " . $this->mysqli->error;
        }

        if ($message) {
            die($message);
        }
    }

    public function getLastError() {
        die("(" . $this->mysqli->errno . ") " . $this->mysqli->error);
    }

    public function closedb() {
        $this->mysqli->disconnect();
    }

    public function testconnection() {
        return $this->mysqli->get_server_info();
    }

    private function _resultToArray($dbresult) {
        // check for error 1st
        $results = array();
        if ($this->mysqli->errno) {
            $this->getLastError();
        } else {
            while ($record = $dbresult->fetch_array(MYSQLI_ASSOC)) {
                if (isset($record['recid']) || isset($record['id'])) {
                    $id = isset($record['recid']) ? $record['recid'] : $record['id'];
                    $results[$id] = $record;
                } else {
                    $results[] = $record;
                }
            }
        }
        return $results;
    }

    public function clear_statistics() {
        foreach (array('bloods', 'alcohol', 'medication') as $table) {
            $sql = "TRUNCATE $table";
            $this->mysqli->query($sql);
        }

    }

    public function get_blood_stats($interval = 30) {
        $earliestdate = 0;
        $statistics = array();

        $sqlbloods = "SELECT recid, thedate, bstime, bsreading, bptime, BP3avg, BP2avg,
                DATE_SUB(thedate, INTERVAL 1 DAY) as effectivedate,
                UNIX_TIMESTAMP(DATE_SUB(thedate, INTERVAL 1 DAY)) AS unixeffdate,
                UNIX_TIMESTAMP(DATE_SUB(thedate, INTERVAL 1 DAY)) AS unixdate,
                UNIX_TIMESTAMP(CONCAT(thedate, ' ', bptime)) AS bptimestamp,
                UNIX_TIMESTAMP(CONCAT(thedate, ' ', bstime)) AS bstimestamp
            FROM bloods WHERE thedate > (SELECT DATE_SUB(MAX(thedate), INTERVAL $interval DAY) FROM `bloods`)
            ORDER BY thedate DESC, bstime ASC";

        $bloodrecords = $this->mysqli->query($sqlbloods);
        while ($result = $bloodrecords->fetch_assoc()) {
            if (!$earliestdate) {
                $earliestdate = $result['effectivedate'];
            }
            $statistics[$result['effectivedate']]['bloods'][] = $result;
        }

        $sqlalcohol = "SELECT *, UNIX_TIMESTAMP(thedate) AS unixdate
                    FROM alcohol
                    WHERE thedate > DATE_SUB(DATE('$earliestdate'), INTERVAL 30 DAY)
                    ORDER BY thedate DESC";

        //echo "<pre>$sqlalcohol</pre>";

        $alrecords = $this->mysqli->query($sqlalcohol);
        while ($result = $alrecords->fetch_assoc()) {
            if (isset($statistics[$result['thedate']])) {
                $statistics[$result['thedate']]['alcohol'] = $result;
            }
        }

        $sqlmedication = "SELECT *, UNIX_TIMESTAMP(thedate) AS unixdate
                        FROM medication
                        WHERE thedate > DATE_SUB(DATE('$earliestdate'), INTERVAL 30 DAY)
                        ORDER BY thedate DESC, medication ASC";


        $medsrecords = $this->mysqli->query($sqlmedication);
        while ($result = $medsrecords->fetch_assoc()) {
            if (isset($statistics[$result['thedate']])) {
                if (!isset($statistics[$result['thedate']]['medication'])) {
                    $statistics[$result['thedate']]['medication'] = array();
                }
                $statistics[$result['thedate']]['medication'][$result['medication']] = $result;
            }
        }

        return $statistics;
    }

}

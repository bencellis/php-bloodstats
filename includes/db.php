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
        foreach (array('bloodbs', 'bloodbp', 'alcohol', 'medication') as $table) {
            $sql = "TRUNCATE $table";
            $this->mysqli->query($sql);
        }

    }

    public function get_blood_stats($page = 1, $perpage = 42, $filter = null) {

        $earliestdate = 0;

        $statistics = array();
        $lowerlimit = $page - 1;
        $higherlimit = $page * $perpage;

        $sqlbs = "SELECT recid, thedate, bstime, bsreading,
                DATE_SUB(thedate, INTERVAL 1 DAY) as effectivedate,
                UNIX_TIMESTAMP(DATE_SUB(thedate, INTERVAL 1 DAY)) AS unixeffdate,
                UNIX_TIMESTAMP(thedate) AS unixdate,
                UNIX_TIMESTAMP(CONCAT(thedate, ' ', bstime)) AS bstimestamp
                FROM bloodbs
                WHERE bsreading > 0
                ORDER BY thedate DESC, bstime ASC
                LIMIT $lowerlimit, $higherlimit";

        $bloodrecords = $this->mysqli->query($sqlbs);
        while ($result = $bloodrecords->fetch_assoc()) {
            if (!$earliestdate) {
                $earliestdate = $result['effectivedate'];
            }
            $statistics[$result['effectivedate']]['bs'][] = $result;
        }

        $sqlbp = "SELECT recid, thedate, bptime, BP3avg, BP2avg,
                DATE_SUB(thedate, INTERVAL 1 DAY) as effectivedate,
                UNIX_TIMESTAMP(DATE_SUB(thedate, INTERVAL 1 DAY)) AS unixeffdate,
                UNIX_TIMESTAMP(thedate) AS unixdate,
                UNIX_TIMESTAMP(CONCAT(thedate, ' ', bptime)) AS bptimestamp
                FROM bloodbp
                WHERE (BP3avg > 0 AND BP2avg > 0)
                ORDER BY thedate DESC, bptime ASC
                LIMIT $lowerlimit, $higherlimit";

        $bloodrecords = $this->mysqli->query($sqlbp);
        while ($result = $bloodrecords->fetch_assoc()) {
            $statistics[$result['effectivedate']]['bp'][] = $result;
        }

        $sqlalcohol = "SELECT *, UNIX_TIMESTAMP(thedate) AS unixdate
                    FROM alcohol
                    ORDER BY thedate DESC
                    LIMIT $lowerlimit, $higherlimit";

        //echo "<pre>$sqlalcohol</pre>";

        $alrecords = $this->mysqli->query($sqlalcohol);
        while ($result = $alrecords->fetch_assoc()) {
            if (isset($statistics[$result['thedate']])) {
                $statistics[$result['thedate']]['alcohol'] = $result;
            }
        }

        $sqlmedication = "SELECT *, UNIX_TIMESTAMP(thedate) AS unixdate
                        FROM medication
                        ORDER BY thedate DESC, medication ASC
                        LIMIT $lowerlimit, $higherlimit";

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

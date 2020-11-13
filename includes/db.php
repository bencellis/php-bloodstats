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

    public function get_blood_stats($page = 1, $pagedays = 90, $order = 'ASC') {

        $statistics = array();
        $lowerlimit = ($page - 1) * $pagedays;
        $higherlimit = $pagedays;

        $datelimitsql = "JOIN (SELECT DISTINCT thedate FROM bloodbs ORDER BY thedate DESC LIMIT $lowerlimit, $higherlimit) B On B.thedate = A.thedate";

        $sqlbs = "SELECT recid, A.thedate, bstime, bsreading,
                    DATE_SUB(A.thedate, INTERVAL 1 DAY) as effectivedate,
                    UNIX_TIMESTAMP(DATE_SUB(A.thedate, INTERVAL 1 DAY)) AS unixeffdate,
                    UNIX_TIMESTAMP(A.thedate) AS unixdate,
                    UNIX_TIMESTAMP(CONCAT(A.thedate, ' ', bstime)) AS bstimestamp
                    FROM bloodbs A
                    $datelimitsql
                    WHERE bsreading > 0
                    ORDER BY thedate $order, bstime ASC";

//         die("<pre>$sqlbs</pre>");

        $bloodrecords = $this->mysqli->query($sqlbs);
        while ($result = $bloodrecords->fetch_assoc()) {
            $statistics[$result['effectivedate']]['bs'][] = $result;
        }

        $sqlbp = "SELECT recid, A.thedate, bptime, BP3avg, BP2avg,
                    DATE_SUB(A.thedate, INTERVAL 1 DAY) as effectivedate,
                    UNIX_TIMESTAMP(DATE_SUB(A.thedate, INTERVAL 1 DAY)) AS unixeffdate,
                    UNIX_TIMESTAMP(A.thedate) AS unixdate,
                    UNIX_TIMESTAMP(CONCAT(A.thedate, ' ', bptime)) AS bptimestamp
                    FROM bloodbp A
                    $datelimitsql
                    WHERE (A.BP3avg > 0 AND A.BP2avg > 0)
                    ORDER BY A.thedate $order, A.bptime ASC";

        $bloodrecords = $this->mysqli->query($sqlbp);
        while ($result = $bloodrecords->fetch_assoc()) {
            $statistics[$result['effectivedate']]['bp'][] = $result;
        }

        $sqlalcohol = "SELECT A.*, UNIX_TIMESTAMP(A.thedate) AS unixdate
                        FROM alcohol A
                        $datelimitsql
                        ORDER BY A.thedate $order";

        //echo "<pre>$sqlalcohol</pre>";

        $alrecords = $this->mysqli->query($sqlalcohol);
        while ($result = $alrecords->fetch_assoc()) {
            if (isset($statistics[$result['thedate']])) {
                $statistics[$result['thedate']]['alcohol'] = $result;
            }
        }

        $sqlmedication = "SELECT A.*, UNIX_TIMESTAMP(A.thedate) AS unixdate
                            FROM medication A
                            $datelimitsql
                            ORDER BY A.thedate $order, A.medication ASC";

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

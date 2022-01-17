<?php
class Calendar {
  // (A) CONSTRUCTOR - CONNECT TO DATABASE
  private $pdo = null;
  private $stmt = null;
  public $error = "";
  function __construct () {
    try {
      $this->pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,
        DB_USER, DB_PASSWORD, [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
      );
    } catch (Exception $ex) { exit($ex->getMessage()); }
  }

  // (B) DESTRUCTOR - CLOSE DATABASE CONNECTION
  function __destruct () {
    if ($this->stmt!==null) { $this->stmt = null; }
    if ($this->pdo!==null) { $this->pdo = null; }
  }

  // (C) HELPER - EXECUTE SQL QUERY
  function exec ($sql, $data=null) {
    try {
      $this->stmt = $this->pdo->prepare($sql);
      $this->stmt->execute($data);
      return true;
    } catch (Exception $ex) {
      $this->error = $ex->getMessage();
      return false;
    }
  }

  // (D) SAVE EVENT
  function save ($start, $end, $txt, $color, $id=null) {
    // (D1) START & END DATE QUICK CHECK
    $uStart = strtotime($start);
    $uEnd = strtotime($end);
    if ($uEnd < $uStart) {
      $this->error = "End date cannot be earlier than start date";
      return false;
    }

    // (D2) SQL - INSERT OR UPDATE
    if ($id==null) {
      $sql = "INSERT INTO `events` (`evt_start`, `evt_end`, `evt_text`, `evt_color`) VALUES (?,?,?,?)";
      $data = [$start, $end, $txt, $color];
    } else {
      $sql = "UPDATE `events` SET `evt_start`=?, `evt_end`=?, `evt_text`=?, `evt_color`=? WHERE `evt_id`=?";
      $data = [$start, $end, $txt, $color, $id];
    }

    // (D3) EXECUTE
    return $this->exec($sql, $data);
  }

  // (E) DELETE EVENT
  function del ($id) {
    return $this->exec("DELETE FROM `events` WHERE `evt_id`=?", [$id]);
  }

  // (F) GET EVENTS FOR SELECTED MONTH
  function get ($month, $year) {
    // (F1) FIST & LAST DAY OF MONTH
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $dayFirst = "{$year}-{$month}-01 00:00:00";
    $dayLast = "{$year}-{$month}-{$daysInMonth} 23:59:59";

    // (F2) GET EVENTS
    if (!$this->exec(
      "SELECT * FROM `events` WHERE (
        (`evt_start` BETWEEN ? AND ?)
        OR (`evt_end` BETWEEN ? AND ?)
        OR (`evt_start` <= ? AND `evt_end` >= ?)
      )", [$dayFirst, $dayLast, $dayFirst, $dayLast, $dayFirst, $dayLast]
    )) { return false; }

    // $events = [
    //  "e" => [ EVENT ID => [DATA], EVENT ID => [DATA], ... ],
    //  "d" => [ DAY => [EVENT IDS], DAY => [EVENT IDS], ... ]
    // ]
    $events = ["e" => [], "d" => []];
    while ($row = $this->stmt->fetch()) {
      $eStartMonth = substr($row["evt_start"], 5, 2);
      $eEndMonth = substr($row["evt_end"], 5, 2);
      $eStartDay = $eStartMonth==$month
                 ? (int)substr($row["evt_start"], 8, 2) : 1 ;
      $eEndDay = $eEndMonth==$month
               ? (int)substr($row["evt_end"], 8, 2) : $daysInMonth ;
      for ($d=$eStartDay; $d<=$eEndDay; $d++) {
        if (!isset($events["d"][$d])) { $events["d"][$d] = []; }
        $events["d"][$d][] = $row["evt_id"];
      }
      $events["e"][$row["evt_id"]] = $row;
      $events["e"][$row["evt_id"]]["first"] = $eStartDay;
    }
    return $events;
  }
}

// (G) DATABASE SETTINGS - CHANGE TO YOUR OWN!
define("DB_HOST", "localhost");
define("DB_NAME", ""); // Database Name Here
define("DB_CHARSET", "utf8");
define("DB_USER", "root");
define("DB_PASSWORD", ""); // Database Password Here

// (H) NEW CALENDAR OBJECT
$_CAL = new Calendar();

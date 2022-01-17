<?php
// (A) INVALID AJAX REQUEST
if (!isset($_POST["req"])) { exit("INVALID REQUEST"); }
require "2-cal-core.php";
switch ($_POST["req"]) {
  // (B) DRAW CALENDAR FOR MONTH
  case "draw":
    // (B1) DATE RANGE CALCULATIONS
    // NUMBER OF DAYS IN MONTH
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $_POST["month"], $_POST["year"]);
    // FIRST & LAST DAY OF MONTH
    $dateFirst = "{$_POST["year"]}-{$_POST["month"]}-01";
    $dateLast = "{$_POST["year"]}-{$_POST["month"]}-{$daysInMonth}";
    // DAY OF WEEK - NOTE 0 IS SUNDAY
    $dayFirst = (new DateTime($dateFirst))->format("w");
    $dayLast = (new DateTime($dateLast))->format("w");

    // (B2) DAY NAMES
    $sunFirst = true; // CHANGE THIS IF YOU WANT MON FIRST
    $days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    if ($sunFirst) { array_unshift($days, "Sun"); }
    else { $days[] = "Sun"; }
    foreach ($days as $d) { echo "<div class='calsq head'>$d</div>"; }
    unset($days);

    // (B3) PAD EMPTY SQUARES BEFORE FIRST DAY OF MONTH
    if ($sunFirst) { $pad = $dayFirst; }
    else { $pad = $dayFirst==0 ? 6 : $dayFirst-1 ; }
    for ($i=0; $i<$pad; $i++) { echo "<div class='calsq blank'></div>"; }

    // (B4) DRAW DAYS IN MONTH
    $events = $_CAL->get($_POST["month"], $_POST["year"]);
    $nowMonth = date("n");
    $nowYear = date("Y");
    $nowDay = ($nowMonth==$_POST["month"] && $nowYear==$_POST["year"]) ? date("j") : 0 ;
    for ($day=1; $day<=$daysInMonth; $day++) { ?>
    <div class="calsq day<?=$day==$nowDay?" today":""?>" data-day="<?=$day?>">
      <div class="calnum"><?=$day?></div>
        <?php if (isset($events["d"][$day])) { foreach ($events["d"][$day] as $eid) { ?>
        <div class="calevt" data-eid="<?=$eid?>"
             style="background:<?=$events["e"][$eid]["evt_color"]?>">
          <?=$events["e"][$eid]["evt_text"]?>
        </div>
        <?php if ($day == $events["e"][$eid]["first"]) {
          echo "<div id='evt$eid' class='calninja'>".json_encode($events["e"][$eid])."</div>";
        }}} ?>
    </div>
    <?php }

    // (B5) PAD EMPTY SQUARES AFTER LAST DAY OF MONTH
    if ($sunFirst) { $pad = $dayLast==0 ? 6 : 6-$dayLast ; }
    else { $pad = $dayLast==0 ? 0 : 7-$dayLast ; }
    for ($i=0; $i<$pad; $i++) { echo "<div class='calsq blank'></div>"; }
    break;

  // (C) SAVE EVENT
  case "save":
    if (!is_numeric($_POST["eid"])) { $_POST["eid"] = null; }
    echo $_CAL->save(
      $_POST["start"], $_POST["end"], $_POST["txt"], $_POST["color"],
      isset($_POST["eid"]) ? $_POST["eid"] : null
    ) ? "OK" : $_CAL->error ;
    break;

  // (D) DELETE EVENT
  case "del":
    echo $_CAL->del($_POST["eid"])  ? "OK" : $_CAL->error ;
    break;
}

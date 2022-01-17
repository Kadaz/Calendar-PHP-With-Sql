var cal = {
  // (A) INIT CALENDAR
  hMth:null, hYr:null, // MONTH & YEAR
  hWrap:null, // DAYS WRAPPER
  // EVENT FORM
  hBlock:null, hForm:null, hFormDel:null, hFormCX:null,
  hID:null, hStart:null, hEnd:null, hTxt:null, hColor:null,
  init : () => {
    // (A1) GET HTML ELEMENTS
    cal.hMth = document.getElementById("calmonth");
    cal.hYr = document.getElementById("calyear");
    cal.hWrap = document.getElementById("calwrap");
    cal.hBlock = document.getElementById("calblock");
    cal.hForm = document.getElementById("calform");
    cal.hFormDel = document.getElementById("calformdel");
    cal.hFormCX = document.getElementById("calformcx");
    cal.hID = document.getElementById("evtid");
    cal.hStart = document.getElementById("evtstart");
    cal.hEnd = document.getElementById("evtend");
    cal.hTxt = document.getElementById("evttxt");
    cal.hColor = document.getElementById("evtcolor");

    // (A2) ATTACH CONTROLS
    cal.hMth.onchange = cal.draw;
    cal.hYr.onchange = cal.draw;
    cal.hForm.onsubmit = cal.save;
    cal.hFormDel.onclick = cal.del;
    cal.hFormCX.onclick = cal.hide;

    // (A3) DRAW CURRENT MONTH/YEAR
    cal.draw();
  },

  // (B) SUPPORT FUNCTION - AJAX FETCH
  ajax : (data, load) => {
    fetch("4-ajax.php", { method:"POST", body:data })
    .then(res=>res.text()).then(load);
  },

  // (C) DRAW CALENDAR
  draw : () => {
    // (C1) FORM DATA
    let data = new FormData();
    data.append("req", "draw");
    data.append("month", cal.hMth.value);
    data.append("year", cal.hYr.value);
    // (C2) AJAX LOAD SELECTED MONTH
    cal.ajax(data, (res) => {
      // (C2-1) ATTACH CALENDAR TO WRAPPER
      cal.hWrap.innerHTML = res;

      // (C2-2) CLICK DAY CELLS TO ADD EVENT
      for (let day of cal.hWrap.getElementsByClassName("day")) {
        day.onclick = () => { cal.show(day); };
      }

      // (C2-3) CLICK EVENT TO EDIT
      for (let evt of cal.hWrap.getElementsByClassName("calevt")) {
        evt.onclick = (e) => { cal.show(evt); e.stopPropagation(); };
      }
    });
  },

  // (D) SHOW EVENT FORM
  show : (cell) => {
    let eid = cell.getAttribute("data-eid");

    // (D1) ADD NEW EVENT
    if (eid === null) {
      let y = cal.hYr.value, m = cal.hMth.value, d = cell.dataset.day;
      if (m.length==1) { m = "0" + m; }
      if (d.length==1) { d = "0" + d; }
      let ymd = `${y}-${m}-${d}T00:00:00`; // RFC 3339
      cal.hForm.reset();
      cal.hID.value = "";
      cal.hStart.value = ymd;
      cal.hEnd.value = ymd;
      cal.hFormDel.style.display = "none";
    }

    // (D2) EDIT EVENT
    else {
      let edata = JSON.parse(document.getElementById("evt"+eid).innerHTML);
      cal.hID.value = eid;
      cal.hStart.value = edata["evt_start"].replaceAll(" ", "T");
      cal.hEnd.value = edata["evt_end"].replaceAll(" ", "T");
      cal.hTxt.value = edata["evt_text"];
      cal.hColor.value = edata["evt_color"];
      cal.hFormDel.style.display = "block";
    }

    // (D3) SHOW EVENT FORM
    cal.hBlock.classList.add("show");
  },

  // (E) HIDE EVENT FORM
  hide : () => { cal.hBlock.classList.remove("show"); },

  // (F) SAVE EVENT
  save : () => {
    cal.ajax(new FormData(cal.hForm), (res) => {
      if (res=="OK") { cal.hide(); cal.draw(); }
      else { alert(res); }
    });
    return false;
  },

  // (G) DELETE EVENT
  del : () => { if (confirm("Delete Event?")) {
    // (G1) FORM DATA
    let data = new FormData();
    data.append("req", "del");
    data.append("eid", cal.hID.value);

    // (G2) AJAX DELETE
    cal.ajax(data, (res) => {
      if (res=="OK") { cal.hide(); cal.draw(); }
      else { alert(res); }
    });
  }}
};
window.addEventListener("DOMContentLoaded", cal.init);

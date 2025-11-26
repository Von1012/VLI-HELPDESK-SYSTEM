$(document).ready(function(){

    loadStats();
    loadCharts();
    loadTable();

    $("#applyFilters").click(function(){
        loadStats();
        loadCharts();
        loadTable();
    });

    $("#resetFilters").click(function(){
        $("#filterForm")[0].reset();
        loadStats();
        loadCharts();
        loadTable();
    });
});

/* --------------------------
   LOAD STATS
--------------------------- */
function loadStats(){
    $.post("report_action.php", $("#filterForm").serialize() + "&action=loadStats", function(res){
        $("#stat_total").text(res.total);
        $("#stat_open").text(res.open);
        $("#stat_closed").text(res.closed);
        $("#stat_avg_replies").text(res.avgReplies);
    }, "json");
}

/* --------------------------
   LOAD CHARTS
--------------------------- */
function loadCharts(){

    // Status Pie
    $.post("report_action.php", $("#filterForm").serialize() + "&action=chartStatus", function(res){
        new Chart(document.getElementById("chartStatus"), {
            type: "pie",
            data: {
                labels: ["Open", "Closed"],
                datasets: [{
                    data: [res.open, res.closed]
                }]
            }
        });
    }, "json");

    // Department Bar
    $.post("report_action.php", $("#filterForm").serialize() + "&action=chartDepartments", function(res){
        new Chart(document.getElementById("chartDept"), {
            type: "bar",
            data: {
                labels: res.labels,
                datasets: [{
                    data: res.data
                }]
            }
        });
    }, "json");

    // Timeline
    $.post("report_action.php", $("#filterForm").serialize() + "&action=chartTimeline", function(res){
        new Chart(document.getElementById("chartTimeline"), {
            type: "line",
            data: {
                labels: res.labels,
                datasets: [{
                    data: res.data
                }]
            }
        });
    }, "json");
}

/* --------------------------
   DATATABLE
--------------------------- */
function loadTable(){
    if($.fn.DataTable.isDataTable("#reportTickets")){
        $("#reportTickets").DataTable().destroy();
    }

    $("#reportTickets").DataTable({
        ajax: {
            url: "report_action.php",
            type: "POST",
            data: function(d){
                return $("#filterForm").serialize() + "&action=loadTable";
            },
            dataSrc: "data"
        }
    });
}

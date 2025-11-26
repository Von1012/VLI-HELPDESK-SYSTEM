<?php 
include 'init.php'; 
if(!$users->isLoggedIn()) {
    header("Location: login.php");	
}
$currentPage = 'report';
include('inc/header.php');
$user = $users->getUserInfo();
?>
<title>VLI HELPDESK SYSTEM - Reports</title>
<link rel="stylesheet" href="css/style.css" />
<?php include('inc/container.php'); ?>

<div class="container">
    <div class="row home-sections">
        <div class="col-12 d-flex align-items-center justify-content-between">
            <h2>Helpdesk System - Reports</h2>
            <?php include('menus.php'); ?>
        </div>
    </div>

<?php
// SAFE DB FETCH - assumes hd_tickets.date is UNIX timestamp
$conn = new mysqli("127.0.0.1", "root", "", "helpdesk_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql = "SELECT t.id, t.title, t.init_msg, t.resolved, t.date AS unix_date, d.name AS department
        FROM hd_tickets t
        LEFT JOIN hd_departments d ON t.department = d.id
        ORDER BY t.date ASC";
$res = $conn->query($sql);
if (!$res) {
    die("Query failed: " . $conn->error);
}

$data = [];
$departmentsSet = [];
$statusSet = ['Resolved'=>0,'Unresolved'=>0];
while ($r = $res->fetch_assoc()) {
    $unix = isset($r['unix_date']) ? (int)$r['unix_date'] : 0;
    $status = (isset($r['resolved']) && ($r['resolved']==1 || strtolower($r['resolved'])==='resolved' || strtolower($r['resolved'])==='closed' || strtolower($r['resolved'])==='1')) ? 'Resolved' : 'Unresolved';
    $dept = $r['department'] ?? 'No Department';
    $title = $r['title'] ?? '';
    $init = $r['init_msg'] ?? '';
    $data[] = [
        'id' => $r['id'] ?? '',
        'title' => $title,
        'init_msg' => $init,
        'status' => $status,
        'department' => $dept,
        'unix_date' => $unix
    ];
    $departmentsSet[$dept] = true;
    if ($status === 'Resolved') $statusSet['Resolved']++; else $statusSet['Unresolved']++;
}
$conn->close();
$departments = array_values(array_keys($departmentsSet));
?>

<style>
/* polished styles */
body{background:#f4f6f9;font-family:Segoe UI,Arial,sans-serif}
.card{border-radius:0.5rem;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
.kpi{padding:18px;color:#fff}
.kpi h5{margin:0;font-size:0.9rem;opacity:0.9}
.kpi h2{margin:6px 0 0;font-size:1.8rem}
.filters .form-control{border-radius:0.35rem}
.canvas-wrap{position:relative;width:100%;height:320px}
@media(max-width:768px){ .canvas-wrap{height:240px} }
.table-fixed thead th{position:sticky;top:0;z-index:3;background:#343a40;color:#fff}
.active-menu{background:#000;color:#fff!important;padding:6px 10px;border-radius:5px}
.small-note{font-size:0.85rem;color:#666}
</style>

<!-- Filters -->
<div class="row mb-3 filters">
    <div class="col-md-2 mb-2">
        <label>Start Date</label>
        <input type="date" id="startDate" class="form-control">
    </div>
    <div class="col-md-2 mb-2">
        <label>End Date</label>
        <input type="date" id="endDate" class="form-control">
    </div>
    <div class="col-md-3 mb-2">
        <label>Department</label>
        <select id="filterDept" class="form-control">
            <option value="">All Departments</option>
            <?php foreach($departments as $d): ?>
                <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2 mb-2">
        <label>Status</label>
        <select id="filterStatus" class="form-control">
            <option value="">All</option>
            <option value="Resolved">Resolved</option>
            <option value="Unresolved">Unresolved</option>
        </select>
    </div>

    <div class="col-md-3 mb-2">
        <label>Pie grouping (issue concern)</label>
        <select id="pieMode" class="form-control">
            <option value="title">Title (exact)</option>
            <option value="init_msg">Initial Message (exact)</option>
            <option value="department">Department</option>
            <option value="keyword">Keyword categories (auto)</option>
            <option value="combined">Combined (title + init_msg grouped)</option>
        </select>
        <div class="small-note">Keyword categories uses built-in keyword mapping (editable in JS).</div>
    </div>
</div>

<!-- KPIs -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card kpi bg-primary">
            <h5>Total Tickets</h5>
            <h2 id="kpiTotal"><?= count($data) ?></h2>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card kpi bg-success">
            <h5>Resolved</h5>
            <h2 id="kpiResolved"><?= $statusSet['Resolved'] ?></h2>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card kpi bg-danger">
            <h5>Unresolved</h5>
            <h2 id="kpiUnresolved"><?= $statusSet['Unresolved'] ?></h2>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card kpi bg-info">
            <h5>Departments</h5>
            <h2 id="kpiDept"><?= count($departments) ?></h2>
        </div>
    </div>
</div>

<!-- Charts: monthly status, monthly dept stacked, pie (issue concerns) -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card p-3">
            <h5>Monthly Resolved vs Unresolved</h5>
            <div class="canvas-wrap"><canvas id="monthlyStatus"></canvas></div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card p-3">
            <h5>Issue Concern (Pie)</h5>
            <div class="canvas-wrap"><canvas id="issuePie"></canvas></div>
        </div>
    </div>

    <div class="col-12 mb-4">
        <div class="card p-3">
            <h5>Monthly by Department (Stacked)</h5>
            <div class="canvas-wrap"><canvas id="monthlyDept"></canvas></div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="row">
    <div class="col-12">
        <div class="card p-3">
            <h5>Ticket Details</h5>
            <div class="table-responsive">
                <table id="reportTable" class="table table-striped table-bordered table-fixed">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Department</th>
                            <th>Title</th>
                            <th>Init Message</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="mt-3 d-flex">
                <button class="btn btn-success mr-2" id="btnExportExcel">Export Excel (with charts)</button>
                <button class="btn btn-primary" id="btnExportPDF">Export PDF (full dashboard)</button>
            </div>
        </div>
    </div>
</div>

</div><!-- container end -->

<!-- libs -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
// Server-side data -> client
const rawData = <?= json_encode($data) ?>;

// convert to JS objects with Date
const allData = rawData.map(r=>{
    const unix = parseInt(r.unix_date || 0);
    return {
        id: r.id,
        title: r.title || '',
        init_msg: r.init_msg || '',
        status: r.status,
        department: r.department || 'No Department',
        date: unix>0 ? new Date(unix*1000) : null
    };
});

// Keyword mapping for 'keyword' pie mode
// Edit/add keywords to tune classification
const keywordMap = {
    'Hardware': ['no power','no display','blue screen','power','display','screen','monitor','keyboard','mouse','hardware','broken'],
    'Network': ['internet','no connection','network','wifi','lan','connection','router','broadband'],
    'Software': ['error','crash','blue screen','software','install','update','application'],
    'Security': ['security','virus','malware','password','hack','breach'],
    'Account': ['login','password','account','signup','email'],
    'Other': []
};

// escape
function esc(s){ return String(s||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

// helpers
function monthKey(d){ const y=d.getFullYear(); const m=('0'+(d.getMonth()+1)).slice(-2); return `${y}-${m}`; }
function monthLabel(ky){ const [y,m]=ky.split('-'); const date=new Date(y,parseInt(m)-1,1); return date.toLocaleString('default',{month:'short', year:'numeric'}); }
function monthsBetween(start,end){
    const arr=[]; const cur=new Date(start.getFullYear(), start.getMonth(),1); const last=new Date(end.getFullYear(), end.getMonth(),1);
    while(cur<=last){ arr.push(monthKey(cur)); cur.setMonth(cur.getMonth()+1); }
    return arr;
}
function colorForIndex(i){
    const p=['#007bff','#28a745','#ffc107','#dc3545','#17a2b8','#6c757d','#fd7e14','#6f42c1','#20c997','#e83e8c'];
    return p[i%p.length];
}

// Aggregation
function aggregateByMonth(filtered){
    const totals={}, status={}, dept={};
    filtered.forEach(r=>{
        if(!r.date) return;
        const k = monthKey(r.date);
        totals[k] = (totals[k]||0)+1;
        status[k] = status[k]||{Resolved:0,Unresolved:0};
        status[k][r.status] = (status[k][r.status]||0)+1;
        dept[k] = dept[k]||{};
        dept[k][r.department] = (dept[k][r.department]||0)+1;
    });
    return {totals,status,dept};
}

// Build pie groups based on mode
function buildPieGroups(filtered, mode){
    const map = {};
    if(mode==='title'){
        filtered.forEach(r=>{ const k = r.title.trim() || 'No Title'; map[k] = (map[k]||0)+1; });
    } else if(mode==='init_msg'){
        filtered.forEach(r=>{ const k = r.init_msg.trim() || 'No Message'; map[k] = (map[k]||0)+1; });
    } else if(mode==='department'){
        filtered.forEach(r=>{ const k = r.department || 'No Department'; map[k] = (map[k]||0)+1; });
    } else if(mode==='combined'){
        filtered.forEach(r=>{
            const k = (r.title.trim() ? r.title.trim() : (r.init_msg.trim() ? r.init_msg.trim() : 'No Detail'));
            map[k] = (map[k]||0)+1;
        });
    } else if(mode==='keyword'){
        // classify via keywordMap - first-match
        filtered.forEach(r=>{
            const lower = (r.title + ' ' + r.init_msg).toLowerCase();
            let found = false;
            for(const cat in keywordMap){
                for(const kw of keywordMap[cat]){
                    if(kw && lower.includes(kw)){
                        map[cat] = (map[cat]||0)+1; found=true; break;
                    }
                }
                if(found) break;
            }
            if(!found) map['Other'] = (map['Other']||0)+1;
        });
    }
    // reduce map to arrays, but combine small slices into 'Other' if too many categories
    return map;
}

// render/update everything
let chartStatus, chartDept, chartPie;
function updateDashboard(filtered){
    // table
    const tbody=document.querySelector('#reportTable tbody'); tbody.innerHTML='';
    filtered.forEach(r=>{
        const created = r.date ? r.date.toLocaleString() : '';
        tbody.insertAdjacentHTML('beforeend',
            `<tr>
                <td>${esc(r.id)}</td>
                <td>${esc(r.department)}</td>
                <td>${esc(r.title)}</td>
                <td>${esc(r.init_msg)}</td>
                <td>${esc(r.status)}</td>
                <td>${created}</td>
            </tr>`);
    });

    // KPIs
    document.getElementById('kpiTotal').innerText = filtered.length;
    document.getElementById('kpiResolved').innerText = filtered.filter(x=>x.status==='Resolved').length;
    document.getElementById('kpiUnresolved').innerText = filtered.filter(x=>x.status==='Unresolved').length;
    const deptSet = Array.from(new Set(filtered.map(x=>x.department)));
    document.getElementById('kpiDept').innerText = deptSet.length;

    // month range for charts
    const dates = filtered.map(x=>x.date).filter(Boolean);
    let startInput = document.getElementById('startDate').value;
    let endInput = document.getElementById('endDate').value;
    let startDate = startInput ? new Date(startInput) : (dates.length? new Date(Math.min(...dates.map(d=>d.getTime()))) : new Date());
    let endDate = endInput ? new Date(endInput) : (dates.length? new Date(Math.max(...dates.map(d=>d.getTime()))) : new Date());
    startDate = new Date(startDate.getFullYear(), startDate.getMonth(), 1);
    endDate = new Date(endDate.getFullYear(), endDate.getMonth(), 1);

    const months = monthsBetween(startDate, endDate);
    const agg = aggregateByMonth(filtered);

    // Monthly Resolved vs Unresolved
    const labels = months.map(mk=>monthLabel(mk));
    const resolvedData = months.map(mk => (agg.status[mk] && agg.status[mk].Resolved) ? agg.status[mk].Resolved : 0);
    const unresolvedData = months.map(mk => (agg.status[mk] && agg.status[mk].Unresolved) ? agg.status[mk].Unresolved : 0);

    if(chartStatus) chartStatus.destroy();
    chartStatus = new Chart(document.getElementById('monthlyStatus'), {
        type:'bar',
        data:{ labels, datasets:[
            { label:'Resolved', data: resolvedData, backgroundColor:'#28a745' },
            { label:'Unresolved', data: unresolvedData, backgroundColor:'#dc3545' }
        ]},
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}}}
    });

    // Monthly Dept stacked
    const deptSetRange = new Set();
    months.forEach(mk => { const map = agg.dept[mk] || {}; Object.keys(map).forEach(d=>deptSetRange.add(d)); });
    const deptList = Array.from(deptSetRange);
    const deptDatasets = deptList.map((d,i)=>({ label:d, data: months.map(mk => (agg.dept[mk]&&agg.dept[mk][d])?agg.dept[mk][d]:0), backgroundColor: colorForIndex(i) }));
    if(chartDept) chartDept.destroy();
    chartDept = new Chart(document.getElementById('monthlyDept'), {
        type:'bar',
        data:{ labels, datasets: deptDatasets },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}}, scales:{ x:{ stacked:true }, y:{ stacked:true, beginAtZero:true } } }
    });

    // Pie chart (issue concern) depending on selected mode
    const mode = document.getElementById('pieMode').value;
    const pieMap = buildPieGroups(filtered, mode);
    // sort and compact: take top 8 slices, rest -> Other
    const pairs = Object.keys(pieMap).map(k=>[k, pieMap[k]]).sort((a,b)=>b[1]-a[1]);
    const MAX_SLICES = 8;
    const labelsPie = pairs.slice(0,MAX_SLICES).map(p=>p[0]);
    const dataPie = pairs.slice(0,MAX_SLICES).map(p=>p[1]);
    if(pairs.length > MAX_SLICES){
        const rest = pairs.slice(MAX_SLICES).reduce((s,p)=>s+p[1],0);
        labelsPie.push('Other');
        dataPie.push(rest);
    }

    if(chartPie) chartPie.destroy();
    chartPie = new Chart(document.getElementById('issuePie'), {
        type:'pie',
        data:{ labels: labelsPie, datasets:[ { data: dataPie, backgroundColor: labelsPie.map((_,i)=>colorForIndex(i)) } ] },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}} }
    });
}

// Filter apply
function applyFilters(){
    const dept = document.getElementById('filterDept').value;
    const status = document.getElementById('filterStatus').value;
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;

    const filtered = allData.filter(r=>{
        if(dept && r.department !== dept) return false;
        if(status && r.status !== status) return false;
        if(start && r.date){
            const s = new Date(start); s.setHours(0,0,0,0); if(r.date < s) return false;
        }
        if(end && r.date){
            const e = new Date(end); e.setHours(23,59,59,999); if(r.date > e) return false;
        }
        return true;
    });

    updateDashboard(filtered);
}

// Exports
document.getElementById('btnExportPDF').addEventListener('click', async ()=>{
    const el = document.querySelector('.container');
    const canvas = await html2canvas(el, { scale:2, useCORS:true });
    const img = canvas.toDataURL('image/png');
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('l','pt','a4');
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = (canvas.height*pdfWidth)/canvas.width;
    pdf.addImage(img,'PNG',0,0,pdfWidth,pdfHeight);
    pdf.save('helpdesk_report.pdf');
});

document.getElementById('btnExportExcel').addEventListener('click', async ()=>{
    const workbook = new ExcelJS.Workbook();
    const ws = workbook.addWorksheet('Tickets');
    ws.addRow(['ID','Department','Title','Init Message','Status','Created']);
    // read current table rows
    const rows = Array.from(document.querySelectorAll('#reportTable tbody tr'));
    rows.forEach(tr=>{
        const cells = Array.from(tr.querySelectorAll('td')).map(td=>td.innerText);
        ws.addRow(cells);
    });

    // Charts sheet
    const wsCharts = workbook.addWorksheet('Charts');

    // monthly status
    const c1 = document.getElementById('monthlyStatus').toDataURL().split(',')[1];
    const id1 = workbook.addImage({ base64: c1, extension: 'png' });
    wsCharts.addImage(id1,'B2:J20');

    // pie
    const c2 = document.getElementById('issuePie').toDataURL().split(',')[1];
    const id2 = workbook.addImage({ base64: c2, extension: 'png' });
    wsCharts.addImage(id2,'B22:J40');

    // dept
    const c3 = document.getElementById('monthlyDept').toDataURL().split(',')[1];
    const id3 = workbook.addImage({ base64: c3, extension: 'png' });
    wsCharts.addImage(id3,'B42:J60');

    const buffer = await workbook.xlsx.writeBuffer();
    saveAs(new Blob([buffer]), 'helpdesk_report.xlsx');
});

// UI listeners
document.getElementById('filterDept').addEventListener('change', applyFilters);
document.getElementById('filterStatus').addEventListener('change', applyFilters);
document.getElementById('startDate').addEventListener('change', applyFilters);
document.getElementById('endDate').addEventListener('change', applyFilters);
document.getElementById('pieMode').addEventListener('change', applyFilters);

// initialize defaults
(function init(){
    const dates = allData.map(d=>d.date).filter(Boolean);
    if(dates.length){
        const min = new Date(Math.min(...dates.map(d=>d.getTime())));
        const max = new Date(Math.max(...dates.map(d=>d.getTime())));
        document.getElementById('startDate').value = min.toISOString().slice(0,10);
        document.getElementById('endDate').value = max.toISOString().slice(0,10);
    }
    applyFilters();

    const rb = document.getElementById('reportBtn'); if(rb) rb.classList.add('active-menu');
})();
</script>

<?php include('inc/footer.php'); ?>

<?php
include 'init.php';
header('Content-Type: application/json');

// Connect DB
$conn = new mysqli(HOST, USER, PASSWORD, DATABASE);
if ($conn->connect_error) {
    die(json_encode(['error' => $conn->connect_error]));
}

$action = $_POST['action'] ?? '';

switch ($action) {

    /** --------------------------------------
     * 1. LOAD MAIN STATS
     * -------------------------------------- */
    case 'loadStats':

        $where = buildWhere();

        $total = $conn->query("SELECT COUNT(*) AS c FROM hd_tickets $where")->fetch_assoc()['c'];
        $open  = $conn->query("SELECT COUNT(*) AS c FROM hd_tickets $where AND resolved=0")->fetch_assoc()['c'];
        $closed = $conn->query("SELECT COUNT(*) AS c FROM hd_tickets $where AND resolved=1")->fetch_assoc()['c'];

        $avgReplies = $conn->query("
            SELECT AVG(reply_count) AS avg_r
            FROM (
                SELECT COUNT(*) AS reply_count 
                FROM hd_ticket_replies r
                JOIN hd_tickets t ON r.ticket_id=t.id
                $where
                GROUP BY ticket_id
            ) x
        ")->fetch_assoc()['avg_r'] ?? 0;

        echo json_encode([
            'total' => $total,
            'open' => $open,
            'closed' => $closed,
            'avgReplies' => round($avgReplies, 1)
        ]);
        break;

    /** --------------------------------------
     * 2. STATUS PIE CHART
     * -------------------------------------- */
    case 'chartStatus':
        $where = buildWhere();

        $open = $conn->query("SELECT COUNT(*) AS c FROM hd_tickets $where AND resolved=0")->fetch_assoc()['c'];
        $closed = $conn->query("SELECT COUNT(*) AS c FROM hd_tickets $where AND resolved=1")->fetch_assoc()['c'];

        echo json_encode([ 'open'=>$open, 'closed'=>$closed ]);
        break;

    /** --------------------------------------
     * 3. DEPARTMENT BAR CHART
     * -------------------------------------- */
    case 'chartDepartments':
        $where = buildWhere();
        $sql = "
            SELECT d.name, COUNT(*) as c
            FROM hd_tickets t
            LEFT JOIN hd_departments d ON t.department = d.id
            $where
            GROUP BY d.id
        ";
        $res = $conn->query($sql);
        $labels = [];
        $data = [];

        while($row = $res->fetch_assoc()){
            $labels[] = $row['name'];
            $data[] = $row['c'];
        }

        echo json_encode(['labels'=>$labels, 'data'=>$data]);
        break;

    /** --------------------------------------
     * 4. TIMELINE (last 12 months)
     * -------------------------------------- */
    case 'chartTimeline':
        $where = buildWhere();

        $sql = "
            SELECT DATE_FORMAT(FROM_UNIXTIME(date),'%Y-%m') AS ym,
                   COUNT(*) AS total
            FROM hd_tickets
            $where
            GROUP BY ym
            ORDER BY ym ASC
        ";
        $res = $conn->query($sql);
        $labels = [];
        $data = [];

        while($row = $res->fetch_assoc()){
            $labels[] = $row['ym'];
            $data[] = $row['total'];
        }

        echo json_encode(['labels'=>$labels, 'data'=>$data]);
        break;

    /** --------------------------------------
     * 5. DATATABLE DATA
     * -------------------------------------- */
    case 'loadTable':

        $where = buildWhere();
        $sql = "
            SELECT t.*, 
                   u.name AS creator,
                   d.name AS dept,
                   (SELECT COUNT(*) FROM hd_ticket_replies r WHERE r.ticket_id=t.id) AS replies
            FROM hd_tickets t
            LEFT JOIN hd_users u ON t.user = u.id
            LEFT JOIN hd_departments d ON t.department = d.id
            $where
            ORDER BY t.id DESC
        ";

        $res = $conn->query($sql);
        $data = [];
        $sn = 1;

        while($row = $res->fetch_assoc()){
            $data[] = [
                $sn++,
                $row['uniqid'],
                $row['title'],
                $row['dept'],
                $row['creator'],
                date("Y-m-d H:i", $row['date']),
                $row['resolved'] ? "Closed" : "Open",
                $row['replies'],
                "<a href='ticket_action.php?id={$row['uniqid']}' class='btn btn-primary btn-sm'>View</a>"
            ];
        }

        echo json_encode(["data"=>$data]);
        break;

    default:
        echo json_encode(['error'=>'Invalid action']);
}


/** ============================
 * Helper for WHERE conditions
 * ============================ */
function buildWhere(){
    $where = "WHERE 1=1";

    if(!empty($_POST['department']))
        $where .= " AND department=".(int)$_POST['department'];

    if(!empty($_POST['status']))
        $where .= " AND resolved=".(int)$_POST['status'];

    if(!empty($_POST['from']))
        $where .= " AND date >= UNIX_TIMESTAMP('{$_POST['from']} 00:00:00')";

    if(!empty($_POST['to']))
        $where .= " AND date <= UNIX_TIMESTAMP('{$_POST['to']} 23:59:59')";

    return $where;
}

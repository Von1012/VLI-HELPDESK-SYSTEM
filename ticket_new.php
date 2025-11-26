<?php
include 'init.php';
if(!$users->isLoggedIn()){
    header("Location: login.php");
    exit;
}
include('inc/header.php');
$user = $users->getUserInfo();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Helpdesk — Tickets</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css"/>
<link rel="stylesheet" href="assets/css/report.css">
</head>
<body>
<?php include('inc/container.php'); ?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Tickets</h2>
        <div>
            <button id="createTicket" class="btn btn-success"><i class="fa fa-plus"></i> Create Ticket</button>
            <a href="report.php" class="btn btn-outline-secondary"><i class="fa fa-chart-pie"></i> Reports</a>
        </div>
    </div>

    <p class="text-muted">View and manage tickets.</p>

    <div class="card">
        <div class="card-body">
            <table id="listTickets" class="table table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Department</th>
                        <th>Created By</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Replies</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- modal: create/edit -->
<div class="modal fade" id="ticketModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="ticketForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa fa-plus"></i> Create Ticket</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label for="subject" class="form-label">Subject</label>
                <input id="subject" name="subject" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="department" class="form-label">Department</label>
                <select id="department" name="department" class="form-select">
                    <?php $tickets->getDepartments(); ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea id="message" name="message" rows="5" class="form-control"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="status" id="open" value="0" checked>
                    <label class="form-check-label" for="open">Open</label>
                </div>
                <?php if(isset($_SESSION['admin'])): ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="status" id="close" value="1">
                    <label class="form-check-label" for="close">Close</label>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" name="ticketId" id="ticketId">
            <input type="hidden" name="action" id="action" value="">
            <button type="submit" id="save" class="btn btn-primary">Save Ticket</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include('inc/footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function(){
    // DataTable
    var table = $('#listTickets').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'ticket_action.php',
            type: 'POST',
            data: { action: 'listTicket' }
        },
        columns: [
            { data: 0 },{ data: 1 },{ data: 2 },{ data: 3 },{ data: 4 },{ data: 5 },
            { data: 6 },{ data: 7 },{ data: 8 }
        ],
        pageLength: 10,
        order:[[5,'desc']]
    });

    // create ticket
    $('#createTicket').on('click', function(){
        $('#ticketForm')[0].reset();
        $('#action').val('createTicket');
        $('#ticketId').val('');
        var modal = new bootstrap.Modal(document.getElementById('ticketModal'));
        modal.show();
    });

    // submit ticket (create/update)
    $(document).on('submit', '#ticketForm', function(e){
        e.preventDefault();
        $('#save').attr('disabled', true);
        $.post('ticket_action.php', $(this).serialize(), function(){
            $('#save').attr('disabled', false);
            $('#ticketModal').modal('hide');
            table.ajax.reload();
        }).fail(function(){ alert('Error saving ticket.'); $('#save').attr('disabled', false); });
    });

    // edit (row actions depend on your server response HTML)
    $(document).on('click', '.edit-btn', function(){
        var ticketId = $(this).data('id');
        $.post('ticket_action.php',{action:'getTicketDetails', ticketId: ticketId}, function(data){
            $('#ticketId').val(data.id);
            $('#subject').val(data.title);
            $('#message').val(data.init_msg);
            $('#department').val(data.department);
            if(data.resolved == '1') $('#close').prop('checked', true); else $('#open').prop('checked', true);
            $('#action').val('updateTicket');
            var modal = new bootstrap.Modal(document.getElementById('ticketModal'));
            modal.show();
        }, 'json');
    });

    // view
    $(document).on('click', '.view-btn', function(){
        var id = $(this).data('id');
        window.location.href = 'view_ticket.php?id=' + encodeURIComponent(id);
    });

    // close
    $(document).on('click', '.close-btn', function(){
        if(!confirm('Close this ticket?')) return;
        var id = $(this).data('id');
        $.post('ticket_action.php', {action:'closeTicket', ticketId: id}, function(){
            table.ajax.reload();
        });
    });

});
</script>

</body>
</html>

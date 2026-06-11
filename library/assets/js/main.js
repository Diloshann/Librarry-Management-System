// ATI Library Management System - Main JS

// Confirm delete
function confirmDelete(msg) {
    return confirm(msg || 'Are you sure you want to delete this? This action cannot be undone.');
}

// Flash message auto-hide
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert-auto');
    alerts.forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 500);
        }, 4000);
    });

    // Modal close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });
});

function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

// Preview book cover image
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById(previewId).src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Search table rows
function searchTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var filter = input.value.toLowerCase();
    var rows = document.querySelectorAll('#' + tableId + ' tbody tr');
    rows.forEach(function (row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// Sidebar mobile toggle
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
}

// Print receipt
function printDiv(divId) {
    var content = document.getElementById(divId).innerHTML;
    var w = window.open('', '_blank');
    w.document.write('<html><head><title>ATI Library Receipt</title>');
    w.document.write('<style>body{font-family:Arial;padding:20px} table{width:100%;border-collapse:collapse} td,th{border:1px solid #ccc;padding:8px}</style>');
    w.document.write('</head><body>' + content + '</body></html>');
    w.document.close();
    w.print();
}

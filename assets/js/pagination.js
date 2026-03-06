// Simple table pagination - 5 rows per page
let paginationStates = {};

function paginateTable(tableId) {
    const table = document.querySelector(tableId + ' table');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => !row.querySelector('td[colspan]'));
    if (rows.length <= 5) return;
    
    const rowsPerPage = 5;
    paginationStates[tableId] = { currentPage: 1, totalPages: Math.ceil(rows.length / rowsPerPage), rows };
    
    function showPage(page) {
        const state = paginationStates[tableId];
        rows.forEach((row, index) => {
            row.style.display = (index >= (page - 1) * rowsPerPage && index < page * rowsPerPage) ? '' : 'none';
        });
        updatePagination();
    }
    
    function updatePagination() {
        const state = paginationStates[tableId];
        const container = document.querySelector(tableId);
        let pagination = container.querySelector('.pagination');
        
        if (!pagination) {
            pagination = document.createElement('div');
            pagination.className = 'pagination';
            container.appendChild(pagination);
        }
        
        pagination.innerHTML = `
            <button onclick="changePage('${tableId}', ${state.currentPage - 1})" ${state.currentPage === 1 ? 'disabled' : ''}>Previous</button>
            <span>Page ${state.currentPage} of ${state.totalPages}</span>
            <button onclick="changePage('${tableId}', ${state.currentPage + 1})" ${state.currentPage === state.totalPages ? 'disabled' : ''}>Next</button>
        `;
    }
    
    window.changePage = function(tableId, page) {
        const state = paginationStates[tableId];
        if (!state || page < 1 || page > state.totalPages) return;
        state.currentPage = page;
        showPage(page);
    };
    
    showPage(1);
}

// Auto-apply to all tables on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.data-table').forEach((table, index) => {
        table.id = table.id || 'table-' + index;
        paginateTable('#' + table.id);
    });
});

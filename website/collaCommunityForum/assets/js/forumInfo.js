document.addEventListener('DOMContentLoaded', function() {
    // Member search and filtering elements
    const memberSearch = document.getElementById('member-search');
    const roleFilter = document.getElementById('role-filter');
    const applyFiltersBtn = document.getElementById('apply-filters');
    const resetFiltersBtn = document.getElementById('reset-filters');
    const memberRows = document.querySelectorAll('.member-table tbody tr');

    // Remove confirmation dialog elements
    const removeConfirmDialog = document.getElementById('remove-confirm-dialog');
    const closeRemoveDialogBtn = removeConfirmDialog ? removeConfirmDialog.querySelector('.close-btn') : null;
    const cancelRemoveBtn = removeConfirmDialog ? removeConfirmDialog.querySelector('.cancel-btn') : null;
    const confirmRemoveBtn = removeConfirmDialog ? removeConfirmDialog.querySelector('.confirm-remove-btn') : null;
    const removeMemberId = document.getElementById('remove-member-id');
    let memberToRemove = null;

    // Edit role dialog elements
    const editRoleDialog = document.getElementById('edit-role-dialog');
    const closeEditDialogBtn = editRoleDialog ? editRoleDialog.querySelector('.close-btn') : null;
    const cancelEditBtn = editRoleDialog ? editRoleDialog.querySelector('.cancel-btn') : null;
    const confirmEditBtn = editRoleDialog ? editRoleDialog.querySelector('.confirm-edit-btn') : null;
    const roleSelect = document.getElementById('role-select');
    const editMemberId = document.getElementById('edit-member-id');
    let memberToEdit = null;

    // Join request view dialog elements
    const viewRequestDialog = document.getElementById('view-request-dialog');
    const closeViewDialogBtn = viewRequestDialog ? viewRequestDialog.querySelector('.close-btn') : null;
    const cancelViewBtn = viewRequestDialog ? viewRequestDialog.querySelector('.cancel-btn') : null;
    const requestIdDisplay = document.getElementById('request-id-display');
    const requestUserDisplay = document.getElementById('request-user-display');
    const requestReasonDisplay = document.getElementById('request-reason-display');
    const requestIdInput = document.getElementById('request-id-input');

    // Toast notification system elements
    const toastNotification = document.getElementById('toast-notification');
    const toastMessage = document.querySelector('.toast-message');
    const toastClose = document.querySelector('.toast-close');
    const toastIcon = document.querySelector('.toast-icon');

    // Log initialization for debugging
    console.log("DOM fully loaded. Starting script initialization...");
    console.log("Member search element:", memberSearch);
    console.log("Member rows found:", memberRows.length);

    // Toast functions
    function showToast(message, type = 'info', duration = 5000) {
        if (!toastNotification || !toastMessage) {
            console.error("Toast notification elements not found.");
            alert(message);
            return;
        }
        toastMessage.textContent = message;
        toastNotification.className = 'toast-notification ' + type;
        if (toastIcon) {
            switch (type) {
                case 'success':
                    toastIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
                    break;
                case 'error':
                    toastIcon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
                    break;
                case 'warning':
                    toastIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                    break;
                default:
                    toastIcon.innerHTML = '<i class="fas fa-info-circle"></i>';
            }
        }
        setTimeout(() => {
            toastNotification.classList.add('show');
        }, 100);
        if (duration > 0) {
            setTimeout(() => { hideToast(); }, duration);
        }
    }

    function hideToast() {
        if (toastNotification) {
            toastNotification.classList.remove('show');
        }
    }

    if (toastClose) {
        toastClose.addEventListener('click', hideToast);
    }

    // Toast messages based on URL parameters (status / error)
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const error = urlParams.get('error');

    if (status === 'approved') {
        showToast('Join request has been approved! The user has been successfully added to the forum.', 'success');
    } else if (status === 'rejected') {
        showToast('Join request has been rejected.', 'info');
    } else if (error) {
        let errorMessage = 'An error occurred while processing the request.';
        switch (error) {
            case 'unauthorized':
                errorMessage = 'You are not authorized to perform this action.';
                break;
            case 'member_add_failed':
                errorMessage = 'Failed to add user to the forum. Please try again.';
                break;
            case 'request_not_found':
                errorMessage = 'The join request could not be found.';
                break;
            case 'invalid_action':
                errorMessage = 'Invalid action. Please try again.';
                break;
        }
        showToast(errorMessage, 'error');
    }

    // Filter functions
    function applyFilters() {
        console.log("Applying filters...");
        const searchValue = memberSearch ? memberSearch.value.toLowerCase() : '';
        const roleValue = roleFilter ? roleFilter.value.toLowerCase() : '';
        memberRows.forEach(row => {
            const nameElement = row.querySelector('.member-name');
            const emailElement = row.querySelector('.member-email');
            if (!nameElement || !emailElement) {
                console.error("Missing name or email element in row");
                return;
            }
            const name = nameElement.textContent.toLowerCase();
            const email = emailElement.textContent.toLowerCase();
            const role = row.dataset.role ? row.dataset.role.toLowerCase() : '';
            const matchesSearch = searchValue === '' || name.includes(searchValue) || email.includes(searchValue);
            const matchesRole = roleValue === '' || role === roleValue;
            if (matchesSearch && matchesRole) {
                row.style.display = '';
                setTimeout(() => { row.style.opacity = '1'; }, 50);
            } else {
                row.style.opacity = '0';
                setTimeout(() => { row.style.display = 'none'; }, 300);
            }
        });
    }

    function resetFilters() {
        console.log("Resetting filters...");
        if (memberSearch) memberSearch.value = '';
        if (roleFilter) roleFilter.value = '';
        memberRows.forEach(row => {
            row.style.display = '';
            setTimeout(() => { row.style.opacity = '1'; }, 50);
        });
    }

    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', applyFilters);
        console.log("Apply filters button listener attached.");
    }
    
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', resetFilters);
        console.log("Reset filters button listener attached.");
    }

    if (memberSearch) {
        memberSearch.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') { applyFilters(); }
        });
    }

    // Close dialog helper functions
    function closeRemoveDialogFunc() {
        if (removeConfirmDialog) {
            removeConfirmDialog.classList.remove('show');
            memberToRemove = null;
        }
    }
    function closeEditDialogFunc() {
        if (editRoleDialog) {
            editRoleDialog.classList.remove('show');
            memberToEdit = null;
        }
    }
    function closeViewDialogFunc() {
        if (viewRequestDialog) {
            viewRequestDialog.classList.remove('show');
        }
    }

    // --- Event Delegation for Remove, Edit, and View Actions ---
    document.addEventListener('click', function(e) {
        // Remove button functionality
        const removeBtn = e.target.closest('.remove-btn');
        if (removeBtn) {
            e.preventDefault();
            memberToRemove = removeBtn.getAttribute('data-id');
            console.log("Remove button clicked for member:", memberToRemove);
            if (removeMemberId) { removeMemberId.textContent = memberToRemove; }
            if (removeConfirmDialog) { removeConfirmDialog.classList.add('show'); }
            else { console.error("Remove confirmation dialog not found."); }
            return;
        }

        // Confirm Remove action
        if (e.target.closest('.confirm-remove-btn')) {
            e.preventDefault();
            if (!memberToRemove) {
                console.error("No member selected for removal.");
                return;
            }
            const confirmBtn = e.target.closest('.confirm-remove-btn');
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            confirmBtn.disabled = true;
            const communityId = urlParams.get('id');
            console.log("Removing member:", memberToRemove, "from community:", communityId);
            fetch('../controllers/removeMemberForumController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    membership_id: memberToRemove,
                    community_id: communityId
                }),
            })
            .then(response => {
                console.log("Remove fetch response status:", response.status);
                if (!response.ok) { throw new Error('Network response was not ok: ' + response.status); }
                return response.json();
            })
            .then(data => {
                console.log("Remove response data:", data);
                if (data.success) {
                    const row = document.querySelector(`tr[data-id="${memberToRemove}"]`);
                    if (row) {
                        row.style.transition = 'opacity 0.5s ease';
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            updateMemberStats();
                        }, 500);
                    } else { console.error("Row not found for member:", memberToRemove); }
                    closeRemoveDialogFunc();
                    showToast('Member removed successfully!', 'success');
                } else {
                    throw new Error(data.message || 'Error removing member');
                }
            })
            .catch(error => {
                console.error('Error removing member:', error);
                showToast(`Error: ${error.message}`, 'error');
            })
            .finally(() => {
                confirmBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Remove';
                confirmBtn.disabled = false;
            });
            return;
        }

        // Cancel remove action (close dialog)
        if (e.target.closest('.cancel-btn') && removeConfirmDialog && removeConfirmDialog.classList.contains('show')) {
            closeRemoveDialogFunc();
            return;
        }

        // Edit button functionality for updating role
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            e.preventDefault();
            memberToEdit = editBtn.getAttribute('data-id');
            const currentRole = editBtn.getAttribute('data-role');
            console.log("Edit button clicked for member:", memberToEdit, "current role:", currentRole);
            if (roleSelect) { roleSelect.value = currentRole; }
            if (editMemberId) { editMemberId.textContent = memberToEdit; }
            if (editRoleDialog) { editRoleDialog.classList.add('show'); }
            else { console.error("Edit role dialog not found."); }
            return;
        }

        // Confirm role edit action
        if (e.target.closest('.confirm-edit-btn')) {
            e.preventDefault();
            if (!memberToEdit) {
                console.error("No member selected for role update.");
                return;
            }
            const confirmEditButton = e.target.closest('.confirm-edit-btn');
            const newRole = roleSelect ? roleSelect.value : 'member';
            console.log("Updating role for member:", memberToEdit, "to:", newRole);
            confirmEditButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            confirmEditButton.disabled = true;
            const communityId = urlParams.get('id');
            fetch('../controllers/updateMemberRoleController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    membership_id: memberToEdit,
                    role: newRole,
                    community_id: communityId
                }),
            })
            .then(response => {
                console.log("Edit fetch response status:", response.status);
                if (!response.ok) { throw new Error('Network response was not ok: ' + response.status); }
                return response.json();
            })
            .then(data => {
                console.log("Edit response data:", data);
                if (data.success) {
                    const row = document.querySelector(`tr[data-id="${memberToEdit}"]`);
                    if (row) {
                        const roleElement = row.querySelector('.role-badge');
                        if (roleElement) {
                            roleElement.classList.add('role-updated');
                            roleElement.textContent = newRole.charAt(0).toUpperCase() + newRole.slice(1);
                            roleElement.className = `role-badge role-${newRole} role-updated`;
                            setTimeout(() => { roleElement.classList.remove('role-updated'); }, 2000);
                        } else {
                            console.error("Role badge not found for member:", memberToEdit);
                        }
                        row.dataset.role = newRole;
                        const editBtnInRow = row.querySelector('.edit-btn');
                        if (editBtnInRow) { editBtnInRow.dataset.role = newRole; }
                    } else { console.error("Row not found for member:", memberToEdit); }
                    updateMemberStats();
                    closeEditDialogFunc();
                    showToast(`Member role updated to ${newRole.charAt(0).toUpperCase() + newRole.slice(1)}`, 'success');
                } else {
                    throw new Error(data.message || 'Failed to update member role');
                }
            })
            .catch(error => {
                console.error('Error updating role:', error);
                showToast(`Error: ${error.message}`, 'error');
            })
            .finally(() => {
                confirmEditButton.innerHTML = '<i class="fas fa-check"></i> Save Changes';
                confirmEditButton.disabled = false;
            });
            return;
        }

        // Cancel edit action (close edit dialog)
        if (e.target.closest('.cancel-btn') && editRoleDialog && editRoleDialog.classList.contains('show')) {
            closeEditDialogFunc();
            return;
        }

        // View request functionality
        const viewRequestBtn = e.target.closest('.view-request-btn');
        if (viewRequestBtn) {
            e.preventDefault();
            const requestId = viewRequestBtn.getAttribute('data-id');
            const reason = viewRequestBtn.getAttribute('data-reason');
            const userId = viewRequestBtn.getAttribute('data-user');
            console.log("View request button clicked for request:", requestId);
            if (requestIdDisplay) { requestIdDisplay.textContent = requestId; }
            if (requestUserDisplay) { requestUserDisplay.textContent = userId; }
            if (requestReasonDisplay) { requestReasonDisplay.textContent = reason; }
            if (requestIdInput) { requestIdInput.value = requestId; }
            if (viewRequestDialog) { viewRequestDialog.classList.add('show'); }
            else { console.error("View request dialog not found."); }
            return;
        }
        if (e.target.closest('.close-btn') && viewRequestDialog && viewRequestDialog.classList.contains('show')) {
            closeViewDialogFunc();
            return;
        }
        if (e.target.closest('.cancel-btn') && viewRequestDialog && viewRequestDialog.classList.contains('show')) {
            closeViewDialogFunc();
            return;
        }
    });

    // Close dialog buttons outside modals (if any)
    if (closeRemoveDialogBtn) { closeRemoveDialogBtn.addEventListener('click', closeRemoveDialogFunc); }
    if (closeEditDialogBtn) { closeEditDialogBtn.addEventListener('click', closeEditDialogFunc); }
    if (cancelEditBtn) { cancelEditBtn.addEventListener('click', closeEditDialogFunc); }
    if (cancelRemoveBtn) { cancelRemoveBtn.addEventListener('click', closeRemoveDialogFunc); }
    if (closeViewDialogBtn) { closeViewDialogBtn.addEventListener('click', closeViewDialogFunc); }
    if (cancelViewBtn) { cancelViewBtn.addEventListener('click', closeViewDialogFunc); }

    // Close dialogs when clicking outside them
    window.addEventListener('click', function(event) {
        if (event.target === removeConfirmDialog) { closeRemoveDialogFunc(); }
        if (event.target === editRoleDialog) { closeEditDialogFunc(); }
        if (event.target === viewRequestDialog) { closeViewDialogFunc(); }
    });

    // Update member statistics
    function updateMemberStats() {
        console.log("Updating member statistics...");
        const visibleRows = Array.from(document.querySelectorAll('.member-table tbody tr')).filter(row => 
            window.getComputedStyle(row).display !== 'none'
        );
        const adminCount = visibleRows.filter(row => row.dataset.role === 'admin').length;
        const moderatorCount = visibleRows.filter(row => row.dataset.role === 'moderator').length;
        const memberCount = visibleRows.filter(row => row.dataset.role === 'member').length;
        console.log("Counts - Admin:", adminCount, "Moderator:", moderatorCount, "Member:", memberCount);
        const statCards = document.querySelectorAll('.stat-number');
        if (statCards.length >= 3) {
            updateStatWithAnimation(statCards[0], adminCount);
            updateStatWithAnimation(statCards[1], moderatorCount);
            updateStatWithAnimation(statCards[2], memberCount);
        } else {
            console.error("Could not find all stat cards. Found:", statCards.length);
        }
    }

    function updateStatWithAnimation(element, newValue) {
        element.classList.add('stat-updated');
        element.textContent = newValue;
        setTimeout(() => { element.classList.remove('stat-updated'); }, 2000);
    }

    console.log("Script initialization complete.");
});
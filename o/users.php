<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini."; 
    header("Location: ../login.php"); 
    exit;
}

// Cek apakah level pengguna adalah "BOD"
if ($_SESSION['level'] !== 'bod') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini."; 
    header("Location: ../login.php"); 
    exit;
}

// Buat CSRF token jika belum ada
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include '_header.php';
include '../includes/config.php'; // Koneksi ke database

// Ambil semua data user kecuali superadmin
$sql = "
    SELECT 
        users.id, 
        users.username, 
        users.fullname, 
        users.email, 
        users.phone, 
        users.flagactive, 
        users.level, 
        users.picture, 
		users.id_cabang,
        cabang.nama_cabang 
    FROM users 
    LEFT JOIN cabang ON users.id_cabang = cabang.id_cabang
    WHERE users.level != 'superadmin'
    ORDER BY users.id DESC";
$result = $conn->query($sql);
?>
<style>

.success {
    color: green !important; /* Warna hijau untuk pesan sukses */
}

/* Kelas untuk pesan error */
.error {
    color: red !important; /* Warna merah untuk pesan error */
}

/* CSS untuk gambar lingkaran */
.circle-image {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ccc;
}
</style>

<div class="container">
    <div class="row">
        <div class="col-md-12 grid-margin">
            <div class="row">
                <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                    <h3 class="font-weight-bold">Data User Bebas Cedera</h3>
                    <h6 class="font-weight-normal mb-0">Semua data user yang terdaftar di sistem klinik Bebas Cedera
                        <a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            + Tambah User
                        </a>
                    </h6>
                </div>
            </div>
        </div>
    </div>

    <!-- Tampilkan pesan sukses atau error -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php elseif (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
	
	<div class="table-responsive">
    <table class="table table-hover table-striped" id="usersTable">
        <thead>
            <tr>
                <th class="text-center">No</th>
				<th class="text-left">Gambar</th>
                <th class="text-left">Username</th>
                <th class="text-left">Full Name</th>
                <th class="text-left">Email</th>
                <th class="text-left">Phone</th>
                <th class="text-left">Status</th>
                <th class="text-left">Level</th>
                <th class="text-left">Cabang</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
			$no = 0;
			while($row = $result->fetch_assoc()): 
			$no++;
			$picture = $row['picture'] ? '../assets/images/faces/' . $row['picture'] : '../assets/images/faces/no-images.png'; 
			?>
                <tr>
                    <td class="text-center"><?php echo $no; ?>.</td>
					<td class="text-left"><img src="<?php echo $picture; ?>" alt="Profile Picture" class="circle-image"></td> 
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo $row['flagactive'] ? 'Active' : 'Inactive'; ?></td>
                    <td><?php echo htmlspecialchars($row['level']); ?></td>
					<td><?php echo htmlspecialchars($row['nama_cabang'] ?? 'N/A'); ?></td>
                    <td>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#editUserModal" 
						   data-id="<?php echo $row['id']; ?>" 
						   data-username="<?php echo htmlspecialchars($row['username']); ?>" 
						   data-fullname="<?php echo htmlspecialchars($row['fullname']); ?>" 
						   data-email="<?php echo htmlspecialchars($row['email']); ?>" 
						   data-phone="<?php echo htmlspecialchars($row['phone']); ?>" 
						   data-level="<?php echo $row['level']; ?>"
						   data-cabang="<?php echo $row['id_cabang']; ?>"><i class="fas fa-pencil-alt"></i> Edit</a> |
                        <a href="#" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-id="<?php echo $row['id']; ?>"><i class="fas fa-trash"></i> Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
	</div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="add-user.php" method="POST" id="addUserForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Tambah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required maxlength="40">
                        <small id="usernameFeedback" class="text-danger"></small>
                    </div>
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required maxlength="50">
                        <small id="fullnameFeedback" class="text-danger"></small>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <small id="emailFeedback" class="text-danger"></small>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required maxlength="14">
                        <small id="phoneFeedback" class="text-danger"></small>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        <small id="passwordFeedback" class="text-danger"></small>
                    </div>
                    <div class="mb-3">
                        <label for="level" class="form-label">Level</label>
                        <select class="form-control" id="level" name="level" required>
                            <option value="" selected>Pilih Level User</option>
                            <option value="customer">Customer</option>
                            <option value="employee">Employee</option>
                            <option value="cabang">Admin Cabang</option>							
                            <option value="cs">Customer Service</option>
                            <option value="hrd">HRD</option>
                            <option value="bod">BOD</option>
                        </select>
                    </div>
					<?php
					// Ambil daftar cabang dari database
					$cabangOptions = [];
					$cabangQuery = "SELECT id_cabang, nama_cabang, kota FROM cabang ORDER BY nama_cabang ASC";
					$cabangResult = $conn->query($cabangQuery);
					if ($cabangResult->num_rows > 0) {
						while ($row = $cabangResult->fetch_assoc()) {
							$cabangOptions[] = $row;
						}
					}
					?>
					<div class="mb-3">
                        <label for="idCabang" class="form-label">Cabang</label>
                        <select class="form-control" id="cabang" name="cabang" required>
                            <option value="" selected>Pilih Cabang</option>
                            <?php foreach ($cabangOptions as $cabang): ?>
                                <option value="<?= htmlspecialchars($cabang['id_cabang']) ?>">
                                    <?= htmlspecialchars($cabang['nama_cabang']) ?> - <?= htmlspecialchars($cabang['kota']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="submitBtn" class="btn btn-teal" disabled>Tambah User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Delete User -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="delete-user.php" method="POST" id="deleteUserForm">
                <!-- Token CSRF -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <!-- ID User -->
                <input type="hidden" id="deleteUserId" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Hapus User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteMessage">Apakah Anda yakin ingin menghapus user ini?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="edit-user.php" method="POST" id="editUserForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Username (cannot be edited) -->
                    <div class="mb-3">
                        <label for="editUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="editUsername" name="username" readonly>
                    </div>
                    <!-- Full Name -->
                    <div class="mb-3">
                        <label for="editFullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="editFullname" name="fullname" required maxlength="50">
                        <small id="editFullnameFeedback" class="text-danger"></small>
                    </div>
                    <!-- Email -->
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editEmail" name="email" required>
                        <small id="editEmailFeedback" class="text-danger"></small>
                    </div>
                    <!-- Phone -->
                    <div class="mb-3">
                        <label for="editPhone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="editPhone" name="phone" required maxlength="14">
                        <small id="editPhoneFeedback" class="text-danger"></small>
                    </div>
                    <!-- Level -->
                    <div class="mb-3">
                        <label for="editLevel" class="form-label">Level</label>
                        <select class="form-control" id="editLevel" name="level" required>
                            <option value="customer">Customer</option>
                            <option value="employee">Employee</option>
                            <option value="cabang">Admin Cabang</option>							
                            <option value="cs">Customer Service</option>
                            <option value="hrd">HRD</option>
                            <option value="bod">BOD</option>
                        </select>
                    </div>
					 <!-- Cabang -->
                    <div class="mb-3">
                        <label for="editCabang" class="form-label">Cabang</label>
						<select class="form-control" id="editCabang" name="cabang" required>
                            <option value="">Pilih Cabang</option>
                            <?php
                            // Ambil daftar cabang dari database
                            $cabangQuery = "SELECT id_cabang, nama_cabang, kota FROM cabang ORDER BY nama_cabang ASC";
                            $cabangResult = $conn->query($cabangQuery);
                            while ($cabangRow = $cabangResult->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($cabangRow['id_cabang']) . '">' . htmlspecialchars($cabangRow['nama_cabang'] . ' - ' . $cabangRow['kota']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <!-- Hidden Input for User ID -->
                    <input type="hidden" id="editUserId" name="id">
					
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="editSubmitBtn" class="btn btn-teal" disabled>Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var jqOld = $.noConflict(true);

jqOld(document).ready(function() {
    var isSmallScreen = window.innerWidth < 1024; // Cek jika ukuran layar kurang dari 1024px

    jqOld('#usersTable').DataTable({
        "pagingType": "full_numbers",
        "pageLength": 20,
        "lengthChange": true,
        "ordering": false,
        "searching": true,
        "responsive": isSmallScreen // Hanya responsif jika ukuran layar kecil
    });
});


//Tambah User
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const fullnameInput = document.getElementById('fullname');
    const phoneInput = document.getElementById('phone');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    let formSubmitted = false;

    let isUsernameAvailable = false;
    let isEmailAvailable = false;
	
	//Function validate form
	function validateForm() {
	const username = usernameInput.value;
	const fullname = fullnameInput.value;
	const phone = phoneInput.value;
	const password = passwordInput.value;

	const isUsernameValid = /^[a-zA-Z0-9_]+$/.test(username);
	const isFullnameValid = /^[a-zA-Z0-9\s_]+$/.test(fullname) && fullname.length <= 50;  // Huruf, angka, spasi, dan _
	const isPhoneValid = /^[0-9]+$/.test(phone) && phone.length <= 14;
	const isPasswordValid = password.length >= 6;

	//document.getElementById('usernameFeedback').textContent = isUsernameValid ? '' : 'Username hanya boleh berisi huruf, angka, dan _';
	document.getElementById('fullnameFeedback').textContent = isFullnameValid ? '' : 'Full Name hanya boleh berisi huruf, angka, spasi, dan _.';
	document.getElementById('phoneFeedback').textContent = isPhoneValid ? '' : 'Nomor telepon harus angka dan maksimal 14 karakter.';
	document.getElementById('passwordFeedback').textContent = isPasswordValid ? '' : 'Password minimal 6 karakter.';

	submitBtn.disabled = !(isUsernameValid && isFullnameValid && isPhoneValid && isPasswordValid && isUsernameAvailable && isEmailAvailable);
}

    usernameInput.addEventListener('input', validateForm);
    fullnameInput.addEventListener('input', validateForm);
    phoneInput.addEventListener('input', validateForm);
    passwordInput.addEventListener('input', validateForm);

    // AJAX untuk validasi username
    usernameInput.addEventListener('blur', function() {
        const username = this.value;
        const feedback = document.getElementById('usernameFeedback');
		const usernameRegex = /^[a-zA-Z0-9_]+$/;
        
        if (username === '') {
        feedback.textContent = 'Username tidak boleh kosong.'; // Tampilkan pesan jika kosong
        feedback.classList.add('error'); // Tambahkan kelas error
        feedback.classList.remove('success'); // Pastikan kelas success dihapus jika ada
        isUsernameAvailable = false; // Username tidak tersedia
        validateForm(); // Periksa ulang form
        return;
		}
		
		if (!usernameRegex.test(username)) {
        feedback.textContent = 'Username hanya boleh berisi huruf, angka, dan _ tanpa spasi'; // Pesan jika format tidak valid
        feedback.classList.add('error');
        feedback.classList.remove('success');
        isUsernameAvailable = false; // Username tidak valid
        validateForm(); // Periksa ulang form
        return;
    }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'check_username.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                if (xhr.responseText === 'taken') {
					feedback.textContent = 'Username sudah digunakan.';
					feedback.classList.add('error');
					feedback.classList.remove('success'); // Pastikan kelas success dihapus jika ada
					isUsernameAvailable = false;
				} else {
					feedback.textContent = 'Username tersedia.';
					feedback.classList.add('success');
					feedback.classList.remove('error'); // Hapus kelas error jika sebelumnya ada
					isUsernameAvailable = true;
				}
                validateForm();
            }
        };
        xhr.send('username=' + encodeURIComponent(username));
    });

    // AJAX untuk validasi email
    emailInput.addEventListener('blur', function() {
        const email = this.value;
        const feedback = document.getElementById('emailFeedback');
        
        if (email === '') {
            feedback.textContent = '';
            isEmailAvailable = false;
            validateForm();
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'check_email.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                if (xhr.responseText === 'taken') {
					feedback.textContent = 'Email sudah digunakan.';
					feedback.classList.add('error');
					feedback.classList.remove('success'); // Pastikan kelas success dihapus jika ada
					isEmailAvailable = false;
				} else {
					feedback.textContent = 'Email tersedia.';
					feedback.classList.add('success');
					feedback.classList.remove('error'); // Hapus kelas error jika sebelumnya ada
					isEmailAvailable = true;
				}
                validateForm();
            }
        };
        xhr.send('email=' + encodeURIComponent(email));
    });

    // Cegah double submit
    document.getElementById('addUserForm').addEventListener('submit', function(event) {
        if (formSubmitted) {
            event.preventDefault();
        } else {
            formSubmitted = true;
            submitBtn.disabled = true;
        }
    });
});
//Eof Tambah User

// Modal delete user
document.addEventListener('DOMContentLoaded', function() {
    const deleteUserModal = document.getElementById('deleteUserModal');
    const deleteUserIdInput = document.getElementById('deleteUserId');
    const deleteMessage = document.getElementById('deleteMessage');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteUserForm = document.getElementById('deleteUserForm');

    deleteUserModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-id');

        // Set the user ID in the hidden input
        deleteUserIdInput.value = userId;

        // Reset the delete message and disable the delete button
        deleteMessage.textContent = 'Apakah Anda yakin ingin menghapus user ini?';
        confirmDeleteBtn.disabled = false;
        xhr.send('id=' + encodeURIComponent(userId) + '&csrf_token=<?php echo $_SESSION['csrf_token']; ?>');
    });

    // Prevent double submit
    deleteUserForm.addEventListener('submit', function(event) {
        confirmDeleteBtn.disabled = true; // Disable the button to prevent double submits
    });
});

// JavaScript for Edit User Modal
document.addEventListener('DOMContentLoaded', function() {
    const editFullnameInput = document.getElementById('editFullname');
    const editEmailInput = document.getElementById('editEmail');
    const editPhoneInput = document.getElementById('editPhone');
    const editSubmitBtn = document.getElementById('editSubmitBtn');
    let formSubmitted = false;

    let isEmailAvailable = true;

    function validateEditForm() {
        const fullname = editFullnameInput.value;
        const email = editEmailInput.value;
        const phone = editPhoneInput.value;

        const isFullnameValid = /^[a-zA-Z0-9\s_]+$/.test(fullname) && fullname.length <= 50;
        const isPhoneValid = /^[0-9]+$/.test(phone) && phone.length <= 14;
        const isEmailValid = email.length > 0; // Basic validation for non-empty email

        document.getElementById('editFullnameFeedback').textContent = isFullnameValid ? '' : 'Full Name hanya boleh berisi huruf, angka, spasi, dan _.';
        document.getElementById('editPhoneFeedback').textContent = isPhoneValid ? '' : 'Nomor telepon harus angka dan maksimal 14 karakter.';

        // Disable submit button if validation fails
        editSubmitBtn.disabled = !(isFullnameValid && isPhoneValid && isEmailValid && isEmailAvailable);
    }

    editFullnameInput.addEventListener('input', validateEditForm);
    editPhoneInput.addEventListener('input', validateEditForm);

    // AJAX for validating email
    editEmailInput.addEventListener('blur', function() {
        const email = this.value;
        const feedback = document.getElementById('editEmailFeedback');

        if (email === '') {
            feedback.textContent = 'Email tidak boleh kosong.';
            isEmailAvailable = false;
            validateEditForm();
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../check_email.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                if (xhr.responseText === 'taken') {
                    feedback.textContent = 'Email sudah digunakan.';
                    feedback.classList.add('error');
                    feedback.classList.remove('success');
                    isEmailAvailable = false;
                } else {
                    feedback.textContent = 'Email tersedia.';
                    feedback.classList.add('success');
                    feedback.classList.remove('error');
                    isEmailAvailable = true;
                }
                validateEditForm();
            }
        };
        xhr.send('email=' + encodeURIComponent(email));
    });

    // Prevent double submit
    document.getElementById('editUserForm').addEventListener('submit', function(event) {
        if (formSubmitted) {
            event.preventDefault();
        } else {
            formSubmitted = true;
            editSubmitBtn.disabled = true;
        }
    });

    // Fill in data when modal is opened
    document.getElementById('editUserModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-id');
        const username = button.getAttribute('data-username');
        const fullname = button.getAttribute('data-fullname');
        const email = button.getAttribute('data-email');
        const phone = button.getAttribute('data-phone');
        const level = button.getAttribute('data-level');
		const idCabang = button.getAttribute('data-cabang');

        // Set form values
        document.getElementById('editUserId').value = userId;
        document.getElementById('editUsername').value = username;
        document.getElementById('editFullname').value = fullname;
        document.getElementById('editEmail').value = email;
        document.getElementById('editPhone').value = phone;
        document.getElementById('editLevel').value = level;
		document.getElementById('editCabang').value = idCabang;

        // Reset validation and submit button
        document.getElementById('editFullnameFeedback').textContent = '';
        document.getElementById('editEmailFeedback').textContent = '';
        document.getElementById('editPhoneFeedback').textContent = '';
        editSubmitBtn.disabled = false;
    });
});
</script>

<?php include '_footer.php'; ?>

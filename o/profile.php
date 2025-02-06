<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error_message'] = "Anda perlu login untuk mengakses halaman ini."; // Simpan pesan error dalam session
    header("Location: ../login.php"); // Redirect ke halaman login jika belum login
    exit;
}

// Cek apakah level pengguna adalah "admin"
if ($_SESSION['level'] !== 'admin') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini."; // Simpan pesan error dalam session
    header("Location: ../login.php"); // Redirect ke halaman login jika bukan admin
    exit;
}

// Cek apakah sesi sudah timeout (tidak ada aktivitas selama 30 menit)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset(); // Hapus semua variabel sesi
    session_destroy(); // Hancurkan sesi
    session_start(); // Mulai sesi baru untuk menyimpan pesan timeout
    $_SESSION['error_message'] = "Sesi Anda telah berakhir. Silakan login kembali."; // Simpan pesan timeout dalam session
    header("Location: ../login.php"); // Redirect ke halaman login
    exit;
}

// Membuat CSRF token jika belum ada
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include '_header.php'; 
?>

<!-- Pesan Sukses/Error -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<div class="row">

  <div class="col-md-12 grid-margin-kurasi">
	<div class="row">
	  <div class="col-12 col-xl-8 mb-4 mb-xl-0">
		<h3 class="font-weight-bold">My Profile</h3>
		<h6 class="font-weight-normal mb-0">Informasi profile dan edit profile Anda.</h6>
	  </div>
	</div>
  </div>

<div class="container mt-4">
    <div class="row">
        <!-- Bagian Kiri: Informasi User -->
        <div class="col-md-4 mb-4">
            <div class="card text-center" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
                <div class="card-body">
                    <img src="<?php echo isset($profileImage) ? htmlspecialchars($profileImage) : '../assets/images/faces/no-image.png'; ?>" 
                         class="rounded-circle mt-3" 
                         style="border: 3px solid #fff; width: 125px; height: 125px; object-fit: cover;" 
                         alt="User profile picture">
                    <h3 class="mt-3"><?php echo htmlspecialchars($_SESSION['fullname'] ?? ''); ?></h3>
                    <p class="text-muted"><?php echo htmlspecialchars($_SESSION['level'] ?? ''); ?></p>
                    <ul class="list-group list-group-flush text-start">
                        <li class="list-group-item">Username: <span class="text-dark"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span></li>
                        <li class="list-group-item">Email: <span class="text-dark"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></span></li>
                        <li class="list-group-item">Phone: <span class="text-dark"><?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?></span></li>
                        <li class="list-group-item">About: <span class="text-dark"><?php echo htmlspecialchars($_SESSION['about'] ?? 'Not provided'); ?></span></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Bagian Kanan: Form Edit Profil -->
        <div class="col-md-8 mb-4">
            <div class="card" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
                <div class="card-body">
                    <h4 class="card-title">Edit Profile</h4>

                    <form method="post" action="profile_update.php" enctype="multipart/form-data" id="profileForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fullname">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" maxlength="100" id="fullname" name="fullname" value="<?php echo htmlspecialchars($_SESSION['fullname'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Full Name is required and must not contain special characters.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Phone & Email -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" maxlength="14" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>" pattern="[0-9]+" minlength="10" required>
                                    <div class="invalid-feedback">Please enter a valid phone number with at least 10 digits.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" maxlength="100" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                            </div>
                        </div>

                        <!-- About & Picture -->
                        <div class="form-group">
                            <label for="about">About (Optional)</label>
                            <textarea class="form-control" id="about" name="about" rows="3"><?php echo htmlspecialchars($_SESSION['about'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="picture">Profile Picture (Optional)</label>
                            <input type="file" class="form-control-file" id="picture" name="picture" accept=".jpg,.jpeg,.png">
                            <small class="form-text text-muted">Only .jpg, .jpeg, and .png files are allowed. Max size 2MB.</small>
                            <div class="invalid-feedback">Invalid file format or size. Max size 2MB.</div>
                        </div>

                        <!-- Password -->
                        <div class="form-group">
                            <label for="password">New Password (Optional)</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="6">
                            <div class="invalid-feedback">Password must be at least 6 characters long.</div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-teal mt-2">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include '_footer.php'; ?>

<!-- Client-side validation -->
<script>
// Mencegah karakter khusus
function preventSpecialChars(input) {
    input.addEventListener('input', function() {
        const regex = /[^a-zA-Z0-9\s]/g;  // Hanya huruf, angka, dan spasi
        this.value = this.value.replace(regex, '');
    });
}

// Terapkan pada field yang dibutuhkan
preventSpecialChars(document.getElementById('fullname'));
preventSpecialChars(document.getElementById('phone'));
preventSpecialChars(document.getElementById('about'));

// Validasi form saat submit
document.getElementById('profileForm').addEventListener('submit', function(event) {
    let valid = true;
    const specialCharPattern = /[^a-zA-Z0-9\s]/;

    // Validasi Full Name
    const fullname = document.getElementById('fullname');
    if (fullname.value.trim() === "" || specialCharPattern.test(fullname.value)) {
        fullname.classList.add("is-invalid");
        valid = false;
    } else {
        fullname.classList.remove("is-invalid");
    }

    // Validasi Email
    const email = document.getElementById('email');
    if (!email.value.includes("@") || !email.value.includes(".")) {
        email.classList.add("is-invalid");
        valid = false;
    } else {
        email.classList.remove("is-invalid");
    }

    // Validasi Phone
    const phone = document.getElementById('phone');
    if (phone.value.length < 10 || !/^[0-9]+$/.test(phone.value)) {
        phone.classList.add("is-invalid");
        valid = false;
    } else {
        phone.classList.remove("is-invalid");
    }

    // Validasi Password
    const password = document.getElementById('password');
    if (password.value && password.value.length < 6) {
        password.classList.add("is-invalid");
        valid = false;
    } else {
        password.classList.remove("is-invalid");
    }

    // Validasi Profile Picture
    const pictureInput = document.getElementById('picture');
    const file = pictureInput.files[0];
    if (file) {
        const allowedExtensions = /(\.jpg|\.jpeg|\.png)$/i;
        const maxSize = 2 * 1024 * 1024; // 2MB
        if (!allowedExtensions.exec(file.name) || file.size > maxSize) {
            pictureInput.classList.add("is-invalid");
            valid = false;
        } else {
            pictureInput.classList.remove("is-invalid");
        }
    }

    if (!valid) {
        event.preventDefault(); // Mencegah submit jika ada validasi yang gagal
    }
});
</script>

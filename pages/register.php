<?php
require_once 'includes/template.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/dashboard');
}

// Handle registration form submission
if (isPost()) {
    $username = trim(getPost('username'));
    $password = getPost('password');
    $confirmPassword = getPost('confirm_password');
    
    // Validation
    $errors = [];
    
    if (empty($username) || empty($password)) {
        $errors[] = 'Username dan password wajib diisi!';
    }
    
    if (strlen($username) < 3) {
        $errors[] = 'Username minimal 3 karakter!';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter!';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Password dan konfirmasi password tidak cocok!';
    }
    
    if (empty($errors)) {
        if (registerUser($username, $password)) {
            setFlashMessage('Pendaftaran berhasil! Silakan login dengan akun baru Anda.', 'success');
            redirect('/login');
        } else {
            setFlashMessage('Username sudah digunakan! Silakan pilih username lain.', 'error');
        }
    } else {
        foreach ($errors as $error) {
            setFlashMessage($error, 'error');
            break; // Show only first error
        }
    }
}

renderHeader('Daftar Akun - Aplikasi Akuntansi');
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-header text-center">
                <h4><i class="fas fa-user-plus me-2"></i>Daftar Akun Baru</h4>
            </div>
            <div class="card-body">
                <form method="POST" id="registerForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Masukkan username (minimal 3 karakter)" 
                               value="<?= escape(getPost('username')) ?>" required>
                        <div class="form-text">Username harus minimal 3 karakter dan belum digunakan</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Masukkan password (minimal 6 karakter)" required>
                        <div class="form-text">Password harus minimal 6 karakter</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Masukkan ulang password" required>
                        <div class="form-text">Pastikan password sama dengan yang di atas</div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-plus me-1"></i>Daftar Sekarang
                        </button>
                    </div>
                </form>
                
                <hr>
                <div class="text-center">
                    <p class="mb-2">Sudah punya akun?</p>
                    <a href="/login" class="btn btn-outline-primary">
                        <i class="fas fa-sign-in-alt me-1"></i>Login di sini
                    </a>
                </div>
                
                <hr>
                <div class="text-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Catatan:</strong> Akun baru akan memiliki akses terbatas. 
                        Hubungi admin untuk mendapatkan akses penuh.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validasi client-side untuk memberikan feedback real-time
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (username.length < 3) {
        alert('Username harus minimal 3 karakter!');
        e.preventDefault();
        return;
    }
    
    if (password.length < 6) {
        alert('Password harus minimal 6 karakter!');
        e.preventDefault();
        return;
    }
    
    if (password !== confirmPassword) {
        alert('Password dan konfirmasi password tidak cocok!');
        e.preventDefault();
        return;
    }
});

// Real-time validation untuk konfirmasi password
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.classList.add('is-invalid');
        if (!document.querySelector('.password-mismatch-feedback')) {
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback password-mismatch-feedback';
            feedback.textContent = 'Password tidak cocok';
            this.parentNode.appendChild(feedback);
        }
    } else {
        this.classList.remove('is-invalid');
        const feedback = document.querySelector('.password-mismatch-feedback');
        if (feedback) {
            feedback.remove();
        }
    }
});
</script>

<?php renderFooter(); ?>
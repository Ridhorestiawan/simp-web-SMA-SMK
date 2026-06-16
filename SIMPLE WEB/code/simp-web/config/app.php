<?php

define('BASE_URL', 'http://localhost/simp-web');

define('ROLE_ADMIN', 'admin');
define('ROLE_GURU', 'guru');
define('ROLE_SISWA', 'siswa');

define('UPLOAD_PATH_MATERI', __DIR__ . '/../assets/uploads/materi/');
define('UPLOAD_PATH_TUGAS', __DIR__ . '/../assets/uploads/tugas/');

define('MAX_FILE_SIZE', 50 * 1024 * 1024);

define('ALLOWED_FILE_TYPES_MATERI', ['pdf', 'ppt', 'pptx', 'mp4', 'doc', 'docx', 'xls', 'xlsx']);
define('ALLOWED_FILE_TYPES_TUGAS', ['pdf', 'doc', 'docx', 'zip', 'rar', 'jpg', 'jpeg', 'png']);

define('SESSION_TIMEOUT', 3600);

date_default_timezone_set('Asia/Jakarta');

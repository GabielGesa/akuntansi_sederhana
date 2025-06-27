<?php
// Database configuration
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    private function connect() {
        $database_url = getenv('DATABASE_URL');
        
        if (!$database_url) {
            throw new Exception('DATABASE_URL environment variable not set');
        }
        
        try {
            // Parse the DATABASE_URL for PostgreSQL
            $url = parse_url($database_url);
            $host = $url['host'];
            $port = $url['port'] ?? 5432;
            $dbname = ltrim($url['path'], '/');
            $username = $url['user'];
            $password = $url['pass'];
            
            // Extract SSL mode from query string
            $query = $url['query'] ?? '';
            parse_str($query, $params);
            $sslmode = $params['sslmode'] ?? 'prefer';
            
            // Build DSN
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";
            
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception('Query failed: ' . $e->getMessage());
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

function initializeDatabase() {
    $db = Database::getInstance();
    
    // Create tables if they don't exist
    $tables = [
        'users' => "
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(80) UNIQUE NOT NULL,
                password_hash VARCHAR(256) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'accounts' => "
            CREATE TABLE IF NOT EXISTS accounts (
                id SERIAL PRIMARY KEY,
                code VARCHAR(10) UNIQUE NOT NULL,
                name VARCHAR(200) NOT NULL,
                account_type VARCHAR(50) NOT NULL,
                normal_balance VARCHAR(10) NOT NULL,
                balance DECIMAL(15,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'journal_entries' => "
            CREATE TABLE IF NOT EXISTS journal_entries (
                id SERIAL PRIMARY KEY,
                date DATE NOT NULL,
                description TEXT NOT NULL,
                debit_account_id INTEGER REFERENCES accounts(id),
                credit_account_id INTEGER REFERENCES accounts(id),
                amount DECIMAL(15,2) NOT NULL,
                entry_type VARCHAR(20) NOT NULL DEFAULT 'general',
                created_by INTEGER REFERENCES users(id),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        "
    ];
    
    foreach ($tables as $table => $sql) {
        try {
            $db->execute($sql);
        } catch (Exception $e) {
            error_log("Error creating table $table: " . $e->getMessage());
        }
    }
    
    // Initialize default data
    initializeDefaultData();
}

function initializeDefaultData() {
    $db = Database::getInstance();
    
    // Check if admin user exists
    $admin = $db->fetchOne("SELECT id FROM users WHERE username = ?", ['admin']);
    if (!$admin) {
        // Create default users
        $db->execute("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)", 
            ['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
        $db->execute("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)", 
            ['user', password_hash('user123', PASSWORD_DEFAULT), 'user']);
    }
    
    // Check if accounts exist
    $accounts = $db->fetchAll("SELECT id FROM accounts LIMIT 1");
    if (empty($accounts)) {
        // Insert default chart of accounts
        $defaultAccounts = [
            // Aset (Assets)
            ['1001', 'Kas', 'Aset', 'debit'],
            ['1002', 'Bank', 'Aset', 'debit'],
            ['1003', 'Piutang Usaha', 'Aset', 'debit'],
            ['1004', 'Piutang Lain-lain', 'Aset', 'debit'],
            ['1005', 'Persediaan', 'Aset', 'debit'],
            ['1006', 'Perlengkapan', 'Aset', 'debit'],
            ['1007', 'Biaya Dibayar Dimuka', 'Aset', 'debit'],
            ['1101', 'Tanah', 'Aset', 'debit'],
            ['1102', 'Bangunan', 'Aset', 'debit'],
            ['1103', 'Akumulasi Penyusutan Bangunan', 'Aset', 'credit'],
            ['1104', 'Peralatan', 'Aset', 'debit'],
            ['1105', 'Akumulasi Penyusutan Peralatan', 'Aset', 'credit'],
            ['1106', 'Kendaraan', 'Aset', 'debit'],
            ['1107', 'Akumulasi Penyusutan Kendaraan', 'Aset', 'credit'],
            
            // Liabilitas (Liabilities)
            ['2001', 'Utang Usaha', 'Liabilitas', 'credit'],
            ['2002', 'Utang Lain-lain', 'Liabilitas', 'credit'],
            ['2003', 'Utang Bank', 'Liabilitas', 'credit'],
            ['2004', 'Utang Pajak', 'Liabilitas', 'credit'],
            ['2005', 'Utang Gaji', 'Liabilitas', 'credit'],
            ['2006', 'Biaya Yang Masih Harus Dibayar', 'Liabilitas', 'credit'],
            
            // Ekuitas (Equity)
            ['3001', 'Modal Pemilik', 'Ekuitas', 'credit'],
            ['3002', 'Modal Tambahan', 'Ekuitas', 'credit'],
            ['3003', 'Laba Ditahan', 'Ekuitas', 'credit'],
            ['3004', 'Prive', 'Ekuitas', 'debit'],
            
            // Pendapatan (Revenue)
            ['4001', 'Pendapatan Jasa', 'Pendapatan', 'credit'],
            ['4002', 'Pendapatan Bunga', 'Pendapatan', 'credit'],
            ['4003', 'Pendapatan Lain-lain', 'Pendapatan', 'credit'],
            
            // Beban (Expenses)
            ['5001', 'Beban Gaji', 'Beban', 'debit'],
            ['5002', 'Beban Listrik', 'Beban', 'debit'],
            ['5003', 'Beban Telepon', 'Beban', 'debit'],
            ['5004', 'Beban Sewa', 'Beban', 'debit'],
            ['5005', 'Beban Perlengkapan', 'Beban', 'debit'],
            ['5006', 'Beban Penyusutan Bangunan', 'Beban', 'debit'],
            ['5007', 'Beban Penyusutan Peralatan', 'Beban', 'debit'],
            ['5008', 'Beban Penyusutan Kendaraan', 'Beban', 'debit'],
            ['5009', 'Beban Bunga', 'Beban', 'debit'],
            ['5010', 'Beban Pajak', 'Beban', 'debit'],
            ['5011', 'Beban Lain-lain', 'Beban', 'debit'],
        ];
        
        foreach ($defaultAccounts as $account) {
            $db->execute(
                "INSERT INTO accounts (code, name, account_type, normal_balance) VALUES (?, ?, ?, ?)",
                $account
            );
        }
    }
}
?>
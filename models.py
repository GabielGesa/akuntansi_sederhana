from flask_sqlalchemy import SQLAlchemy
from werkzeug.security import generate_password_hash, check_password_hash
from datetime import datetime
from decimal import Decimal
import os

# Initialize SQLAlchemy
db = SQLAlchemy()

class User(db.Model):
    __tablename__ = 'users'
    
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    password_hash = db.Column(db.String(256), nullable=False)
    role = db.Column(db.String(20), nullable=False, default='user')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def set_password(self, password):
        self.password_hash = generate_password_hash(password)
    
    def check_password(self, password):
        return check_password_hash(self.password_hash, password)
    
    def __repr__(self):
        return f'<User {self.username}>'

class Account(db.Model):
    __tablename__ = 'accounts'
    
    id = db.Column(db.Integer, primary_key=True)
    code = db.Column(db.String(10), unique=True, nullable=False)
    name = db.Column(db.String(200), nullable=False)
    account_type = db.Column(db.String(50), nullable=False)
    normal_balance = db.Column(db.String(10), nullable=False)  # 'debit' or 'credit'
    balance = db.Column(db.Numeric(15, 2), default=0)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Relationships
    journal_entries_debit = db.relationship('JournalEntry', foreign_keys='JournalEntry.debit_account_id', backref='debit_account')
    journal_entries_credit = db.relationship('JournalEntry', foreign_keys='JournalEntry.credit_account_id', backref='credit_account')
    
    def __repr__(self):
        return f'<Account {self.code}: {self.name}>'

class JournalEntry(db.Model):
    __tablename__ = 'journal_entries'
    
    id = db.Column(db.Integer, primary_key=True)
    date = db.Column(db.Date, nullable=False)
    description = db.Column(db.Text, nullable=False)
    debit_account_id = db.Column(db.Integer, db.ForeignKey('accounts.id'), nullable=False)
    credit_account_id = db.Column(db.Integer, db.ForeignKey('accounts.id'), nullable=False)
    amount = db.Column(db.Numeric(15, 2), nullable=False)
    entry_type = db.Column(db.String(20), nullable=False, default='general')  # 'general', 'adjusting', 'closing'
    created_by = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Relationships
    creator = db.relationship('User', backref='journal_entries')
    
    def __repr__(self):
        return f'<JournalEntry {self.id}: {self.description}>'

def init_default_data():
    """Initialize default users and chart of accounts"""
    
    # Create default users if they don't exist
    if not User.query.filter_by(username='admin').first():
        admin = User()
        admin.username = 'admin'
        admin.role = 'admin'
        admin.set_password('admin123')
        db.session.add(admin)
    
    if not User.query.filter_by(username='user').first():
        user = User()
        user.username = 'user'
        user.role = 'user'
        user.set_password('user123')
        db.session.add(user)
    
    # Create default chart of accounts if they don't exist
    default_accounts = [
        # Assets (1000-1999)
        ('1001', 'Kas', 'Aset', 'debit'),
        ('1002', 'Bank', 'Aset', 'debit'),
        ('1003', 'Piutang Dagang', 'Aset', 'debit'),
        ('1004', 'Persediaan Barang', 'Aset', 'debit'),
        ('1005', 'Perlengkapan Kantor', 'Aset', 'debit'),
        ('1006', 'Asuransi Dibayar Dimuka', 'Aset', 'debit'),
        ('1007', 'Sewa Dibayar Dimuka', 'Aset', 'debit'),
        ('1101', 'Peralatan Kantor', 'Aset', 'debit'),
        ('1102', 'Akumulasi Penyusutan Peralatan Kantor', 'Aset', 'credit'),
        ('1201', 'Kendaraan', 'Aset', 'debit'),
        ('1202', 'Akumulasi Penyusutan Kendaraan', 'Aset', 'credit'),
        ('1301', 'Gedung', 'Aset', 'debit'),
        ('1302', 'Akumulasi Penyusutan Gedung', 'Aset', 'credit'),
        
        # Liabilities (2000-2999)
        ('2001', 'Utang Dagang', 'Liabilitas', 'credit'),
        ('2002', 'Utang Bank', 'Liabilitas', 'credit'),
        ('2003', 'Utang Gaji', 'Liabilitas', 'credit'),
        ('2004', 'Utang Pajak', 'Liabilitas', 'credit'),
        ('2005', 'Utang Listrik', 'Liabilitas', 'credit'),
        ('2006', 'Utang Telepon', 'Liabilitas', 'credit'),
        ('2007', 'Pendapatan Diterima Dimuka', 'Liabilitas', 'credit'),
        
        # Equity (3000-3999)
        ('3001', 'Modal Pemilik', 'Ekuitas', 'credit'),
        ('3002', 'Prive Pemilik', 'Ekuitas', 'debit'),
        ('3003', 'Laba Ditahan', 'Ekuitas', 'credit'),
        
        # Revenue (4000-4999)
        ('4001', 'Pendapatan Penjualan', 'Pendapatan', 'credit'),
        ('4002', 'Pendapatan Jasa', 'Pendapatan', 'credit'),
        ('4003', 'Pendapatan Bunga', 'Pendapatan', 'credit'),
        ('4004', 'Pendapatan Sewa', 'Pendapatan', 'credit'),
        ('4005', 'Pendapatan Lain-lain', 'Pendapatan', 'credit'),
        
        # Expenses (5000-5999)
        ('5001', 'Beban Gaji', 'Beban', 'debit'),
        ('5002', 'Beban Sewa', 'Beban', 'debit'),
        ('5003', 'Beban Listrik', 'Beban', 'debit'),
        ('5004', 'Beban Telepon', 'Beban', 'debit'),
        ('5005', 'Beban Perlengkapan', 'Beban', 'debit'),
        ('5006', 'Beban Penyusutan Peralatan', 'Beban', 'debit'),
        ('5007', 'Beban Penyusutan Kendaraan', 'Beban', 'debit'),
        ('5008', 'Beban Penyusutan Gedung', 'Beban', 'debit'),
        ('5009', 'Beban Asuransi', 'Beban', 'debit'),
        ('5010', 'Beban Transportasi', 'Beban', 'debit'),
        ('5011', 'Beban Promosi', 'Beban', 'debit'),
        ('5012', 'Beban Administrasi', 'Beban', 'debit'),
        ('5013', 'Beban Lain-lain', 'Beban', 'debit'),
    ]
    
    for code, name, account_type, normal_balance in default_accounts:
        if not Account.query.filter_by(code=code).first():
            account = Account()
            account.code = code
            account.name = name
            account.account_type = account_type
            account.normal_balance = normal_balance
            account.balance = Decimal('0.00')
            db.session.add(account)
    
    try:
        db.session.commit()
        print("Default data initialized successfully")
    except Exception as e:
        db.session.rollback()
        print(f"Error initializing default data: {e}")
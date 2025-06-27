import os
import logging
from datetime import datetime, date
from decimal import Decimal, ROUND_HALF_UP
from flask import Flask, render_template, request, redirect, url_for, session, flash, jsonify
from werkzeug.security import generate_password_hash, check_password_hash
from sqlalchemy import func, desc, and_, or_
from models import db, User, Account, JournalEntry, init_default_data

# Configure logging
logging.basicConfig(level=logging.DEBUG)

# Create Flask app
app = Flask(__name__)
app.secret_key = os.environ.get("SESSION_SECRET", "dev-secret-key-change-in-production")

# Configure database
database_url = os.environ.get('DATABASE_URL')
if not database_url:
    raise RuntimeError("DATABASE_URL environment variable is not set")

app.config['SQLALCHEMY_DATABASE_URI'] = database_url
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.config['SQLALCHEMY_ENGINE_OPTIONS'] = {
    'pool_recycle': 300,
    'pool_pre_ping': True,
}

# Initialize database
db.init_app(app)

# Create tables and initialize default data
with app.app_context():
    db.create_all()
    init_default_data()

# Utility functions
def format_currency(amount):
    """Format amount as Indonesian Rupiah"""
    if amount is None:
        return "Rp 0"
    
    # Convert to Decimal for precise formatting
    if isinstance(amount, (int, float)):
        amount = Decimal(str(amount))
    
    # Round to 2 decimal places
    amount = amount.quantize(Decimal('0.01'), rounding=ROUND_HALF_UP)
    
    # Format with thousand separators
    formatted = "{:,.2f}".format(float(amount))
    return f"Rp {formatted}"

def get_account_balance(account_id):
    """Get current balance of an account"""
    account = Account.query.get(account_id)
    if not account:
        return Decimal('0')
    
    # Calculate balance from journal entries
    debit_entries = db.session.query(func.coalesce(func.sum(JournalEntry.amount), 0)).filter_by(debit_account_id=account_id).scalar()
    credit_entries = db.session.query(func.coalesce(func.sum(JournalEntry.amount), 0)).filter_by(credit_account_id=account_id).scalar()
    
    if account.normal_balance == 'debit':
        return debit_entries - credit_entries
    else:
        return credit_entries - debit_entries

def update_account_balances():
    """Update all account balances"""
    accounts = Account.query.all()
    for account in accounts:
        account.balance = get_account_balance(account.id)
    db.session.commit()

def calculate_trial_balance():
    """Calculate trial balance"""
    accounts = Account.query.order_by(Account.code).all()
    trial_balance = []
    total_debit = Decimal('0')
    total_credit = Decimal('0')
    
    for account in accounts:
        balance = get_account_balance(account.id)
        
        if balance != 0:
            if account.normal_balance == 'debit' and balance > 0:
                debit_amount = balance
                credit_amount = Decimal('0')
            elif account.normal_balance == 'debit' and balance < 0:
                debit_amount = Decimal('0')
                credit_amount = abs(balance)
            elif account.normal_balance == 'credit' and balance > 0:
                debit_amount = Decimal('0')
                credit_amount = balance
            else:
                debit_amount = abs(balance)
                credit_amount = Decimal('0')
            
            trial_balance.append({
                'code': account.code,
                'name': account.name,
                'debit': debit_amount,
                'credit': credit_amount
            })
            
            total_debit += debit_amount
            total_credit += credit_amount
    
    return trial_balance, total_debit, total_credit

# Authentication decorators
def login_required(f):
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            flash('Silakan login terlebih dahulu', 'warning')
            return redirect(url_for('login'))
        return f(*args, **kwargs)
    decorated_function.__name__ = f.__name__
    return decorated_function

def admin_required(f):
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            flash('Silakan login terlebih dahulu', 'warning')
            return redirect(url_for('login'))
        
        user = User.query.get(session['user_id'])
        if not user or user.role != 'admin':
            flash('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.', 'error')
            return redirect(url_for('dashboard'))
        
        return f(*args, **kwargs)
    decorated_function.__name__ = f.__name__
    return decorated_function

# Routes
@app.route('/')
def index():
    if 'user_id' in session:
        return redirect(url_for('dashboard'))
    return redirect(url_for('login'))

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        
        user = User.query.filter_by(username=username).first()
        
        if user and user.check_password(password):
            session['user_id'] = user.id
            session['username'] = user.username
            session['role'] = user.role
            flash(f'Selamat datang, {username}!', 'success')
            return redirect(url_for('dashboard'))
        else:
            flash('Username atau password salah!', 'error')
    
    return render_template('login.html')

@app.route('/logout')
def logout():
    session.clear()
    flash('Anda telah logout', 'info')
    return redirect(url_for('login'))

@app.route('/dashboard')
@login_required
def dashboard():
    # Get summary statistics
    total_accounts = Account.query.count()
    total_entries = JournalEntry.query.count()
    
    # Get recent journal entries
    recent_entries = JournalEntry.query.order_by(desc(JournalEntry.created_at)).limit(5).all()
    
    # Calculate total assets, liabilities, and equity
    asset_accounts = Account.query.filter_by(account_type='Aset').all()
    liability_accounts = Account.query.filter_by(account_type='Liabilitas').all()
    equity_accounts = Account.query.filter_by(account_type='Ekuitas').all()
    
    total_assets = sum(get_account_balance(acc.id) for acc in asset_accounts)
    total_liabilities = sum(get_account_balance(acc.id) for acc in liability_accounts)
    total_equity = sum(get_account_balance(acc.id) for acc in equity_accounts)
    
    return render_template('dashboard.html',
                         total_accounts=total_accounts,
                         total_entries=total_entries,
                         recent_entries=recent_entries,
                         total_assets=total_assets,
                         total_liabilities=total_liabilities,
                         total_equity=total_equity,
                         format_currency=format_currency)

@app.route('/chart-of-accounts')
@login_required
def chart_of_accounts_view():
    accounts = Account.query.order_by(Account.code).all()
    
    # Update balances
    for account in accounts:
        account.current_balance = get_account_balance(account.id)
    
    return render_template('chart_of_accounts.html', accounts=accounts, format_currency=format_currency)

@app.route('/add-account', methods=['GET', 'POST'])
@admin_required
def add_account():
    if request.method == 'POST':
        code = request.form['code']
        name = request.form['name']
        account_type = request.form['account_type']
        normal_balance = request.form['normal_balance']
        
        # Check if account code already exists
        existing_account = Account.query.filter_by(code=code).first()
        if existing_account:
            flash('Kode akun sudah ada!', 'error')
            return render_template('add_account.html')
        
        # Create new account
        new_account = Account()
        new_account.code = code
        new_account.name = name
        new_account.account_type = account_type
        new_account.normal_balance = normal_balance
        
        try:
            db.session.add(new_account)
            db.session.commit()
            flash('Akun berhasil ditambahkan!', 'success')
            return redirect(url_for('chart_of_accounts_view'))
        except Exception as e:
            db.session.rollback()
            flash(f'Terjadi kesalahan: {str(e)}', 'error')
    
    return render_template('add_account.html')

@app.route('/journal-entry', methods=['GET', 'POST'])
@login_required
def journal_entry():
    if request.method == 'POST':
        entry_date = datetime.strptime(request.form['date'], '%Y-%m-%d').date()
        description = request.form['description']
        debit_account_id = int(request.form['debit_account'])
        credit_account_id = int(request.form['credit_account'])
        amount = Decimal(request.form['amount'])
        
        # Create journal entry
        new_entry = JournalEntry()
        new_entry.date = entry_date
        new_entry.description = description
        new_entry.debit_account_id = debit_account_id
        new_entry.credit_account_id = credit_account_id
        new_entry.amount = amount
        new_entry.entry_type = 'general'
        new_entry.created_by = session['user_id']
        
        try:
            db.session.add(new_entry)
            db.session.commit()
            flash('Jurnal berhasil ditambahkan!', 'success')
            return redirect(url_for('journal_entry'))
        except Exception as e:
            db.session.rollback()
            flash(f'Terjadi kesalahan: {str(e)}', 'error')
    
    accounts = Account.query.order_by(Account.code).all()
    entries = JournalEntry.query.filter_by(entry_type='general').order_by(desc(JournalEntry.date)).all()
    
    return render_template('journal_entry.html', accounts=accounts, entries=entries, format_currency=format_currency)

@app.route('/adjusting-entry', methods=['GET', 'POST'])
@login_required
def adjusting_entry():
    if request.method == 'POST':
        entry_date = datetime.strptime(request.form['date'], '%Y-%m-%d').date()
        description = request.form['description']
        debit_account_id = int(request.form['debit_account'])
        credit_account_id = int(request.form['credit_account'])
        amount = Decimal(request.form['amount'])
        
        new_entry = JournalEntry()
        new_entry.date = entry_date
        new_entry.description = description
        new_entry.debit_account_id = debit_account_id
        new_entry.credit_account_id = credit_account_id
        new_entry.amount = amount
        new_entry.entry_type = 'adjusting'
        new_entry.created_by = session['user_id']
        
        try:
            db.session.add(new_entry)
            db.session.commit()
            flash('Jurnal penyesuaian berhasil ditambahkan!', 'success')
            return redirect(url_for('adjusting_entry'))
        except Exception as e:
            db.session.rollback()
            flash(f'Terjadi kesalahan: {str(e)}', 'error')
    
    accounts = Account.query.order_by(Account.code).all()
    entries = JournalEntry.query.filter_by(entry_type='adjusting').order_by(desc(JournalEntry.date)).all()
    
    return render_template('adjusting_entry.html', accounts=accounts, entries=entries, format_currency=format_currency)

@app.route('/closing-entry', methods=['GET', 'POST'])
@login_required
def closing_entry():
    if request.method == 'POST':
        entry_date = datetime.strptime(request.form['date'], '%Y-%m-%d').date()
        description = request.form['description']
        debit_account_id = int(request.form['debit_account'])
        credit_account_id = int(request.form['credit_account'])
        amount = Decimal(request.form['amount'])
        
        new_entry = JournalEntry()
        new_entry.date = entry_date
        new_entry.description = description
        new_entry.debit_account_id = debit_account_id
        new_entry.credit_account_id = credit_account_id
        new_entry.amount = amount
        new_entry.entry_type = 'closing'
        new_entry.created_by = session['user_id']
        
        try:
            db.session.add(new_entry)
            db.session.commit()
            flash('Jurnal penutup berhasil ditambahkan!', 'success')
            return redirect(url_for('closing_entry'))
        except Exception as e:
            db.session.rollback()
            flash(f'Terjadi kesalahan: {str(e)}', 'error')
    
    accounts = Account.query.order_by(Account.code).all()
    entries = JournalEntry.query.filter_by(entry_type='closing').order_by(desc(JournalEntry.date)).all()
    
    return render_template('closing_entry.html', accounts=accounts, entries=entries, format_currency=format_currency)

@app.route('/general-ledger')
@login_required
def general_ledger():
    account_id = request.args.get('account_id')
    
    if account_id:
        account = Account.query.get(account_id)
        if account:
            # Get all entries for this account
            debit_entries = JournalEntry.query.filter_by(debit_account_id=account_id).order_by(JournalEntry.date).all()
            credit_entries = JournalEntry.query.filter_by(credit_account_id=account_id).order_by(JournalEntry.date).all()
            
            # Combine and sort entries
            all_entries = []
            for entry in debit_entries:
                all_entries.append({
                    'date': entry.date,
                    'description': entry.description,
                    'debit': entry.amount,
                    'credit': Decimal('0'),
                    'type': entry.entry_type
                })
            
            for entry in credit_entries:
                all_entries.append({
                    'date': entry.date,
                    'description': entry.description,
                    'debit': Decimal('0'),
                    'credit': entry.amount,
                    'type': entry.entry_type
                })
            
            # Sort by date
            all_entries.sort(key=lambda x: x['date'])
            
            # Calculate running balance
            balance = Decimal('0')
            for entry in all_entries:
                if account.normal_balance == 'debit':
                    balance += entry['debit'] - entry['credit']
                else:
                    balance += entry['credit'] - entry['debit']
                entry['balance'] = balance
            
            return render_template('general_ledger.html', 
                                 account=account, 
                                 entries=all_entries, 
                                 format_currency=format_currency)
    
    accounts = Account.query.order_by(Account.code).all()
    return render_template('general_ledger.html', accounts=accounts)

@app.route('/trial-balance')
@login_required
def trial_balance():
    trial_balance_data, total_debit, total_credit = calculate_trial_balance()
    
    return render_template('trial_balance.html',
                         trial_balance=trial_balance_data,
                         total_debit=total_debit,
                         total_credit=total_credit,
                         format_currency=format_currency)

@app.route('/income-statement')
@login_required
def income_statement():
    # Get revenue and expense accounts
    revenue_accounts = Account.query.filter_by(account_type='Pendapatan').all()
    expense_accounts = Account.query.filter_by(account_type='Beban').all()
    
    revenues = []
    expenses = []
    total_revenue = Decimal('0')
    total_expenses = Decimal('0')
    
    for account in revenue_accounts:
        balance = get_account_balance(account.id)
        if balance != 0:
            revenues.append({
                'code': account.code,
                'name': account.name,
                'amount': balance
            })
            total_revenue += balance
    
    for account in expense_accounts:
        balance = get_account_balance(account.id)
        if balance != 0:
            expenses.append({
                'code': account.code,
                'name': account.name,
                'amount': balance
            })
            total_expenses += balance
    
    net_income = total_revenue - total_expenses
    
    return render_template('income_statement.html',
                         revenues=revenues,
                         expenses=expenses,
                         total_revenue=total_revenue,
                         total_expenses=total_expenses,
                         net_income=net_income,
                         format_currency=format_currency)

@app.route('/balance-sheet')
@login_required
def balance_sheet():
    # Get accounts by type
    asset_accounts = Account.query.filter_by(account_type='Aset').order_by(Account.code).all()
    liability_accounts = Account.query.filter_by(account_type='Liabilitas').order_by(Account.code).all()
    equity_accounts = Account.query.filter_by(account_type='Ekuitas').order_by(Account.code).all()
    
    assets = []
    liabilities = []
    equity = []
    
    total_assets = Decimal('0')
    total_liabilities = Decimal('0')
    total_equity = Decimal('0')
    
    for account in asset_accounts:
        balance = get_account_balance(account.id)
        if balance != 0:
            assets.append({
                'code': account.code,
                'name': account.name,
                'amount': balance
            })
            total_assets += balance
    
    for account in liability_accounts:
        balance = get_account_balance(account.id)
        if balance != 0:
            liabilities.append({
                'code': account.code,
                'name': account.name,
                'amount': balance
            })
            total_liabilities += balance
    
    for account in equity_accounts:
        balance = get_account_balance(account.id)
        if balance != 0:
            equity.append({
                'code': account.code,
                'name': account.name,
                'amount': balance
            })
            total_equity += balance
    
    return render_template('balance_sheet.html',
                         assets=assets,
                         liabilities=liabilities,
                         equity=equity,
                         total_assets=total_assets,
                         total_liabilities=total_liabilities,
                         total_equity=total_equity,
                         format_currency=format_currency)

@app.route('/equity-statement')
@login_required
def equity_statement():
    # Get equity accounts
    equity_accounts = Account.query.filter_by(account_type='Ekuitas').order_by(Account.code).all()
    
    equity_data = []
    total_equity = Decimal('0')
    
    for account in equity_accounts:
        balance = get_account_balance(account.id)
        if balance != 0:
            equity_data.append({
                'code': account.code,
                'name': account.name,
                'amount': balance
            })
            total_equity += balance
    
    # Get net income from income statement
    revenue_accounts = Account.query.filter_by(account_type='Pendapatan').all()
    expense_accounts = Account.query.filter_by(account_type='Beban').all()
    
    total_revenue = sum(get_account_balance(acc.id) for acc in revenue_accounts)
    total_expenses = sum(get_account_balance(acc.id) for acc in expense_accounts)
    net_income = total_revenue - total_expenses
    
    return render_template('equity_statement.html',
                         equity_data=equity_data,
                         total_equity=total_equity,
                         net_income=net_income,
                         format_currency=format_currency)

@app.route('/search-entries')
@login_required
def search_entries():
    search_query = request.args.get('query', '')
    entry_type = request.args.get('type', '')
    
    entries = JournalEntry.query
    
    if search_query:
        entries = entries.filter(JournalEntry.description.ilike(f'%{search_query}%'))
    
    if entry_type:
        entries = entries.filter_by(entry_type=entry_type)
    
    entries = entries.order_by(desc(JournalEntry.date)).all()
    
    return render_template('search_entries.html', 
                         entries=entries, 
                         search_query=search_query,
                         entry_type=entry_type,
                         format_currency=format_currency)

# Template filters
@app.template_filter('currency')
def currency_filter(amount):
    return format_currency(amount)

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)
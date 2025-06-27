import os
import logging
from datetime import datetime
from decimal import Decimal, ROUND_HALF_UP
from flask import Flask, render_template, request, redirect, url_for, session, flash, jsonify
from werkzeug.security import generate_password_hash, check_password_hash

# Configure logging
logging.basicConfig(level=logging.DEBUG)

app = Flask(__name__)
app.secret_key = os.environ.get("SESSION_SECRET", "dev-secret-key-change-in-production")

# In-memory data storage
users_db = {
    'admin': {
        'password_hash': generate_password_hash('admin123'),
        'role': 'admin',
        'name': 'Administrator'
    },
    'user': {
        'password_hash': generate_password_hash('user123'),
        'role': 'user',
        'name': 'User Akuntansi'
    }
}

# Chart of Accounts structure
chart_of_accounts = {
    '1000': {'name': 'Kas', 'type': 'Harta', 'subtype': 'Harta Lancar', 'balance': Decimal('0')},
    '1100': {'name': 'Bank', 'type': 'Harta', 'subtype': 'Harta Lancar', 'balance': Decimal('0')},
    '1200': {'name': 'Piutang Usaha', 'type': 'Harta', 'subtype': 'Harta Lancar', 'balance': Decimal('0')},
    '1300': {'name': 'Persediaan', 'type': 'Harta', 'subtype': 'Harta Lancar', 'balance': Decimal('0')},
    '1400': {'name': 'Peralatan', 'type': 'Harta', 'subtype': 'Harta Tetap', 'balance': Decimal('0')},
    '1450': {'name': 'Akumulasi Penyusutan Peralatan', 'type': 'Harta', 'subtype': 'Harta Tetap', 'balance': Decimal('0')},
    '2000': {'name': 'Utang Usaha', 'type': 'Kewajiban', 'subtype': 'Kewajiban Lancar', 'balance': Decimal('0')},
    '2100': {'name': 'Utang Gaji', 'type': 'Kewajiban', 'subtype': 'Kewajiban Lancar', 'balance': Decimal('0')},
    '3000': {'name': 'Modal Saham', 'type': 'Modal', 'subtype': 'Modal Disetor', 'balance': Decimal('0')},
    '3100': {'name': 'Laba Ditahan', 'type': 'Modal', 'subtype': 'Laba Ditahan', 'balance': Decimal('0')},
    '4000': {'name': 'Pendapatan Jasa', 'type': 'Pendapatan', 'subtype': 'Pendapatan Operasional', 'balance': Decimal('0')},
    '4100': {'name': 'Pendapatan Bunga', 'type': 'Pendapatan', 'subtype': 'Pendapatan Non-Operasional', 'balance': Decimal('0')},
    '5000': {'name': 'Beban Gaji', 'type': 'Beban', 'subtype': 'Beban Operasional', 'balance': Decimal('0')},
    '5100': {'name': 'Beban Penyusutan', 'type': 'Beban', 'subtype': 'Beban Operasional', 'balance': Decimal('0')},
    '5200': {'name': 'Beban Listrik', 'type': 'Beban', 'subtype': 'Beban Operasional', 'balance': Decimal('0')},
}

# Journal entries storage
journal_entries = []
adjusting_entries = []
closing_entries = []

# Utility functions
def format_currency(amount):
    """Format amount as Indonesian Rupiah"""
    if amount is None:
        amount = Decimal('0')
    return f"Rp {amount:,.2f}".replace(',', 'X').replace('.', ',').replace('X', '.')

def get_account_balance(account_code):
    """Get current balance of an account"""
    return chart_of_accounts.get(account_code, {}).get('balance', Decimal('0'))

def update_account_balance(account_code, amount, is_debit=True):
    """Update account balance based on accounting rules"""
    if account_code not in chart_of_accounts:
        return False
    
    account_type = chart_of_accounts[account_code]['type']
    current_balance = chart_of_accounts[account_code]['balance']
    
    # Normal balance rules:
    # Assets (Harta) and Expenses (Beban): Debit increases, Credit decreases
    # Liabilities (Kewajiban), Equity (Modal), Revenue (Pendapatan): Credit increases, Debit decreases
    
    if account_type in ['Harta', 'Beban']:
        if is_debit:
            chart_of_accounts[account_code]['balance'] = current_balance + amount
        else:
            chart_of_accounts[account_code]['balance'] = current_balance - amount
    else:  # Kewajiban, Modal, Pendapatan
        if is_debit:
            chart_of_accounts[account_code]['balance'] = current_balance - amount
        else:
            chart_of_accounts[account_code]['balance'] = current_balance + amount
    
    return True

def calculate_trial_balance():
    """Calculate trial balance"""
    trial_balance = []
    total_debit = Decimal('0')
    total_credit = Decimal('0')
    
    for code, account in chart_of_accounts.items():
        balance = account['balance']
        debit_balance = Decimal('0')
        credit_balance = Decimal('0')
        
        # Determine if balance goes to debit or credit side
        if account['type'] in ['Harta', 'Beban']:
            if balance >= 0:
                debit_balance = balance
                total_debit += balance
            else:
                credit_balance = abs(balance)
                total_credit += abs(balance)
        else:  # Kewajiban, Modal, Pendapatan
            if balance >= 0:
                credit_balance = balance
                total_credit += balance
            else:
                debit_balance = abs(balance)
                total_debit += abs(balance)
        
        trial_balance.append({
            'code': code,
            'name': account['name'],
            'type': account['type'],
            'debit': debit_balance,
            'credit': credit_balance
        })
    
    return trial_balance, total_debit, total_credit

# Authentication routes
@app.route('/')
def index():
    if 'username' in session:
        return redirect(url_for('dashboard'))
    return redirect(url_for('login'))

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        
        if username in users_db and check_password_hash(users_db[username]['password_hash'], password):
            session['username'] = username
            session['role'] = users_db[username]['role']
            session['name'] = users_db[username]['name']
            flash('Login berhasil!', 'success')
            return redirect(url_for('dashboard'))
        else:
            flash('Username atau password salah!', 'error')
    
    return render_template('login.html')

@app.route('/logout')
def logout():
    session.clear()
    flash('Logout berhasil!', 'success')
    return redirect(url_for('login'))

# Main application routes
@app.route('/dashboard')
def dashboard():
    if 'username' not in session:
        return redirect(url_for('login'))
    
    # Calculate summary statistics
    total_assets = sum(acc['balance'] for acc in chart_of_accounts.values() if acc['type'] == 'Harta')
    total_liabilities = sum(acc['balance'] for acc in chart_of_accounts.values() if acc['type'] == 'Kewajiban')
    total_equity = sum(acc['balance'] for acc in chart_of_accounts.values() if acc['type'] == 'Modal')
    total_revenue = sum(acc['balance'] for acc in chart_of_accounts.values() if acc['type'] == 'Pendapatan')
    total_expenses = sum(acc['balance'] for acc in chart_of_accounts.values() if acc['type'] == 'Beban')
    
    net_income = total_revenue - total_expenses
    
    return render_template('dashboard.html',
                         total_assets=total_assets,
                         total_liabilities=total_liabilities,
                         total_equity=total_equity,
                         total_revenue=total_revenue,
                         total_expenses=total_expenses,
                         net_income=net_income,
                         format_currency=format_currency)

@app.route('/chart-of-accounts')
def chart_of_accounts_view():
    if 'username' not in session:
        return redirect(url_for('login'))
    
    # Group accounts by type for better display
    grouped_accounts = {}
    for code, account in chart_of_accounts.items():
        account_type = account['type']
        if account_type not in grouped_accounts:
            grouped_accounts[account_type] = []
        grouped_accounts[account_type].append({
            'code': code,
            'name': account['name'],
            'subtype': account['subtype'],
            'balance': account['balance']
        })
    
    return render_template('chart_of_accounts.html', 
                         grouped_accounts=grouped_accounts,
                         format_currency=format_currency)

@app.route('/add-account', methods=['GET', 'POST'])
def add_account():
    if 'username' not in session or session['role'] != 'admin':
        flash('Akses ditolak!', 'error')
        return redirect(url_for('dashboard'))
    
    if request.method == 'POST':
        code = request.form['code']
        name = request.form['name']
        account_type = request.form['type']
        subtype = request.form['subtype']
        
        if code in chart_of_accounts:
            flash('Kode akun sudah ada!', 'error')
        else:
            chart_of_accounts[code] = {
                'name': name,
                'type': account_type,
                'subtype': subtype,
                'balance': Decimal('0')
            }
            flash('Akun berhasil ditambahkan!', 'success')
            return redirect(url_for('chart_of_accounts_view'))
    
    return render_template('add_account.html')

@app.route('/journal-entry', methods=['GET', 'POST'])
def journal_entry():
    if 'username' not in session:
        return redirect(url_for('login'))
    
    if request.method == 'POST':
        date = request.form['date']
        description = request.form['description']
        
        # Process journal entry lines
        entry_lines = []
        total_debit = Decimal('0')
        total_credit = Decimal('0')
        
        # Get all form fields for entry lines
        i = 0
        while f'account_{i}' in request.form:
            account_code = request.form[f'account_{i}']
            debit_amount = request.form.get(f'debit_{i}', '0')
            credit_amount = request.form.get(f'credit_{i}', '0')
            
            if account_code and (debit_amount != '0' or credit_amount != '0'):
                debit_amount = Decimal(debit_amount) if debit_amount else Decimal('0')
                credit_amount = Decimal(credit_amount) if credit_amount else Decimal('0')
                
                entry_lines.append({
                    'account_code': account_code,
                    'account_name': chart_of_accounts[account_code]['name'],
                    'debit': debit_amount,
                    'credit': credit_amount
                })
                
                total_debit += debit_amount
                total_credit += credit_amount
                
                # Update account balances
                if debit_amount > 0:
                    update_account_balance(account_code, debit_amount, is_debit=True)
                if credit_amount > 0:
                    update_account_balance(account_code, credit_amount, is_debit=False)
            
            i += 1
        
        # Validate debit = credit
        if total_debit != total_credit:
            flash('Total debit harus sama dengan total kredit!', 'error')
        elif entry_lines:
            journal_entry_data = {
                'id': len(journal_entries) + 1,
                'date': date,
                'description': description,
                'lines': entry_lines,
                'total_debit': total_debit,
                'total_credit': total_credit,
                'created_by': session['username'],
                'created_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            }
            journal_entries.append(journal_entry_data)
            flash('Jurnal umum berhasil disimpan!', 'success')
            return redirect(url_for('journal_entry'))
        else:
            flash('Minimal satu baris jurnal harus diisi!', 'error')
    
    return render_template('journal_entry.html', accounts=chart_of_accounts)

@app.route('/adjusting-entry', methods=['GET', 'POST'])
def adjusting_entry():
    if 'username' not in session:
        return redirect(url_for('login'))
    
    if request.method == 'POST':
        date = request.form['date']
        description = request.form['description']
        
        # Process adjusting entry lines
        entry_lines = []
        total_debit = Decimal('0')
        total_credit = Decimal('0')
        
        # Get all form fields for entry lines
        i = 0
        while f'account_{i}' in request.form:
            account_code = request.form[f'account_{i}']
            debit_amount = request.form.get(f'debit_{i}', '0')
            credit_amount = request.form.get(f'credit_{i}', '0')
            
            if account_code and (debit_amount != '0' or credit_amount != '0'):
                debit_amount = Decimal(debit_amount) if debit_amount else Decimal('0')
                credit_amount = Decimal(credit_amount) if credit_amount else Decimal('0')
                
                entry_lines.append({
                    'account_code': account_code,
                    'account_name': chart_of_accounts[account_code]['name'],
                    'debit': debit_amount,
                    'credit': credit_amount
                })
                
                total_debit += debit_amount
                total_credit += credit_amount
                
                # Update account balances
                if debit_amount > 0:
                    update_account_balance(account_code, debit_amount, is_debit=True)
                if credit_amount > 0:
                    update_account_balance(account_code, credit_amount, is_debit=False)
            
            i += 1
        
        # Validate debit = credit
        if total_debit != total_credit:
            flash('Total debit harus sama dengan total kredit!', 'error')
        elif entry_lines:
            adjusting_entry_data = {
                'id': len(adjusting_entries) + 1,
                'date': date,
                'description': description,
                'lines': entry_lines,
                'total_debit': total_debit,
                'total_credit': total_credit,
                'created_by': session['username'],
                'created_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            }
            adjusting_entries.append(adjusting_entry_data)
            flash('Jurnal penyesuaian berhasil disimpan!', 'success')
            return redirect(url_for('adjusting_entry'))
        else:
            flash('Minimal satu baris jurnal harus diisi!', 'error')
    
    return render_template('adjusting_entry.html', accounts=chart_of_accounts)

@app.route('/closing-entry', methods=['GET', 'POST'])
def closing_entry():
    if 'username' not in session or session['role'] != 'admin':
        flash('Akses ditolak!', 'error')
        return redirect(url_for('dashboard'))
    
    if request.method == 'POST':
        date = request.form['date']
        
        # Calculate net income
        total_revenue = sum(acc['balance'] for acc in chart_of_accounts.values() if acc['type'] == 'Pendapatan')
        total_expenses = sum(acc['balance'] for acc in chart_of_accounts.values() if acc['type'] == 'Beban')
        net_income = total_revenue - total_expenses
        
        # Create closing entries
        entry_lines = []
        
        # Close revenue accounts
        for code, account in chart_of_accounts.items():
            if account['type'] == 'Pendapatan' and account['balance'] != 0:
                entry_lines.append({
                    'account_code': code,
                    'account_name': account['name'],
                    'debit': account['balance'],
                    'credit': Decimal('0')
                })
                # Zero out revenue account
                chart_of_accounts[code]['balance'] = Decimal('0')
        
        # Close expense accounts
        for code, account in chart_of_accounts.items():
            if account['type'] == 'Beban' and account['balance'] != 0:
                entry_lines.append({
                    'account_code': code,
                    'account_name': account['name'],
                    'debit': Decimal('0'),
                    'credit': account['balance']
                })
                # Zero out expense account
                chart_of_accounts[code]['balance'] = Decimal('0')
        
        # Transfer net income to retained earnings
        if net_income != 0:
            retained_earnings_code = '3100'  # Laba Ditahan
            entry_lines.append({
                'account_code': retained_earnings_code,
                'account_name': chart_of_accounts[retained_earnings_code]['name'],
                'debit': Decimal('0') if net_income > 0 else abs(net_income),
                'credit': net_income if net_income > 0 else Decimal('0')
            })
            # Update retained earnings balance
            chart_of_accounts[retained_earnings_code]['balance'] += net_income
        
        closing_entry_data = {
            'id': len(closing_entries) + 1,
            'date': date,
            'description': 'Jurnal Penutup - Tutup Buku Periode',
            'lines': entry_lines,
            'net_income': net_income,
            'created_by': session['username'],
            'created_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }
        closing_entries.append(closing_entry_data)
        flash('Jurnal penutup berhasil dibuat!', 'success')
        return redirect(url_for('closing_entry'))
    
    # Calculate current period net income for preview
    total_revenue = sum(acc['balance'] for acc in chart_of_accounts.values() if acc['type'] == 'Pendapatan')
    total_expenses = sum(acc['balance'] for acc in chart_of_accounts.values() if acc['type'] == 'Beban')
    net_income = total_revenue - total_expenses
    
    return render_template('closing_entry.html', 
                         total_revenue=total_revenue,
                         total_expenses=total_expenses,
                         net_income=net_income,
                         format_currency=format_currency)

@app.route('/general-ledger')
def general_ledger():
    if 'username' not in session:
        return redirect(url_for('login'))
    
    # Build general ledger with transaction history
    ledger_data = {}
    
    for code, account in chart_of_accounts.items():
        ledger_data[code] = {
            'name': account['name'],
            'type': account['type'],
            'transactions': [],
            'balance': account['balance']
        }
    
    # Add journal entries to ledger
    for entry in journal_entries:
        for line in entry['lines']:
            ledger_data[line['account_code']]['transactions'].append({
                'date': entry['date'],
                'description': entry['description'],
                'debit': line['debit'],
                'credit': line['credit'],
                'type': 'Jurnal Umum'
            })
    
    # Add adjusting entries to ledger
    for entry in adjusting_entries:
        for line in entry['lines']:
            ledger_data[line['account_code']]['transactions'].append({
                'date': entry['date'],
                'description': entry['description'],
                'debit': line['debit'],
                'credit': line['credit'],
                'type': 'Jurnal Penyesuaian'
            })
    
    # Add closing entries to ledger
    for entry in closing_entries:
        for line in entry['lines']:
            ledger_data[line['account_code']]['transactions'].append({
                'date': entry['date'],
                'description': entry['description'],
                'debit': line['debit'],
                'credit': line['credit'],
                'type': 'Jurnal Penutup'
            })
    
    # Sort transactions by date
    for code in ledger_data:
        ledger_data[code]['transactions'].sort(key=lambda x: x['date'])
    
    return render_template('general_ledger.html', 
                         ledger_data=ledger_data,
                         format_currency=format_currency)

@app.route('/trial-balance')
def trial_balance():
    if 'username' not in session:
        return redirect(url_for('login'))
    
    trial_balance_data, total_debit, total_credit = calculate_trial_balance()
    
    return render_template('trial_balance.html',
                         trial_balance=trial_balance_data,
                         total_debit=total_debit,
                         total_credit=total_credit,
                         format_currency=format_currency)

@app.route('/income-statement')
def income_statement():
    if 'username' not in session:
        return redirect(url_for('login'))
    
    # Calculate income statement
    revenue_accounts = []
    expense_accounts = []
    total_revenue = Decimal('0')
    total_expenses = Decimal('0')
    
    for code, account in chart_of_accounts.items():
        if account['type'] == 'Pendapatan':
            revenue_accounts.append({
                'code': code,
                'name': account['name'],
                'balance': account['balance']
            })
            total_revenue += account['balance']
        elif account['type'] == 'Beban':
            expense_accounts.append({
                'code': code,
                'name': account['name'],
                'balance': account['balance']
            })
            total_expenses += account['balance']
    
    net_income = total_revenue - total_expenses
    
    return render_template('income_statement.html',
                         revenue_accounts=revenue_accounts,
                         expense_accounts=expense_accounts,
                         total_revenue=total_revenue,
                         total_expenses=total_expenses,
                         net_income=net_income,
                         format_currency=format_currency)

@app.route('/balance-sheet')
def balance_sheet():
    if 'username' not in session:
        return redirect(url_for('login'))
    
    # Calculate balance sheet
    asset_accounts = []
    liability_accounts = []
    equity_accounts = []
    total_assets = Decimal('0')
    total_liabilities = Decimal('0')
    total_equity = Decimal('0')
    
    for code, account in chart_of_accounts.items():
        if account['type'] == 'Harta':
            asset_accounts.append({
                'code': code,
                'name': account['name'],
                'subtype': account['subtype'],
                'balance': account['balance']
            })
            total_assets += account['balance']
        elif account['type'] == 'Kewajiban':
            liability_accounts.append({
                'code': code,
                'name': account['name'],
                'subtype': account['subtype'],
                'balance': account['balance']
            })
            total_liabilities += account['balance']
        elif account['type'] == 'Modal':
            equity_accounts.append({
                'code': code,
                'name': account['name'],
                'subtype': account['subtype'],
                'balance': account['balance']
            })
            total_equity += account['balance']
    
    return render_template('balance_sheet.html',
                         asset_accounts=asset_accounts,
                         liability_accounts=liability_accounts,
                         equity_accounts=equity_accounts,
                         total_assets=total_assets,
                         total_liabilities=total_liabilities,
                         total_equity=total_equity,
                         format_currency=format_currency)

@app.route('/equity-statement')
def equity_statement():
    if 'username' not in session:
        return redirect(url_for('login'))
    
    # Calculate equity statement
    equity_accounts = []
    total_equity = Decimal('0')
    
    for code, account in chart_of_accounts.items():
        if account['type'] == 'Modal':
            equity_accounts.append({
                'code': code,
                'name': account['name'],
                'subtype': account['subtype'],
                'balance': account['balance']
            })
            total_equity += account['balance']
    
    # Calculate current period net income
    total_revenue = sum(acc['balance'] for acc in chart_of_accounts.values() if acc['type'] == 'Pendapatan')
    total_expenses = sum(acc['balance'] for acc in chart_of_accounts.values() if acc['type'] == 'Beban')
    net_income = total_revenue - total_expenses
    
    return render_template('equity_statement.html',
                         equity_accounts=equity_accounts,
                         total_equity=total_equity,
                         net_income=net_income,
                         format_currency=format_currency)

@app.route('/search-entries')
def search_entries():
    if 'username' not in session:
        return redirect(url_for('login'))
    
    # Get search parameters
    search_date = request.args.get('date', '')
    search_account = request.args.get('account', '')
    search_description = request.args.get('description', '')
    
    # Combine all entries for search
    all_entries = []
    
    # Add journal entries
    for entry in journal_entries:
        all_entries.append({
            'type': 'Jurnal Umum',
            'id': entry['id'],
            'date': entry['date'],
            'description': entry['description'],
            'lines': entry['lines'],
            'total_debit': entry['total_debit'],
            'total_credit': entry['total_credit']
        })
    
    # Add adjusting entries
    for entry in adjusting_entries:
        all_entries.append({
            'type': 'Jurnal Penyesuaian',
            'id': entry['id'],
            'date': entry['date'],
            'description': entry['description'],
            'lines': entry['lines'],
            'total_debit': entry['total_debit'],
            'total_credit': entry['total_credit']
        })
    
    # Add closing entries
    for entry in closing_entries:
        all_entries.append({
            'type': 'Jurnal Penutup',
            'id': entry['id'],
            'date': entry['date'],
            'description': entry['description'],
            'lines': entry['lines'],
            'net_income': entry.get('net_income', Decimal('0'))
        })
    
    # Filter entries based on search criteria
    filtered_entries = []
    for entry in all_entries:
        include_entry = True
        
        if search_date and entry['date'] != search_date:
            include_entry = False
        
        if search_description and search_description.lower() not in entry['description'].lower():
            include_entry = False
        
        if search_account:
            account_found = False
            for line in entry['lines']:
                if line['account_code'] == search_account:
                    account_found = True
                    break
            if not account_found:
                include_entry = False
        
        if include_entry:
            filtered_entries.append(entry)
    
    # Sort by date (newest first)
    filtered_entries.sort(key=lambda x: x['date'], reverse=True)
    
    return render_template('search_entries.html',
                         entries=filtered_entries,
                         accounts=chart_of_accounts,
                         search_date=search_date,
                         search_account=search_account,
                         search_description=search_description,
                         format_currency=format_currency)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)

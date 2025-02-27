from flask import Flask, render_template, request, redirect, url_for, flash
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager, UserMixin, login_user, login_required, logout_user, current_user
from flask_bcrypt import Bcrypt
from datetime import datetime
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart

app = Flask(__name__)
app.config['SQLALCHEMY_DATABASE_URI'] = 'mssql+pyodbc://LP-10-BAH-AB/inventory_db?trusted_connection=yes&driver=ODBC+Driver+17+for+SQL+Server'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.secret_key = 'your_secret_key'

db = SQLAlchemy(app)
bcrypt = Bcrypt(app)

# Flask-Login setup
login_manager = LoginManager(app)
login_manager.login_view = 'login'

# User model
class User(db.Model, UserMixin):
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(50), unique=True, nullable=False)
    password = db.Column(db.String(60), nullable=False)
    is_admin = db.Column(db.Boolean, default=False)

class Drug(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    reception_date = db.Column(db.Date, nullable=False, default=datetime.utcnow)
    expiry_date = db.Column(db.Date, nullable=False, default=datetime.utcnow)
    total_stock = db.Column(db.Integer, nullable=False)
    used = db.Column(db.Integer, default=0)
    remaining = db.Column(db.Integer, nullable=False)
    received_from = db.Column(db.String(100), nullable=True)
    box_count = db.Column(db.Integer, default=1)
    pack_per_box = db.Column(db.Integer, default=1)
    lot_batch_number = db.Column(db.String(100), nullable=True)
    company_name = db.Column(db.String(100), nullable=True)
    reference_number = db.Column(db.String(100), nullable=True)
    reorder_limit = db.Column(db.Integer, nullable=False, default=10)  # Add this line

# Load user for Flask-Login
@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

# SMTP Configuration
SMTP_SERVER = 'smtp.gmail.com'
SMTP_PORT = 587
SMTP_USERNAME = 'your_email@gmail.com'  # Replace with your Gmail address
SMTP_PASSWORD = 'your_app_password'    # Replace with the generated app password
ADMIN_EMAIL = 'admin@example.com'      # Replace with the admin email

# Function to send email
def send_email(subject, body):
    try:
        msg = MIMEMultipart()
        msg['From'] = SMTP_USERNAME
        msg['To'] = ADMIN_EMAIL
        msg['Subject'] = subject
        msg.attach(MIMEText(body, 'plain'))

        server = smtplib.SMTP(SMTP_SERVER, SMTP_PORT)
        server.starttls()
        server.login(SMTP_USERNAME, SMTP_PASSWORD)
        server.sendmail(SMTP_USERNAME, ADMIN_EMAIL, msg.as_string())
        server.quit()
        print("Email sent successfully!")
    except Exception as e:
        print(f"Error sending email: {e}")

# Home route (requires login)
@app.route('/')
@login_required
def index():
    drugs = Drug.query.filter(Drug.remaining != 0).all()
    for drug in drugs:
        if drug.remaining <= drug.reorder_limit:  # Check against reorder_limit
            flash(f'Reorder {drug.name} - This item is below the reorder limit! Remaining stock: {drug.remaining}', 'warning')
            
            # Send email alert
            subject = f"Low Stock Alert: {drug.name}"
            body = f"The stock for {drug.name} is below the reorder limit. Remaining stock: {drug.remaining}. Please reorder soon."
            send_email(subject, body)
    
    return render_template('index.html', drugs=drugs, datetime=datetime)
# Login route
@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        user = User.query.filter_by(username=username).first()
        if user and bcrypt.check_password_hash(user.password, password):
            login_user(user)
            flash('Login successful!', 'success')
            return redirect(url_for('index'))
        else:
            flash('Invalid username or password.', 'danger')
    return render_template('login.html')

# Signup route (only accessible by admin)
@app.route('/signup', methods=['GET', 'POST'])
@login_required
def signup():
    if not current_user.is_admin:
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('index'))
    
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        is_admin = request.form.get('is_admin') == 'on'

        # Check if the username already exists
        existing_user = User.query.filter_by(username=username).first()
        if existing_user:
            flash('Username already exists. Please choose a different username.', 'danger')
            return redirect(url_for('signup'))

        # If the username is unique, create the user
        hashed_password = bcrypt.generate_password_hash(password).decode('utf-8')
        new_user = User(username=username, password=hashed_password, is_admin=is_admin)
        db.session.add(new_user)
        db.session.commit()
        flash('Account created successfully!', 'success')
        return redirect(url_for('login'))
    return render_template('signup.html')
def create_admin_user():
    # Check if an admin user already exists
    admin_user = User.query.filter_by(username='admin').first()
    if not admin_user:
        # Create the admin user
        hashed_password = bcrypt.generate_password_hash('admin123').decode('utf-8')
        admin_user = User(username='admin', password=hashed_password, is_admin=True)
        db.session.add(admin_user)
        db.session.commit()
        print("Admin user created successfully!")
    else:
        print("Admin user already exists.")
# Route to manage users (only accessible by admin)
@app.route('/manage_users', methods=['GET', 'POST'])
@login_required
def manage_users():
    if not current_user.is_admin:
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('index'))

    if request.method == 'POST':
        # Handle adding a new user
        username = request.form['username']
        password = request.form['password']
        is_admin = request.form.get('is_admin') == 'on'

        # Check if the username already exists
        existing_user = User.query.filter_by(username=username).first()
        if existing_user:
            flash('Username already exists. Please choose a different username.', 'danger')
        else:
            # Create the new user
            hashed_password = bcrypt.generate_password_hash(password).decode('utf-8')
            new_user = User(username=username, password=hashed_password, is_admin=is_admin)
            db.session.add(new_user)
            db.session.commit()
            flash('User added successfully!', 'success')

    # Fetch all users
    users = User.query.all()
    return render_template('manage_users.html', users=users)

# Route to delete a user (only accessible by admin)
@app.route('/delete_user/<int:user_id>', methods=['POST'])
@login_required
def delete_user(user_id):
    if not current_user.is_admin:
        flash('You do not have permission to perform this action.', 'danger')
        return redirect(url_for('index'))

    user = User.query.get_or_404(user_id)
    db.session.delete(user)
    db.session.commit()
    flash('User deleted successfully!', 'success')
    return redirect(url_for('manage_users'))
# Call this function during application initialization
with app.app_context():
    create_admin_user()
# Logout route
@app.route('/logout')
@login_required
def logout():
    logout_user()
    flash('You have been logged out.', 'success')
    return redirect(url_for('login'))

@app.route('/add', methods=['POST'])
@login_required
def add_drug():
    if not current_user.is_admin:
        flash('You do not have permission to add item.', 'danger')
        return redirect(url_for('index'))
    
    try:
        name = request.form['name']
        received_from = request.form['received_from']
        box_count = int(request.form['box_count'])
        pack_per_box = int(request.form['pack_per_box'])
        lot_batch_number_lotnumber = request.form['lot_batch_number']
        company_name = request.form['company_name']
        expiry_date = request.form['expiry_date']
        reference_number = request.form['reference_number']
        reorder_limit = int(request.form['reorder_limit'])  # Add this line

        new_drug = Drug(
            name=name,
            received_from=received_from,
            box_count=box_count,
            pack_per_box=pack_per_box,
            lot_batch_number=lot_batch_number_lotnumber,
            company_name=company_name,
            reference_number=reference_number,
            expiry_date=expiry_date,
            total_stock=box_count * pack_per_box,
            remaining=box_count,  # Remaining is now based on box_count
            reorder_limit=reorder_limit  # Add this line
        )
        db.session.add(new_drug)
        db.session.commit()
        flash('Item added successfully!', 'success')
    except Exception as e:
        db.session.rollback()
        flash(f'Error adding item: {str(e)}', 'danger')
    return redirect(url_for('index'))

@app.route('/update_used/<int:drug_id>', methods=['POST'])
@login_required
def update_used(drug_id):
    if not current_user.is_admin:
        flash('You do not have permission to update item usage.', 'danger')
        return redirect(url_for('index'))
    
    try:
        drug = Drug.query.get_or_404(drug_id)
        used = int(request.form['used'])
        
        if used > drug.remaining:
            flash('Used quantity cannot exceed remaining stock!', 'danger')
        else:
            drug.used += used
            drug.remaining = drug.box_count - drug.used  # Remaining is now based on box_count
            db.session.commit()
            flash('Used quantity updated successfully!', 'success')
    except Exception as e:
        db.session.rollback()
        flash(f'Error updating used quantity: {str(e)}', 'danger')
    return redirect(url_for('index'))

@app.route('/search', methods=['GET'])
@login_required
def search_drug():
    query = request.args.get('search_query', '')
    drugs = Drug.query.filter(Drug.name.ilike(f'%{query}%')).all()
    return render_template('index.html', drugs=drugs, search_query=query)

@app.route('/summary')
@login_required
def summary():
    if not current_user.is_admin:
        flash('You do not have permission to view the summary.', 'danger')
        return redirect(url_for('index'))
    
    # Get the start_date and end_date from the query parameters
    start_date = request.args.get('start_date')
    end_date = request.args.get('end_date')

    # Query the database based on the date range
    if start_date and end_date:
        # Convert the date strings to date objects
        start_date = datetime.strptime(start_date, '%Y-%m-%d').date()
        end_date = datetime.strptime(end_date, '%Y-%m-%d').date()

        # Fetch drugs within the date range
        drugs = Drug.query.filter(
            Drug.reception_date >= start_date,
            Drug.reception_date <= end_date
        ).all()
    else:
        # If no date range is provided, fetch all drugs
        drugs = Drug.query.all()

    # Calculate totals
    total_stock = sum(drug.total_stock for drug in drugs)
    total_used = sum(drug.used for drug in drugs)
    total_remaining = sum(drug.remaining for drug in drugs)

    return render_template(
        'summary.html',
        drugs=drugs,
        total_stock=total_stock,
        total_used=total_used,
        total_remaining=total_remaining
    )

if __name__ == '__main__':
    with app.app_context():
        db.create_all()
    app.run(host='0.0.0.0', port=5000, debug=True)
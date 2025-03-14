"""
Flask Application for Lab Consumables Management
This application provides functionalities for managing lab consumables, including drugs and expired drugs, 
user authentication, and organization management. The application uses Flask, Flask-SQLAlchemy, Flask-Login, 
and Flask-Bcrypt for web framework, ORM, user session management, and password hashing respectively.
Modules:
    - flask: Flask, render_template, request, redirect, url_for, flash
    - flask_sqlalchemy: SQLAlchemy
    - flask_login: LoginManager, UserMixin, login_user, login_required, logout_user, current_user
    - flask_bcrypt: Bcrypt
    - datetime: datetime
Configuration:
    - SQLALCHEMY_DATABASE_URI: Database URI for SQLAlchemy
    - SQLALCHEMY_TRACK_MODIFICATIONS: Track modifications setting for SQLAlchemy
    - secret_key: Secret key for session management
Database Models:
    - Organization: Represents an organization with users and drugs
    - User: Represents a user with authentication details
    - Drug: Represents a drug with inventory details
    - ExpiredDrug: Represents an expired drug with inventory details
Routes:
    - /: Home route, displays drugs for the current organization (requires login)
    - /remove_expired/<int:drug_id>: Removes an expired drug and moves it to expired items (requires login)
    - /login: Login route for user authentication
    - /logout: Logout route for ending user session (requires login)
    - /signup: Signup route for creating new users (requires admin access)
    - /manage_users: Route for managing users within the organization (requires admin access)
    - /add: Route for adding new drugs to the inventory (requires login)
    - /search: Route for searching drugs by name (requires login)
    - /summary: Route for generating inventory summary reports (requires login)
    - /register_organization: Route for registering a new organization and its superuser
    - /update_used/<int:drug_id>: Route for updating the used quantity of a drug (requires login)
    - /delete_user/<int:user_id>: Route for deleting a user from the organization (requires admin access)
Functions:
    - load_user(user_id): Loads a user for Flask-Login session management
Execution:
    - The application runs on host '0.0.0.0' and port 5000 with debug mode enabled
    # Install the required modules
    pip install flask flask_sqlalchemy flask_login flask_bcrypt apscheduler pywin32

- win32com.client: win32 (for sending emails via Outlook)
- apscheduler.schedulers.background: BackgroundScheduler (for scheduling tasks)
- atexit: atexit (for ensuring the scheduler shuts down when the app stops)
- DrugUpdate: Represents updates to drug quantities
- /log_report: Route for generating a log report of drug updates (requires login)
- send_outlook_email(subject, body, to_recipients, sender_email, cc_recipients=None, attachment=None): Sends an email using Outlook
- send_daily_email_reminder(): Sends a daily email reminder for low stock items
- create_system_owner(): Creates a system owner if one does not exist
-this is abdoulie bahgit
"""
from flask import Flask, render_template, request, redirect, url_for, flash
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager, UserMixin, login_user, login_required, logout_user, current_user
from flask_bcrypt import Bcrypt
from datetime import datetime
import win32com.client as win32
from apscheduler.schedulers.background import BackgroundScheduler
import atexit




app = Flask(__name__)
app.config['SQLALCHEMY_DATABASE_URI'] = 'mssql+pyodbc://LP-10-BAH-AB/inventory_db?trusted_connection=yes&driver=ODBC+Driver+17+for+SQL+Server'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.secret_key = 'your_secret_key'

db = SQLAlchemy(app)
bcrypt = Bcrypt(app)

# Flask-Login setup
login_manager = LoginManager(app)
login_manager.login_view = 'login'  # Set the login view

# APScheduler setup
scheduler = BackgroundScheduler()
scheduler.start()

# Ensure the scheduler shuts down when the app stops
atexit.register(lambda: scheduler.shutdown())



@app.context_processor
def inject_datetime():
    return dict(datetime=datetime)

class Organization(db.Model):
    __tablename__ = 'organization'
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), unique=True, nullable=False)
    users = db.relationship('User', backref='organization', lazy=True)
    drugs = db.relationship('Drug', backref='organization', lazy=True)
    expired_drugs = db.relationship('ExpiredDrug', backref='organization', lazy=True)

class User(db.Model, UserMixin):
    __tablename__ = 'user'
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(50), unique=True, nullable=False)
    password = db.Column(db.String(60), nullable=False)
    email = db.Column(db.String(100), nullable=False)  # Add this line for email
    is_admin = db.Column(db.Boolean, default=False)
    role = db.Column(db.String(50), default='user')
    organization_id = db.Column(db.Integer, db.ForeignKey('organization.id'), nullable=False)

class Drug(db.Model):
    __tablename__ = 'drug'
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    reception_date = db.Column(db.Date, nullable=False, default=datetime.utcnow)
    expiry_date = db.Column(db.Date, nullable=False, default=datetime.utcnow)
    total_stock = db.Column(db.Integer, nullable=False)
    used = db.Column(db.Integer, default=0)
    remaining = db.Column(db.Integer, nullable=False)
    losses_adjustment = db.Column(db.Integer, default=0)  # New column for losses/adjustment
    received_from = db.Column(db.String(100), nullable=True)
    box_count = db.Column(db.Integer, default=1)
    pack_per_box = db.Column(db.Integer, default=1)
    lot_batch_number = db.Column(db.String(100), nullable=True)
    company_name = db.Column(db.String(100), nullable=True)
    reference_number = db.Column(db.String(100), nullable=True)
    reorder_limit = db.Column(db.Integer, nullable=False, default=10)
    is_maintained = db.Column(db.Boolean, default=True)
    first_used_date = db.Column(db.Date, nullable=True)
    date_finished = db.Column(db.Date, nullable=True)
    organization_id = db.Column(db.Integer, db.ForeignKey('organization.id'), nullable=False)
class ExpiredDrug(db.Model):
    __tablename__ = 'expired_drug'
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    reception_date = db.Column(db.Date, nullable=False)
    expiry_date = db.Column(db.Date, nullable=False)
    total_stock = db.Column(db.Integer, nullable=False)
    used = db.Column(db.Integer, default=0)
    remaining = db.Column(db.Integer, nullable=False)
    received_from = db.Column(db.String(100), nullable=True)
    box_count = db.Column(db.Integer, default=1)
    pack_per_box = db.Column(db.Integer, default=1)
    lot_batch_number = db.Column(db.String(100), nullable=True)
    company_name = db.Column(db.String(100), nullable=True)
    reference_number = db.Column(db.String(100), nullable=True)
    reorder_limit = db.Column(db.Integer, nullable=False, default=10)
    date_finished = db.Column(db.Date, nullable=True)
    first_used_date = db.Column(db.Date, nullable=True)
    organization_id = db.Column(db.Integer, db.ForeignKey('organization.id'), nullable=False)

class DrugUpdate(db.Model):
    __tablename__ = 'drug_update'
    id = db.Column(db.Integer, primary_key=True)
    drug_id = db.Column(db.Integer, db.ForeignKey('drug.id'), nullable=False)  # Ensure nullable=False
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    updated_amount = db.Column(db.Integer, nullable=False)
    update_date = db.Column(db.DateTime, default=datetime.utcnow)

# Load user for Flask-Login
@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

# Home route (requires login)
@app.route('/')
@login_required
def index():
    # Fetch drugs only for the current organization
    drugs = Drug.query.filter_by(organization_id=current_user.organization_id).filter(Drug.remaining != 0).all()
    today = datetime.utcnow().date()

    # Fetch the admin's email from the database
    admin = User.query.filter_by(organization_id=current_user.organization_id, is_admin=True).first()
    if not admin:
        flash('No admin found for this organization.', 'danger')
        return render_template('index.html', drugs=drugs, datetime=datetime)

    admin_email = admin.email  # Get the admin's email from the database

    # Check if admin_email is None or empty
    if not admin_email:
        flash('Admin email is not set. Please update the admin profile with a valid email.', 'warning')
        return render_template('index.html', drugs=drugs, datetime=datetime)

    for drug in drugs:
        if drug.remaining <= drug.reorder_limit:
            flash(f'Reorder {drug.name} - This item is below the reorder limit! Remaining stock: {drug.remaining}', 'warning')
            
            # Send an email to the admin
            subject = f"Low Stock Alert: {drug.name}"
            body = f"The current stock level for {drug.name} is below the reorder limit. Remaining stock: {drug.remaining}."
            to_recipients = [admin_email]  # Use the admin's email from the database
            sender_email = "lshab52@lshtm.ac.uk"  # Hardcode the sender's email or fetch it from a config file
            cc_recipients = None  # Optional: Add CC recipients if needed
            attachment = None  # Optional: Add attachment if needed

            send_outlook_email(subject, body, to_recipients, sender_email, cc_recipients, attachment)

        if drug.expiry_date < today:
            drug.is_maintained = False
        else:
            drug.is_maintained = True

    db.session.commit()
    return render_template('index.html', drugs=drugs, datetime=datetime)

# Remove expired drug route
@app.route('/remove_expired/<int:drug_id>', methods=['POST'])
@login_required
def remove_expired(drug_id):
    drug = Drug.query.get_or_404(drug_id)

    if not drug.is_maintained:  # Only remove if marked as expired
        # Create a new ExpiredDrug entry
        expired_drug = ExpiredDrug(
            name=drug.name,
            reception_date=drug.reception_date,
            expiry_date=drug.expiry_date,
            total_stock=drug.total_stock,
            used=drug.used,
            remaining=drug.remaining,
            received_from=drug.received_from,
            box_count=drug.box_count,
            pack_per_box=drug.pack_per_box,
            lot_batch_number=drug.lot_batch_number,
            company_name=drug.company_name,
            reference_number=drug.reference_number,
            reorder_limit=drug.reorder_limit,
            date_finished=datetime.utcnow().date(),  # Set the date_finished to today
            first_used_date=drug.first_used_date  # Copy the first_used_date from the Drug table
        )
        
        db.session.add(expired_drug)
        db.session.delete(drug)
        
        # Record the update in the drug_update table
        drug_update = DrugUpdate(
            drug_id=drug.id,  # Ensure drug_id is set correctly
            user_id=current_user.id,
            updated_amount=drug.used,
            
        )
        db.session.add(drug_update)
        
        db.session.commit()
        flash(f'Expired item "{drug.name}" removed and moved to expired items!', 'success')

    return redirect(url_for('index'))


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

            # Redirect based on role
            if user.role == 'system_owner':
                return redirect(url_for('register_organization'))  # Redirect to register_organization
            else:
                return redirect(url_for('index'))  # Redirect to the home page for other users
        else:
            flash('Invalid username or password.', 'danger')
    return render_template('login.html')
def create_system_owner():
    # Check if a system owner already exists
    system_owner = User.query.filter_by(role='system_owner').first()
    if not system_owner:
        # Create the system owner
        hashed_password = bcrypt.generate_password_hash('system_owner_password').decode('utf-8')
        system_owner = User(
            username='system_owner',
            password=hashed_password,
            role='system_owner',
            is_admin=True,
            organization_id=None  # System owner doesn't belong to any organization initially
        )
        db.session.add(system_owner)
        db.session.commit()
        print("System owner created successfully!")
    else:
        print("System owner already exists.")
# Logout route
def send_outlook_email(subject, body, to_recipients, sender_email, cc_recipients=None, attachment=None):
    try:
        # Check if to_recipients contains None
        if None in to_recipients:
            raise ValueError("Recipient list contains None. Please provide valid email addresses.")

        # Attempt to get an instance of the Outlook application
        outlook = win32.GetActiveObject("Outlook.Application")
    except Exception as e:
        # If Outlook is not running, start a new instance
        outlook = win32.Dispatch("Outlook.Application")

    mail = outlook.CreateItem(0)  # 0 means the email is a new mail item

    mail.Subject = subject
    mail.Body = body

    # Set the sender's email
    mail.Sender = sender_email

    # Adding recipients
    mail.To = ";".join(to_recipients)  # ";" is used to separate multiple recipients

    if cc_recipients:
        mail.CC = ";".join(cc_recipients)

    # Attach a file if specified
    if attachment:
        mail.Attachments.Add(attachment)

    # Display the email before sending (Outlook will open if not already running)
    mail.Display()

    # Sending the email
    mail.Send()
    print("Email sent successfully!")

# Function to send email reminder
def send_daily_email_reminder():
    with app.app_context():  # Ensure the app context is available
        # Fetch drugs only for the current organization
        drugs = Drug.query.filter(Drug.remaining != 0).all()

        # Fetch the admin's email from the database
        admin = User.query.filter_by(is_admin=True).first()
        if not admin:
            print("No admin found.")
            return

        admin_email = admin.email  # Get the admin's email from the database

        # Check if admin_email is None or empty
        if not admin_email:
            print("Admin email is not set.")
            return

        # Prepare the email content
        low_stock_drugs = [drug for drug in drugs if drug.remaining <= drug.reorder_limit]
        if not low_stock_drugs:
            print("No low stock items found.")
            return

        subject = "Daily Low Stock Reminder"
        body = "The following items are below the reorder limit:\n\n"
        for drug in low_stock_drugs:
            body += f"- {drug.name}: Remaining stock: {drug.remaining}\n"

        to_recipients = [admin_email]  # Use the admin's email from the database
        sender_email = "lshab52@lshtm.ac.uk"  # Hardcode the sender's email or fetch it from a config file
        cc_recipients = None  # Optional: Add CC recipients if needed
        attachment = None  # Optional: Add attachment if needed

        # Send the email
        send_outlook_email(subject, body, to_recipients, sender_email, cc_recipients, attachment)
        print("Daily email reminder sent.")

# Schedule the email reminder to run once per day at a specific time (e.g., 8:00 AM)
scheduler.add_job(
    func=send_daily_email_reminder,
    trigger='cron',
    hour=8,  # 8 AM
    minute=0,
    timezone='UTC'  # Adjust the timezone as needed
)


@app.route('/logout')
@login_required
def logout():
    logout_user()
    flash('You have been logged out.', 'success')
    return redirect(url_for('login'))  # Redirect to the login page

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
        new_user = User(username=username, password=hashed_password, is_admin=is_admin, organization_id=current_user.organization_id)
        db.session.add(new_user)
        db.session.commit()
        flash('Account created successfully!', 'success')
        return redirect(url_for('login'))
    return render_template('signup.html')

# Route to manage users (only accessible by admin)
@app.route('/manage_users', methods=['GET', 'POST'])
@login_required
def manage_users():
    if not current_user.is_admin:
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('index'))

    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        email = request.form['email']  # Get the email from the form
        is_admin = request.form.get('is_admin') == 'on'

        existing_user = User.query.filter_by(username=username).first()
        if existing_user:
            flash('Username already exists. Please choose a different username.', 'danger')
        else:
            hashed_password = bcrypt.generate_password_hash(password).decode('utf-8')
            new_user = User(
                username=username,
                password=hashed_password,
                email=email,  # Save the email
                is_admin=is_admin,
                organization_id=current_user.organization_id
            )
            db.session.add(new_user)
            db.session.commit()
            flash('User added successfully!', 'success')

    users = User.query.filter_by(organization_id=current_user.organization_id).all()
    return render_template('manage_users.html', users=users)

# Add drug route - Allow any logged-in user to add items
@app.route('/add', methods=['POST'])
@login_required
def add_drug():
    try:
        # Extract form data
        name = request.form['name']
        received_from = request.form['received_from']
        box_count = int(request.form['box_count'])
        pack_per_box = int(request.form['pack_per_box'])
        lot_batch_number_lotnumber = request.form['lot_batch_number']
        company_name = request.form['company_name']
        expiry_date = request.form['expiry_date']
        reference_number = request.form['reference_number']
        reorder_limit = int(request.form['reorder_limit'])
        losses_adjustment = int(request.form['losses_adjustment'])  # Get losses/adjustment value

        # Calculate total stock and remaining stock
        total_stock = box_count
        remaining = total_stock - losses_adjustment  # Subtract losses/adjustment from total stock

        # Create a new Drug object
        new_drug = Drug(
            name=name,
            received_from=received_from,
            box_count=box_count,
            pack_per_box=pack_per_box,
            lot_batch_number=lot_batch_number_lotnumber,
            company_name=company_name,
            reference_number=reference_number,
            expiry_date=expiry_date,
            total_stock=total_stock,
            remaining=remaining,  # Set remaining stock after losses/adjustment
            losses_adjustment=losses_adjustment,  # Save losses/adjustment value
            reorder_limit=reorder_limit,
            organization_id=current_user.organization_id  # Associate with the current organization
        )

        # Add the new drug to the database
        db.session.add(new_drug)
        db.session.commit()
        flash('Item added successfully!', 'success')
    except Exception as e:
        # Rollback in case of an error
        db.session.rollback()
        flash(f'Error adding item: {str(e)}', 'danger')
    return redirect(url_for('index'))

# Search drug route
@app.route('/search', methods=['GET'])
@login_required
def search_drug():
    query = request.args.get('search_query', '')
    # Fetch drugs only for the current organization
    drugs = Drug.query.filter(
        Drug.organization_id == current_user.organization_id,
        Drug.name.ilike(f'%{query}%')
    ).all()
    return render_template('index.html', drugs=drugs, search_query=query, datetime=datetime)

# Summary route - Allow any logged-in user to generate reports
@app.route('/summary')
@login_required
def summary():
    # Get the start_date and end_date from the query parameters
    start_date = request.args.get('start_date')
    end_date = request.args.get('end_date')

    # Query the database based on the date range
    if start_date and end_date:
        # Convert the date strings to date objects
        start_date = datetime.strptime(start_date, '%Y-%m-%d').date()
        end_date = datetime.strptime(end_date, '%Y-%m-%d').date()

        # Fetch drugs within the date range for the current organization
        drugs = Drug.query.filter(
            Drug.organization_id == current_user.organization_id,
            Drug.reception_date >= start_date,
            Drug.reception_date <= end_date
        ).all()
        expired_drugs = ExpiredDrug.query.filter(
            ExpiredDrug.organization_id == current_user.organization_id,
            ExpiredDrug.reception_date >= start_date,
            ExpiredDrug.reception_date <= end_date
        ).all()
    else:
        # If no date range is provided, fetch all drugs and expired drugs for the current organization
        drugs = Drug.query.filter_by(organization_id=current_user.organization_id).all()
        expired_drugs = ExpiredDrug.query.filter_by(organization_id=current_user.organization_id).all()

    # Combine the drugs and expired_drugs lists
    all_drugs = drugs + expired_drugs

    # Calculate totals
    total_stock = sum(drug.total_stock for drug in all_drugs)
    total_used = sum(drug.used for drug in all_drugs)
    total_remaining = sum(drug.remaining for drug in all_drugs)

    return render_template(
        'summary.html',
        drugs=all_drugs,
        total_stock=total_stock,
        total_used=total_used,
        total_remaining=total_remaining,
        datetime=datetime  # Pass datetime to the template
    )

# Register organization route
@app.route('/register_organization', methods=['GET', 'POST'])
@login_required
def register_organization():
    if current_user.role != 'system_owner':
        flash('You do not have permission to access this page.', 'danger')
        return redirect(url_for('index'))

    if request.method == 'POST':
        org_name = request.form['org_name']
        admin_username = request.form['admin_username']
        admin_password = request.form['admin_password']

        # Check if the organization name already exists
        existing_org = Organization.query.filter_by(name=org_name).first()
        if existing_org:
            flash('Organization name already exists. Please choose a different name.', 'danger')
            return redirect(url_for('register_organization'))

        # Create the organization
        new_org = Organization(name=org_name)
        db.session.add(new_org)
        db.session.commit()

        # Create the admin user for the organization
        hashed_password = bcrypt.generate_password_hash(admin_password).decode('utf-8')
        admin_user = User(
            username=admin_username,
            password=hashed_password,
            is_admin=True,
            organization_id=new_org.id
        )
        db.session.add(admin_user)
        db.session.commit()

        flash('Organization and admin user created successfully!', 'success')
        return redirect(url_for('index'))

    return render_template('register_organization.html')
# Update used quantity route
@app.route('/update_used/<int:drug_id>', methods=['POST'])
@login_required
def update_used(drug_id):
    try:
        drug = Drug.query.get_or_404(drug_id)
        used = int(request.form['used'])
        

        if used > drug.remaining:
            flash('Used quantity cannot exceed remaining stock!', 'danger')
        else:
            # Check if it's the first time the drug is being used
            if drug.used == 0 and used > 0:
                drug.first_used_date = datetime.utcnow().date()
            
            drug.used += used
            drug.remaining = drug.total_stock - drug.used  # Update remaining stock
            
            # Check if the drug has finished
            if drug.remaining == 0:
                drug.date_finished = datetime.utcnow().date()
            
            # Record the update
            drug_update = DrugUpdate(
                drug_id=drug.id,
                user_id=current_user.id,
                updated_amount=used,
                
            )
            db.session.add(drug_update)
            
            db.session.commit()
            flash('Used quantity updated successfully!', 'success')
    except Exception as e:
        db.session.rollback()
        flash(f'Error updating used quantity: {str(e)}', 'danger')
    return redirect(url_for('index'))

# Delete user route
@app.route('/delete_user/<int:user_id>', methods=['POST'])
@login_required
def delete_user(user_id):
    if not current_user.is_admin:
        flash('You do not have permission to perform this action.', 'danger')
        return redirect(url_for('index'))

    user = User.query.filter_by(id=user_id, organization_id=current_user.organization_id).first()
    if not user:
        flash('User not found or does not belong to your organization.', 'danger')
        return redirect(url_for('manage_users'))

    db.session.delete(user)
    db.session.commit()
    flash('User deleted successfully!', 'success')
    return redirect(url_for('manage_users'))

@app.route('/log_report', methods=['GET'])
@login_required
def log_report():
    # Get query parameters
    start_date = request.args.get('start_date')
    end_date = request.args.get('end_date')
    search_query = request.args.get('search_query', '').strip()  # Get the search query

    # Base query with organization filter
    query = db.session.query(
        DrugUpdate.id,
        DrugUpdate.drug_id,
        Drug.name.label('drug_name'),
        DrugUpdate.user_id,
        User.username.label('user_name'),
        DrugUpdate.updated_amount,
        DrugUpdate.update_date
    ).join(Drug, DrugUpdate.drug_id == Drug.id) \
     .join(User, DrugUpdate.user_id == User.id) \
     .filter(Drug.organization_id == current_user.organization_id)  # Filter by current user's organization

    # Apply date filtering if start_date and end_date are provided
    if start_date and end_date:
        start_date = datetime.strptime(start_date, '%Y-%m-%d').date()
        end_date = datetime.strptime(end_date, '%Y-%m-%d').date()
        query = query.filter(DrugUpdate.update_date >= start_date, DrugUpdate.update_date <= end_date)

    # Apply search filtering if search_query is provided
    if search_query:
        query = query.filter(
            (Drug.name.ilike(f'%{search_query}%')) |  # Search by drug name
            (User.username.ilike(f'%{search_query}%'))  # Search by user name
        )

    # Fetch the results
    updates = query.all()

    return render_template('log_report.html', updates=updates, start_date=start_date, end_date=end_date, search_query=search_query)
    # Run the application
if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)

    

from flask import Flask
from flask_mail import Mail, Message
import os
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

app = Flask(__name__)

# Configure Flask-Mail
app.config['MAIL_SERVER'] = 'smtp.gmail.com'
app.config['MAIL_PORT'] = 587
app.config['MAIL_USE_TLS'] = True
app.config['MAIL_USERNAME'] = os.getenv('MAIL_USERNAME')  # Use environment variables
app.config['MAIL_PASSWORD'] = os.getenv('MAIL_PASSWORD')  # Use environment variables

mail = Mail(app)

def send_email(to, subject, body):
    try:
        # Create a message
        msg = Message(subject, sender=app.config['MAIL_USERNAME'], recipients=[to])
        msg.body = body

        # Push the application context
        with app.app_context():
            mail.send(msg)
            print("Email sent successfully!")
    except Exception as e:
        print(f"Error sending email: {e}")

# Example usage
if __name__ == '__main__':
    send_email('abliebah@mrc.gm', 'Welcome!', 'Welcome to our app!')
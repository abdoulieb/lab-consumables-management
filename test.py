from flask_bcrypt import Bcrypt

bcrypt = Bcrypt()

# Example password
password = "Simplicity@7345615"

# Hash the password before storing it
hashed_password = bcrypt.generate_password_hash(password).decode('utf-8')

# Store hashed_password in the database
print("Hashed Password:", hashed_password)